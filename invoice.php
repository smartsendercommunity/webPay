<?php

// v1   19.11.2021
// Powered by Smart Sender
// https://smartsender.com

ini_set('max_execution_time', '1700');
set_time_limit(1700);
error_reporting(E_ERROR);
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
function generate_string($length) {
    $string = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $all_length = strlen($string);
    $random_string = '';
    for($i = 0; $i < $length; $i++) {
        $random_character = $string[mt_rand(0, $all_length - 1)];
        $random_string .= $random_character;
    }
    return $random_string;
}
}

if ($input["userId"] == NULL) {
    $result["state"] = false;
    $result["message"]["userId"] = "userId is missing";
}
if ($input["amount"] == NULL) {
    $result["state"] = false;
    $result["message"]["amount"] = "amount is missing";
}
if ($input["currency"] == NULL) {
    $result["state"] = false;
    $result["message"]["currency"] = "currency is missing";
}
if ($input["product"] == NULL) {
    $result["state"] = false;
    $result["message"]["product"] = "product is missing";
}
if ($input["action"] == NULL) {
    $result["state"] = false;
    $result["message"]["action"] = "action is missing";
}
if ($result["state"] === false) {
    http_response_code(422);
    echo json_encode($result);
    exit;
}

// Формирование данных
$send_data["resourceId"] = $store_id;
$send_data["resourceOrderNumber"] = $input["userId"]."-".mt_rand(100000, 999999);
$send_data["longDescription"] = $input["description"];
$send_data["customer"]["resourceCustomerId"] = $input["userId"];
if ($input["email"] != NULL) {
    $send_data["customer"]["email"] = $input["email"];
}
if ($input["phone"] != NULL) {
    $send_data["customer"]["phone"] = $input["phone"];
}
if ($input["firstName"] != NULL) {
    $send_data["customer"]["name"] = $input["firstName"];
}
if ($input["lastName"] != NULL) {
    $send_data["customer"]["surname"] = $input["lastName"];
}
$send_data["items"][0]["idx"] = 1;
$send_data["items"][0]["name"] = $input["product"];
$send_data["items"][0]["quantity"] = 1;
$send_data["items"][0]["price"]["amount"] = $input["amount"];
$send_data["items"][0]["price"]["currency"] = $input["currency"];
$send_data["urls"]["resourceNotifyUrl"] = $url."/callback.php?action=".$input["action"];
$send_data["total"]["currency"] = $input["currency"];
$send_data["total"]["amount"] = $input["amount"];

$payload = json_encode($send_data);
$rand_string = generate_string(36);
$string = "POST\n/woc/order\napplication/json;charset=utf-8\n".$store_id."\n".$rand_string."\n".$payload."\n";
$signature = hash_hmac('sha512', $string, $api_key, true);
$send_header[] = "Authorization: HmacSHA512 ".$store_id.":".$rand_string.":".base64_encode($signature);
$send_header[] = "Content-Type: application/json;charset=utf-8";
if ($input["test"] === true) {
    $send_url = "https://sand-box.webpay.by/woc/order";
} else {
    $send_url = "https://api.webpay.by/woc/order";
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $send_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_HTTPHEADER, $send_header);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
$content = curl_exec($ch);
curl_close($ch);


$result = json_decode($content, true);
echo json_encode($result);




