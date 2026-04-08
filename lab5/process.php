<?php
session_start();

$pdo = new PDO("mysql:host=localhost;dbname=u82322;charset=utf8mb4", 'u82322', '6121845');

$data = $_POST;
$errors = [];

// валидация
if (empty($data['full_name'])) $errors['full_name'] = "Введите ФИО";
if (empty($data['phone'])) $errors['phone'] = "Введите телефон";
if (empty($data['email'])) $errors['email'] = "Введите email";

if (!empty($errors)) {
    setcookie('errors', json_encode($errors), time()+3600);
    setcookie('old', json_encode($data), time()+3600);
    header("Location: index.php");
    exit;
}

// если авторизован — UPDATE
if (isset($_SESSION['user_id'])) {

    $stmt = $pdo->prepare("
        UPDATE applications SET
        full_name=?, phone=?, email=?, birth_date=?, gender=?, biography=?
        WHERE id=?
    ");

    $stmt->execute([
        $data['full_name'],
        $data['phone'],
        $data['email'],
        $data['birth_date'],
        $data['gender'],
        $data['biography'],
        $_SESSION['user_id']
    ]);

} else {
    // генерация логина/пароля
    $login = 'user_' . uniqid();
    $password = bin2hex(random_bytes(4));
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("
        INSERT INTO applications
        (full_name, phone, email, birth_date, gender, biography, contract_accepted, login, password_hash)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $data['full_name'],
        $data['phone'],
        $data['email'],
        $data['birth_date'],
        $data['gender'],
        $data['biography'],
        1,
        $login,
        $hash
    ]);

    setcookie('auth_login', $login, time()+60);
    setcookie('auth_password', $password, time()+60);
}

setcookie('success', '1', time()+5);

header("Location: index.php");