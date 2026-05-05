<?php
require_once __DIR__ . '/db.php';

// Генерируем новый правильный хэш
$hash = password_hash('123', PASSWORD_BCRYPT);
echo "Новый хэш: " . $hash . "<br>";

// Проверяем что в БД
$stmt = getDB()->query('SELECT login, password_hash FROM admins');
$row = $stmt->fetch();
echo "В БД логин: " . $row['login'] . "<br>";
echo "В БД хэш: " . $row['password_hash'] . "<br>";
echo "Проверка password_verify: " . (password_verify('123', $row['password_hash']) ? 'OK' : 'FAIL');