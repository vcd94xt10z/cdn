<?php 
namespace lib;

use Exception;
use zion\utils\HTTPUtils;

/**
 * @author Vinicius Cesar Dias
 * @since 08/05/2019
 */
class App {
    public static function route(){
        // parâmetros
        $uri        = $_SERVER["REQUEST_URI"];
        $servername = $_SERVER["SERVER_NAME"];
        
        $cacheKey   = str_replace(array("/","-"),"_",strtolower($uri));
        $cacheKey   = preg_replace("[^a-z0-9]","",$cacheKey);
        
        $cacheFolder = \zion\APP_ROOT."tmp".\DS."cache".\DS.$servername.\DS;
        $cacheFile  = $cacheFolder.$cacheKey;
        
        if(!file_exists($cacheFolder)){
            @mkdir($cacheFolder,0777,true);
            chmod($cacheFolder,0777);
        }
        
        try {
            $config = CDN::getDomainConfig($servername);
            if(CDN::byPass()){
                $xCache = "BYPASS";
                
                header("Cache-Control: must-revalidate, max-age: 0, s-maxage: 0, private");
                header("Pragma: no-cache");
                header("x-cache: ".$xCache." on ".$_SERVER["SERVER_ADDR"]);
                
                $response = CDN::getOriginalContent($config);
                
                $fileInfo = array(
                    "headers" => $response["lastHeaders"],
                    "content" => $response["body"]
                );
                
                CDN::send($fileInfo,$xCache);
            }else{
                $xCache = "MISS";
                
                // se o cache existe e ainda é válido, usa o cache
                $fileInfo = null;
                if(file_exists($cacheFile)){
                    $fileInfo = CDN::loadCache($cacheFile);
                    $lifetime = CDN::getLifetimeFile($cacheFile);
                    if($fileInfo != null AND $lifetime < $fileInfo["smaxage"]){
                        $xCache = "HIT";
                    }
                }
                
                // fazendo a requisição
                if($xCache == "MISS"){
                    $response = CDN::getOriginalContent($config);
                    
                    $cacheControl = null;
                    foreach($response["lastHeaders"] AS $header){
                        if(strtolower($header["key"]) == "cache-control"){
                            $cacheControl = HTTPUtils::parseCacheControl($header["value"]);
                        }
                    }
                    
                    // gravando no cache
                    CDN::saveCache($cacheFile, $response["lastHeaders"], $response["body"], 
                        $cacheControl["max-age"], $cacheControl["s-maxage"]);
                    
                    // gerando fileinfo
                    $fileInfo = array(
                        "headers" => $response["lastHeaders"],
                        "content" => $response["body"]
                    );
                }
                
                CDN::send($fileInfo,$xCache);
            }
        }catch(Exception $e){
            header("HTTP/1.1 500");
            echo $e->getMessage();
        }
        exit();
    }
}
?>