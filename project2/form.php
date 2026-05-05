<?php
// ============================================================
// form.php — Обработчик формы (Задания 3, 4, 5)
// ============================================================
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/validate.php';

// Принимаем только POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$validLangs    = getAllLanguages();
$validLangIds  = array_column($validLangs, 'id');

// Собираем данные из POST
$data = [
    'full_name' => $_POST['full_name']  ?? '',
    'phone'     => $_POST['phone']      ?? '',
    'email'     => $_POST['email']      ?? '',
    'birthdate' => $_POST['birthdate']  ?? '',
    'gender'    => $_POST['gender']     ?? '',
    'languages' => $_POST['languages']  ?? [],
    'biography' => $_POST['biography']  ?? '',
    'agreed'    => $_POST['agreed']     ?? '',
];

$errors = validateForm($data, $validLangIds);

if (!empty($errors)) {
    // Сохраняем ошибки и введённые значения в Cookie (Задание 4)
    setErrorCookies($errors, $data);
    header('Location: index.php#contacts');
    exit;
}

// Генерируем логин и пароль (Задание 5)
$login       = generateUniqueLogin();
$plainPass   = bin2hex(random_bytes(6)); // 12 символов
$passHash    = password_hash($plainPass, PASSWORD_BCRYPT);

// Сохраняем в БД
$appId = insertApplication($data, $login, $passHash);
insertApplicationLanguages($appId, array_map('intval', $data['languages']));

// Сохраняем успешные значения как дефолт в Cookie на 1 год (Задание 4)
$defaults = $data;
unset($defaults['agreed'], $defaults['languages']);
setSuccessCookies($defaults);

// Стартуем сессию и помечаем пользователя как авторизованного (Задание 5)
session_start();
$_SESSION['app_id'] = $appId;
$_SESSION['login']  = $login;

// Показываем логин и пароль один раз (Задание 5)
// Сохраняем в сессии — profile.php покажет и сразу удалит
$_SESSION['new_credentials'] = ['login' => $login, 'password' => $plainPass];

header('Location: profile.php');
exit;
