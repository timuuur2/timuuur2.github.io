<?php
session_start();
require 'db.php';

// CSRF fix: проверяем токен через hash_equals (защита от timing-атак)
if (
    empty($_POST['csrf_token']) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    http_response_code(403);
    exit('Forbidden: недействительный CSRF-токен.');
}

$name      = trim($_POST['name']  ?? '');
$email     = trim($_POST['email'] ?? '');
$phone     = trim($_POST['phone'] ?? '');
$languages = $_POST['languages']  ?? [];

// Валидация
if ($name === '' || $email === '') {
    header("Location: index.php");
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: index.php");
    exit;
}

// SQL Injection fix: prepared statements (уже было, сохраняем)
$stmt = $pdo->prepare("INSERT INTO applications (name, email, phone) VALUES (?, ?, ?)");
$stmt->execute([$name, $email, $phone]);
$app_id = $pdo->lastInsertId();

// Сохраняем языки — только целочисленные id
if (!empty($languages)) {
    $stmt = $pdo->prepare("
        INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)
    ");
    foreach ($languages as $lang_id) {
        if (ctype_digit((string)$lang_id)) {
            $stmt->execute([$app_id, (int)$lang_id]);
        }
    }
}

header("Location: index.php");
exit;
