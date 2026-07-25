<?php
//الملفconfig/config_db.php: يحتوي عل اعدادات الاتصال بقاعدة البيانات باستخدام pdo.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}



$host = "localhost";
$user = "root";
$password = "";
$db = "borrow_it_ar";

$config = new mysqli($host,$user,$password)or die("conect_faild: %s\n". $config ->error);
$config -> select_db($db)

?>