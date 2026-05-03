<?php
session_start();

// Уже авторизован — сразу в панель
if (!empty($_SESSION['admin_id'])) {
    header("Location: admin.php");
    exit;
}

// CSRF fix: генерируем токен
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// XSS fix
function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF fix: проверяем токен
    if (
        empty($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        http_response_code(403);
        exit('Forbidden: недействительный CSRF-токен.');
    }

    require 'db.php';

    $login    = trim($_POST['login']    ?? '');
    $password = trim($_POST['password'] ?? '');

    // SQL Injection fix: prepared statement
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE login = ?");
    $stmt->execute([$login]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        // Session fixation fix: перевыпускаем ID сессии после успешного входа
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $admin['id'];
        header("Location: admin.php");
        exit;
    } else {
        $error = 'Неверный логин или пароль.';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход в админку</title>
</head>
<body>

<h2>Вход в админку</h2>

<?php if ($error): ?>
    <!-- XSS fix: e() для вывода сообщения об ошибке -->
    <p style="color:red;"><?= e($error) ?></p>
<?php endif; ?>

<form method="POST">
    <!-- CSRF fix: скрытый токен -->
    <input type="hidden" name="csrf_token"
           value="<?= e($_SESSION['csrf_token']) ?>">

    <input type="text"     name="login"    placeholder="Логин"  required><br><br>
    <input type="password" name="password" placeholder="Пароль" required><br><br>
    <button type="submit">Войти</button>
</form>

</body>
</html>
