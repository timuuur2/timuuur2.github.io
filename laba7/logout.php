<?php
session_start();

// CSRF fix: выход только через POST с валидным токеном
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    !empty($_POST['csrf_token']) &&
    !empty($_SESSION['csrf_token']) &&
    hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    session_destroy();
}

header("Location: login.php");
exit;
