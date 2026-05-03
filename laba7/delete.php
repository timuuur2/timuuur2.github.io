<?php
session_start();
require 'db.php';

// Проверка авторизации
if (empty($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// CSRF fix: принимаем только POST-запросы с валидным токеном
if (
    $_SERVER['REQUEST_METHOD'] !== 'POST' ||
    empty($_POST['csrf_token']) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    http_response_code(403);
    exit('Forbidden: недействительный запрос.');
}

// SQL Injection fix: строгая валидация id как целого числа
$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    header("Location: admin.php");
    exit;
}

// Удаляем связи, затем саму заявку (prepared statements)
$pdo->prepare("DELETE FROM application_languages WHERE application_id=?")->execute([$id]);
$pdo->prepare("DELETE FROM applications WHERE id=?")->execute([$id]);

header("Location: admin.php");
exit;
