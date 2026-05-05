<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/validate.php';

// Тестовые данные
$data = [
    'full_name' => 'Тест Тестов',
    'phone'     => '+7 999 123-45-67',
    'email'     => 'test@test.ru',
    'birthdate' => '1990-01-01',
    'gender'    => 'male',
    'languages' => [1, 2],
    'biography' => 'Тестовая биография',
    'agreed'    => '1',
];

try {
    $login    = generateUniqueLogin();
    $hash     = password_hash('testpass', PASSWORD_BCRYPT);
    $id       = insertApplication($data, $login, $hash);
    insertApplicationLanguages($id, [1, 2]);
    echo "Успешно! ID: $id, Логин: $login";
} catch (Exception $e) {
    echo "Ошибка: " . $e->getMessage();
}
