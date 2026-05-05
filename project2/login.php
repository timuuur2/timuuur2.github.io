<?php
// ============================================================
// login.php — Страница входа (Задание 5)
// ============================================================
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/validate.php';
session_start();

// Уже авторизован — перенаправляем
if (!empty($_SESSION['app_id'])) {
    header('Location: profile.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login    = trim($_POST['login']    ?? '');
    $password = trim($_POST['password'] ?? '');

    $app = getApplicationByLogin($login);
    if ($app && password_verify($password, $app['password_hash'])) {
        $_SESSION['app_id'] = $app['id'];
        $_SESSION['login']  = $app['login'];
        header('Location: profile.php');
        exit;
    }
    $error = 'Неверный логин или пароль.';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Вход — Drupal-coder</title>
<link rel="stylesheet" href="styles.css">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
body { background: #F6F7FB; display:flex; align-items:center; justify-content:center; min-height:100vh; }
.login-box { background:#fff; border-radius:12px; box-shadow:0 4px 32px rgba(5,12,51,.08); padding:48px 40px; width:100%; max-width:420px; }
.login-box h1 { font-family:'Montserrat',sans-serif; font-size:24px; font-weight:700; color:#050C33; margin:0 0 8px; }
.login-box p  { color:#6b7280; font-size:14px; margin:0 0 32px; }
.login-box label { display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px; }
.login-box input { width:100%; box-sizing:border-box; border:1.5px solid #E5E5E5; border-radius:8px; padding:12px 14px; font-size:15px; font-family:inherit; margin-bottom:18px; transition:border-color .2s; }
.login-box input:focus { outline:none; border-color:#F14D34; }
.login-box .btn { width:100%; padding:14px; background:#F14D34; color:#fff; border:none; border-radius:8px; font-size:16px; font-weight:700; font-family:'Montserrat',sans-serif; cursor:pointer; transition:background .2s; }
.login-box .btn:hover { background:#d63c25; }
.login-box .error { background:#fff0ee; color:#c0392b; border:1px solid #f5c6c0; border-radius:8px; padding:12px 14px; margin-bottom:20px; font-size:14px; }
.login-box .back { display:block; text-align:center; margin-top:20px; color:#F14D34; font-size:14px; text-decoration:none; }
.login-box .back:hover { text-decoration:underline; }
</style>
</head>
<body>
<div class="login-box">
    <h1>Вход в личный кабинет</h1>
    <p>Используйте логин и пароль, полученные при отправке заявки.</p>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <label for="login">Логин</label>
        <input type="text" id="login" name="login" value="<?= htmlspecialchars($_POST['login'] ?? '') ?>" required autocomplete="username">

        <label for="password">Пароль</label>
        <input type="password" id="password" name="password" required autocomplete="current-password">

        <button type="submit" class="btn">Войти</button>
    </form>
    <a href="index.php" class="back">← На главную</a>
</div>
</body>
</html>
