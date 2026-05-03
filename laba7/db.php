<?php
// Information Disclosure fix: отключаем вывод ошибок пользователю
ini_set('display_errors', 0);
error_reporting(0);

$pdo = new PDO(
    "mysql:host=localhost;dbname=u82322;charset=utf8mb4",
    'u82322',
    '6121845',
    [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false, // SQL Injection fix: реальные prepared statements на стороне MySQL
    ]
);
