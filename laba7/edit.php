<?php
session_start();
require 'db.php';

// Проверка авторизации
if (empty($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// CSRF fix: генерируем токен
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// XSS fix: вспомогательная функция
function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

// SQL Injection fix: строгая валидация id
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: admin.php");
    exit;
}

// СОХРАНЕНИЕ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF fix: проверяем токен
    if (
        empty($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        http_response_code(403);
        exit('Forbidden: недействительный CSRF-токен.');
    }

    $name      = trim($_POST['name']  ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $languages = $_POST['languages']  ?? [];

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: edit.php?id=$id");
        exit;
    }

    // SQL Injection fix: prepared statement
    $pdo->prepare("
        UPDATE applications SET name=?, email=?, phone=? WHERE id=?
    ")->execute([$name, $email, $phone, $id]);

    $pdo->prepare("
        DELETE FROM application_languages WHERE application_id=?
    ")->execute([$id]);

    if (!empty($languages)) {
        $stmt = $pdo->prepare("
            INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)
        ");
        foreach ($languages as $lang_id) {
            if (ctype_digit((string)$lang_id)) {
                $stmt->execute([$id, (int)$lang_id]);
            }
        }
    }

    header("Location: admin.php");
    exit;
}

// Данные заявки
$stmt = $pdo->prepare("SELECT * FROM applications WHERE id=?");
$stmt->execute([$id]);
$app = $stmt->fetch();

if (!$app) {
    header("Location: admin.php");
    exit;
}

$languages = $pdo->query("SELECT * FROM languages")->fetchAll();

$stmt = $pdo->prepare("
    SELECT language_id FROM application_languages WHERE application_id=?
");
$stmt->execute([$id]);
$userLangs = array_column($stmt->fetchAll(), 'language_id');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактирование заявки</title>
</head>
<body>

<!-- XSS fix: e() для id в заголовке -->
<h2>Редактировать заявку #<?= e((string)$id) ?></h2>
<a href="admin.php">← Назад</a>

<form method="POST">

    <!-- CSRF fix: скрытый токен -->
    <input type="hidden" name="csrf_token"
           value="<?= e($_SESSION['csrf_token']) ?>">

    Имя:<br>
    <!-- XSS fix: e() для всех значений из БД -->
    <input name="name"  value="<?= e($app['name'])  ?>" required><br><br>

    Email:<br>
    <input name="email" type="email" value="<?= e($app['email']) ?>" required><br><br>

    Телефон:<br>
    <input name="phone" value="<?= e((string)$app['phone']) ?>"><br><br>

    Языки:<br>
    <?php foreach ($languages as $lang): ?>
        <label>
            <input type="checkbox" name="languages[]"
                   value="<?= e((string)$lang['id']) ?>"
                   <?= in_array($lang['id'], $userLangs) ? 'checked' : '' ?>>
            <?= e($lang['name']) ?>
        </label><br>
    <?php endforeach; ?>

    <br>
    <button type="submit">Сохранить</button>

</form>

</body>
</html>
