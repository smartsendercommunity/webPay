<?php

// Данные интеграции с LiqPay
$store_id = "";
$api_key = "";
$secret_key = "";
$ss_token = "";

// Сервисные данные
$dir = dirname($_SERVER["PHP_SELF"]);
$url = ((!empty($_SERVER["HTTPS"])) ? "https" : "http") . "://" . $_SERVER["HTTP_HOST"] . $dir;
$url = explode("?", $url);
$url = $url[0];