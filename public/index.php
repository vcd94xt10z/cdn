<?php
use zion\utils\HTTPUtils;

require(dirname(dirname(dirname(__FILE__)))."/zionphp/autoload.php");

\lib\App::route();

if(!headers_sent()){
    HTTPUtils::status(404);
    HTTPUtils::template(404);
}
?>