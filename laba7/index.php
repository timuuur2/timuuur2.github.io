<?php
session_start();
require 'db.php';

// CSRF fix: генерируем токен один раз за сессию
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// XSS fix: вспомогательная функция экранирования
function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

$languages = $pdo->query("SELECT * FROM languages")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Форма заявки</title>
</head>
<body>

<h2>Форма заявки</h2>

<form action="process.php" method="POST">

    <!-- CSRF fix: скрытое поле с токеном -->
    <input type="hidden" name="csrf_token"
           value="<?= e($_SESSION['csrf_token']) ?>">

    <p>
        Имя:<br>
        <input type="text" name="name" required>
    </p>

    <p>
        Email:<br>
        <input type="email" name="email" required>
    </p>

    <p>
        Телефон:<br>
        <input type="text" name="phone">
    </p>

    <p>
        Языки программирования:<br>
        <?php foreach ($languages as $lang): ?>
            <label>
                <!-- XSS fix: e() для id и name из БД -->
                <input type="checkbox" name="languages[]"
                       value="<?= e((string)$lang['id']) ?>">
                <?= e($lang['name']) ?>
            </label><br>
        <?php endforeach; ?>
    </p>

    <button type="submit">Отправить</button>

</form>

</body>
</html>
