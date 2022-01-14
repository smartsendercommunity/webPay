<?php

// v1   19.11.2021
// Powered by Smart Sender
// https://smartsender.com

ini_set('max_execution_time', '1700');
set_time_limit(1700);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: application/json');
header('Content-Type: application/json; charset=utf-8');

http_response_code(200);

//--------------

$input = json_decode(file_get_contents('php://input'), true);
include ('config.php');

// Functions
{
function send_forward($inputJSON, $link){
	
$request = 'POST';	
		
$descriptor = curl_init($link);

 curl_setopt($descriptor, CURLOPT_POSTFIELDS, $inputJSON);
 curl_setopt($descriptor, CURLOPT_RETURNTRANSFER, 1);
 curl_setopt($descriptor, CURLOPT_HTTPHEADER, array('Content-Type: application/json')); 
 curl_setopt($descriptor, CURLOPT_CUSTOMREQUEST, $request);

    $itog = curl_exec($descriptor);
    curl_close($descriptor);

   		 return $itog;
		
}
function send_bearer($url, $token, $type = "GET", $param = []){
	
		
$descriptor = curl_init($url);

 curl_setopt($descriptor, CURLOPT_POSTFIELDS, json_encode($param));
 curl_setopt($descriptor, CURLOPT_RETURNTRANSFER, 1);
 curl_setopt($descriptor, CURLOPT_HTTPHEADER, array('User-Agent: M-Soft Integration', 'Content-Type: application/json', 'Authorization: Bearer '.$token)); 
 curl_setopt($descriptor, CURLOPT_CUSTOMREQUEST, $type);

    $itog = curl_exec($descriptor);
    curl_close($descriptor);

   		 return $itog;
		
}
}

$checkSignature = md5($_POST["batch_timestamp"].$_POST["currency_id"].$_POST["amount"].$_POST["payment_method"].$_POST["order_id"].$_POST["site_order_id"].$_POST["transaction_id"].$_POST["payment_type"].$_POST["rrn"].$secret_key);

if ($checkSignature != $_POST["wsb_signature"]) {
    http_response_code(422);
    $result["state"] = false;
    $result["message"]["signature"] = "signature is failed";
    echo json_encode($result);
    exit;
}


// Запуск триггера в Smart Sender
$userId = (explode("-", $_POST["site_order_id"]))[0];
$trigger["name"] = $_GET["action"];
$result["SmartSender"] = json_decode(send_bearer("https://api.smartsender.com/v1/contacts/".$userId."/fire", $ss_token, "POST", $trigger), true);


echo json_encode($result);












