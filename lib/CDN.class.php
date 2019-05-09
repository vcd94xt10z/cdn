<?php 
namespace lib;

use Exception;
use zion\utils\HTTPUtils;

/**
 * @author Vinicius Cesar Dias
 * @since 08/05/2019
 */
class CDN {
    public static function getDomainConfig($servername){
        $file = \zion\APP_ROOT."cdn.json";
        
        if(!file_exists($file)){
            throw new Exception("O arquivo de configuração não existe");
        }
        $content = file_get_contents($file);
        if($content == ""){
            throw new Exception("O arquivo de configuração não tem conteúdo");
        }
        $json = json_decode($content);
        if($json == null){
            throw new Exception("O arquivo de configuração não é um json válido");
        }
        
        foreach($json AS $obj){
            if($obj->domain == $servername){
                return $obj;
            }
        }
        
        throw new Exception("Domínio ".$servername." não cadastrado");
    }
    
    public static function send(array $fileInfo,$xCache=""){
        $i=0;
        foreach($fileInfo["headers"] AS $header){
            $line = "";
            if($i == 0){
                $line = $header["key"];
            }else{
                $line = $header["key"].": ".$header["value"];
            }
            
            // ignorando esse cabeçalho por enquanto para não causar problemas
            if($header["key"] == "Transfer-Encoding"){
                continue;
            }
            
            header($line);
            $i++;
        }
        
        if($xCache == ""){
            header("x-zcache: zcdn");
        }else{
            header("x-zcache: zcdn (".$xCache.")");
        }
        
        echo $fileInfo["content"];
    }
    
    public static function getOriginalContent($config,&$curlInfo=array()) {
        $url = "http://".$config->source.$_SERVER["REQUEST_URI"];
        $method = "GET";
        $data = null;
        $options = array(CURLOPT_HTTPHEADER => array("Host: ".$_SERVER["SERVER_NAME"]));
        
        $response = HTTPUtils::curl2($url, $method, $data, $options, $curlInfo);
        
        /*
        if($curlInfo["http_code"] != "200"){
            throw new Exception("Erro");
        }
        */
        
        return $response;
    }
    
    public static function saveCache($file,$headers,$content,$maxage,$smaxage){
        $f = fopen($file,"a+");
        $fileContent = array(
            "maxage"  => intval($maxage),
            "smaxage" => intval($smaxage),
            "headers" => $headers,
            "content" => $content
        );
        fwrite($f,serialize($fileContent));
        $fileContent = null;
        fclose($f);
    }
    
    public static function loadCache($file){
        $content = file_get_contents($file);
        $info = unserialize($content);
        return $info;
    }
    
    /**
     * Retorna o tempo de vida do arquivo em segundos
     * @param $file
     * @return int
     */
    public static function getLifetimeFile($file) : int {
        $diff = time()-filemtime($file);
        return $diff;
    }
    
    /**
     * Retorna se a url atual é para fazer cache
     * @return bool
     */
    public static function byPass() : bool {
        if(in_array($_SERVER["REQUEST_METHOD"],array("GET","HEAD"))){
            return false;
        }
        return true;
    }  
}
?>