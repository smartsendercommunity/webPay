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
if ($input["phone"] == NULL && $input["email"] == NULL) {
    $result["state"] = false;
    $result["contacts"] = "phone or email is missing";
}
if ($input["description"] == NULL) {
    $result["state"] = false;
    $result["message"]["description"] = "description is missing";
}
if ($input["action"] == NULL) {
    $result["state"] = false;
    $result["message"] = "action is missing";
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
$send_data["urls"]["resourceNotifyUrl"] = $url."/callback.php?action=".$input["action"];

// Получение списка товаров в корзине пользователя
$cursor = json_decode(send_bearer("https://api.smartsender.com/v1/contacts/".$input["userId"]."/checkout?page=1&limitation=20", $ss_token), true);
if ($cursor["error"] != NULL && $cursor["error"] != 'undefined') {
    $result["status"] = "error";
    $result["message"][] = "Ошибка получения данных из SmartSender";
    if ($cursor["error"]["code"] == 404 || $cursor["error"]["code"] == 400) {
        $result["message"][] = "Пользователь не найден. Проверте правильность идентификатора пользователя и приналежность токена к текущему проекту.";
    } else if ($cursor["error"]["code"] == 403) {
        $result["message"][] = "Токен проекта SmartSender указан неправильно. Проверте правильность токена.";
    }
    echo json_encode($result);
    exit;
} else if (empty($cursor["collection"])) {
    $result["status"] = "error";
    $result["message"][] = "Корзина пользователя пустая. Для тестирования добавте товар в корзину.";
    echo json_encode($result);
    exit;
}
$pages = $cursor["cursor"]["pages"];
for ($i = 1; $i <= $pages; $i++) {
    $checkout = json_decode(send_bearer("https://api.smartsender.com/v1/contacts/".$input["userId"]."/checkout?page=".$i."&limitation=20", $ss_token), true);
	$essences = $checkout["collection"];
	$send_data["total"]["currency"] = $essences[0]["cash"]["currency"];
	foreach ($essences as $product) {
	    $items["price"]["amount"] = $product["price"];
	    $items["price"]["currency"] = $product["cash"]["currency"];
	    $items["quantity"] = $product["pivot"]["quantity"];
	    $items["name"] = $product["product"]["name"].': '.$product["name"];
	    $send_data["items"][] = $items;
	    unset($items);
		$summ[] = $product["pivot"]["quantity"]*$product["cash"]["amount"];
    	}
    }
    
$send_data["total"]["amount"] = array_sum($summ);

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






