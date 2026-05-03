<?php
session_start();
require 'db.php';

// Проверка авторизации
if (empty($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// CSRF fix: генерируем токен для форм на этой странице
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// XSS fix: вспомогательная функция
function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

// SQL Injection fix: все запросы через PDO (уже было, сохраняем)
$apps = $pdo->query("
    SELECT
        a.id,
        a.name,
        a.email,
        a.phone,
        GROUP_CONCAT(l.name ORDER BY l.name SEPARATOR ', ') AS languages
    FROM applications a
    LEFT JOIN application_languages al ON a.id = al.application_id
    LEFT JOIN languages l              ON al.language_id = l.id
    GROUP BY a.id
    ORDER BY a.id DESC
")->fetchAll();

$stats = $pdo->query("
    SELECT l.name, COUNT(al.application_id) AS cnt
    FROM languages l
    LEFT JOIN application_languages al ON l.id = al.language_id
    GROUP BY l.id
    ORDER BY cnt DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админ панель</title>
</head>
<body>

<h2>Админ панель</h2>

<!-- CSRF fix: выход через POST-форму с токеном -->
<form method="POST" action="logout.php" style="display:inline">
    <input type="hidden" name="csrf_token"
           value="<?= e($_SESSION['csrf_token']) ?>">
    <button type="submit">Выйти</button>
</form>

<h3>Заявки</h3>

<?php if (empty($apps)): ?>
    <p>Заявок пока нет.</p>
<?php else: ?>
<table border="1" cellpadding="6" cellspacing="0">
    <tr>
        <th>#</th><th>Имя</th><th>Email</th><th>Телефон</th><th>Языки</th><th>Действия</th>
    </tr>
    <?php foreach ($apps as $app): ?>
    <tr>
        <!-- XSS fix: e() для всех ячеек, включая id -->
        <td><?= e((string)$app['id']) ?></td>
        <td><?= e($app['name']) ?></td>
        <td><?= e($app['email']) ?></td>
        <td><?= e((string)$app['phone']) ?></td>
        <td><?= e((string)$app['languages']) ?></td>
        <td>
            <!-- Редактирование через GET — безопасно, нет изменения данных -->
            <a href="edit.php?id=<?= e((string)$app['id']) ?>">Редактировать</a> |

            <!-- CSRF fix: удаление через POST-форму с токеном, НЕ через GET-ссылку -->
            <form method="POST" action="delete.php" style="display:inline"
                  onsubmit="return confirm('Удалить заявку?')">
                <input type="hidden" name="csrf_token"
                       value="<?= e($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="id" value="<?= e((string)$app['id']) ?>">
                <button type="submit">Удалить</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>

<h3>Статистика по языкам</h3>
<ul>
    <?php foreach ($stats as $row): ?>
        <!-- XSS fix: e() для данных из БД -->
        <li><?= e($row['name']) ?>: <?= (int)$row['cnt'] ?></li>
    <?php endforeach; ?>
</ul>

</body>
</html>
