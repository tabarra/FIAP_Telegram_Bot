<?php
function strContains($needle, $haystack){
    return (strpos($haystack, $needle) !== false);
}


function getJSON($resource, $request){
    $elementName = $resource.'Result';
    $url = 'http://www2.fiap.com.br/smaiw/app.asmx';
    $headers = [
        "User-Agent: kSOAP/2.0",
        "SOAPAction: http://tempuri.org/".$resource,
        "Content-Type: text/xml",
        "Connection: close",
        "Host: www2.fiap.com.br",
        "Accept-Encoding: gzip"
    ];

    $reqXML = '<v:Envelope xmlns:i="http://www.w3.org/2001/XMLSchema-instance" xmlns:d="http://www.w3.org/2001/XMLSchema" xmlns:c="http://schemas.xmlsoap.org/soap/encoding/" xmlns:v="http://schemas.xmlsoap.org/soap/envelope/">
        <v:Header />
        <v:Body>
            '.$request.'
        </v:Body>
    </v:Envelope>';

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $reqXML); // the SOAP request
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_ENCODING , 'gzip');
    $resp = curl_exec($ch);
    if($resp === null || $resp === false) die('erro curl');
    if(!strContains("\"Erro\":0", $resp)) return false;

    list($trash,$resp)=explode('<soap:Body>',$resp);
    list($resp,$trash)=explode('</soap:Body>',$resp);
    unset($trash);
    $resp=str_replace('xmlns="url"','',$resp);
    $xml=simplexml_load_string($resp);
    $json = $xml->{$elementName}->__toString();

    return $json;
}


function sendJSONData($url, $data, $pretty = false){
    $pretty = ($pretty)? JSON_PRETTY_PRINT : null;
    $data = json_encode($data, $pretty);
    if($data === false) return false;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_USERAGENT, 'tabarra/FIAP_Telegram_Bot v1');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
    curl_setopt($ch, CURLOPT_ENCODING , 'gzip');
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data))
    );

    $resp = curl_exec($ch);
    if($resp === null || $resp === false) return false;
    $resp = @json_decode($resp);
    if(!is_object($resp)) return false;
    return $resp;
}


function sendTelegram($msg, $chat_id, $token){
    $msgObj = new stdClass();
    $msgObj->parse_mode = 'markdown';
    $msgObj->chat_id = $chat_id;
    $msgObj->text = $msg;
    $url = "https://api.telegram.org/bot{$token}/sendMessage";

    return sendJSONData($url, $msgObj);
}
