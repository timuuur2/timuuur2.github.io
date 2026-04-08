<?php
session_start();

$pdo = new PDO("mysql:host=localhost;dbname=u82322;charset=utf8mb4", 'u82322', '6121845');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $stmt = $pdo->prepare("SELECT * FROM applications WHERE login=?");
    $stmt->execute([$_POST['login']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($_POST['password'], $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
    }

    header("Location: index.php");
}