<?php 
namespace lib;

use Exception;

class App {
    public static function route(){
        // parâmetros
        $uri        = $_SERVER["REQUEST_URI"];
        $servername = $_SERVER["SERVER_NAME"];
        
        $cacheKey   = str_replace("/","_",strtolower($uri));
        $cacheKey   = preg_replace("[^a-z0-9]","",$cacheKey);
        
        $cacheFolder = zion\APP_ROOT."tmp".\DS."cache".\DS.$servername.\DS;
        $cacheFile  = $cacheFolder.$cacheKey;
        
        @mkdir($cacheFolder,0777,true);
        chmod($cacheFolder,0777);
        
        try {
            $config = CDN::getDomainConfig($servername);
            
            if(CDN::byPass()){
                $xCache = "BYPASS";
                
                header("Cache-Control: must-revalidate");
                $offset = strtotime('-10 minute');
                header("Expires: ".gmdate("D, d M Y H:i:s", $offset)." GMT");
                header("x-cache: ".$xCache." on ".$_SERVER["SERVER_ADDR"]);
                
                echo CDN::getOriginalContent($config);
            }else{
                $xCache = "MISS";
                
                // se o cache existe e ainda é válido, usa o cache
                if(file_exists($cacheFile)){
                    $maxTime = CDN::getCacheTime();
                    $lifetime = CDN::getLifetimeFile($cacheFile);
                    
                    if($lifetime < $maxTime){
                        $xCache = "HIT";
                    }
                }
                
                // fazendo a requisição
                if($xCache == "MISS"){
                    $responseBody = CDN::getOriginalContent($config);
                    
                    // gravando no cache
                    $f = fopen($cacheFile,"a+");
                    fwrite($f,$responseBody);
                    fclose($f);
                }
                
                // cabeçalhos para fazer cache de 1 minuto
                header("Cache-Control: must-revalidate");
                $offset = strtotime('+1 minute');
                header("Expires: ".gmdate("D, d M Y H:i:s", $offset)." GMT");
                header("x-cache: ".$xCache." on ".$_SERVER["SERVER_ADDR"]);
                
                readfile($cacheFile);
                echo "<!-- servercache ".date("d/m/Y H:i:s")." -->";
            }
        }catch(Exception $e){
            header("HTTP/1.1 500");
            echo $e->getMessage();
        }
    }
}
?>