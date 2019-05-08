<?php 
namespace lib;

use Exception;

class CDN {
    public static function getDomainConfig($servername){
        return array(
            "origem" => "0.0.0.0"
        );
    }
    
    public static function getOriginalContent($config) : string {
        $url = "http://".$config["origem"].$_SERVER["REQUEST_URI"];
        $method = "GET";
        $data = null;
        $options = array(CURLOPT_HTTPHEADER => array("Host: ".$_SERVER["SERVER_NAME"]));
        $curlInfo = null;
        
        $responseBody = self::curl($url,$method, $data, $options, $curlInfo);
        if($curlInfo["http_code"] != "200"){
            throw new Exception("Erro");
        }
        
        return $responseBody;
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
        if($_SERVER["REQUEST_METHOD"] != "GET"){
            return true;
        }
        
        $prefixArray = [];
        $prefixArray[] = "/modules/";
        $prefixArray[] = "/servico/";
        
        foreach($prefixArray AS $prefix){
            if (strpos($_SERVER["REQUEST_URI"],$prefix) === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Tempo em segundos que o cache será válido
     * @return int
     */
    public static function getCacheTime() : int {
        if (preg_match('/(\.jpg|\.png|\.bmp)$/i', $_SERVER["REQUEST_URI"])) {
            return 31557600; // 365.25 dias
        }
        
        return 3600; // 1 hora
    }
    
    public static function curl($url, $method = "GET", $data = null, $options = null, &$curlInfo = null) {
        if (!function_exists("curl_init")) {
            throw new Exception("A biblioteca curl não esta disponível", -1);
        }
        
        if ($data === null) {
            $data = array();
        }
        
        if (!is_array($options)) {
            $options = array();
        }
        
        // opções default
        if (empty($options)) {
            $options[CURLOPT_TIMEOUT] = 60;
            $options[CURLOPT_CONNECTTIMEOUT] = 30;
            $options[CURLOPT_USERAGENT] = "php";
        }
        
        $ch = curl_init();
        if ($ch === false) {
            throw new Exception("Não foi possível initializar curl (curl_init), verifique se a URL " . $url . " esta acessível", -2);
        }
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 30);
        
        // ignora erros de ssl
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        
        // setando opções definidas pelo usuário
        foreach ($options AS $key => $value) {
            curl_setopt($ch, $key, $value);
        }
        
        // método da requisição
        switch ($method) {
            case "POST":
                curl_setopt($ch, CURLOPT_POST, 1);
                break;
            case "GET":
                curl_setopt($ch, CURLOPT_POSTFIELDS, null);
                curl_setopt($ch, CURLOPT_POST, false);
                curl_setopt($ch, CURLOPT_HTTPGET, true);
                break;
            case "HEAD":
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "HEAD");
                curl_setopt($ch, CURLOPT_NOBODY, true);
                break;
            default:
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
                break;
        }
        
        // dados do corpo da requisição
        // a função http_build_query já codifica os campos
        // Atenção! Essa função tem que ser testada com binarios e upload de arquivos
        if ($data !== null) {
            if (is_array($data)) {
                $fieldsString = http_build_query($data);
                if (!empty($data)) {
                    // necessário para que o outro lado entenda que os parâmetros estão codificados
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        'Content-Type: application/x-www-form-urlencoded',
                        'Cache-Control: no-cache'
                    ));
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $fieldsString);
                }
            } elseif (is_string($data) AND trim($data) != "") {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
        }
        
        $response = curl_exec($ch);
        
        if ($response === false) {
            $errorCode = intval(curl_errno($ch));
            $errorList = array(
                1 => "Protocolo desconhecido",
                3 => "URL incorreta",
                5 => "Host do proxy não encontrado",
                6 => "Host não encontrado",
                7 => "Erro em conectar no host ou proxy",
                9 => "Acesso negado",
                22 => "Erro na requisição",
                26 => "Erro na leitura",
                27 => "Falta de memória",
                28 => "Timeout",
                47 => "Limite de redirecionamento atingido",
                55 => "Erro de rede no envio de dados",
                56 => "Erro de rede na leitura de dados",
            );
            $errorMessage = $errorList[$errorCode];
            if (mb_strlen($errorMessage) <= 0) {
                $errorMessage = "Erro desconhecido em executar curl, verifique se a URL " . $url . " esta acessível";
            }
            
            // concatenando informações adicionais
            $errorMessage = "[" . $errorCode . "][" . $url . "] " . $errorMessage;
            
            throw new Exception($errorMessage, $errorCode);
        }
        $curlInfo = curl_getinfo($ch);
        
        curl_close($ch);
        return $response;
    }
}
?>