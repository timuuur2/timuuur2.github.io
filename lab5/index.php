<?php
session_start();

// подключение БД
$pdo = new PDO("mysql:host=localhost;dbname=u82322;charset=utf8mb4", 'u82322', '6121845');

// cookies
$errors = isset($_COOKIE['errors']) ? json_decode($_COOKIE['errors'], true) : [];
$old = isset($_COOKIE['old']) ? json_decode($_COOKIE['old'], true) : [];

setcookie('errors', '', time()-3600);
setcookie('old', '', time()-3600);

// если авторизован — загружаем данные
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM applications WHERE id=?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) $old = $user;
}

// функции
function value($name) {
    global $old;
    return isset($old[$name]) ? htmlspecialchars($old[$name]) : '';
}

function errorClass($name) {
    global $errors;
    return isset($errors[$name]) ? 'error' : '';
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Анкета</title>
<style>
body {font-family:sans-serif;background:#f4f6f9;padding:40px;}
.container {max-width:500px;margin:auto;background:#fff;padding:30px;border-radius:10px;}
.error {border:2px solid red;}
.error-text {color:red;font-size:12px;}
.success {color:green;text-align:center;}
</style>
</head>
<body>

<div class="container">

<h2>Анкета</h2>

<?php if (isset($_COOKIE['success'])): ?>
<div class="success">Сохранено!</div>
<?php endif; ?>

<?php if (isset($_COOKIE['auth_login'])): ?>
<div class="success">
Логин: <?= $_COOKIE['auth_login'] ?><br>
Пароль: <?= $_COOKIE['auth_password'] ?>
</div>
<?php endif; ?>

<!-- форма -->
<form action="process.php" method="POST">

ФИО:<br>
<input name="full_name" class="<?=errorClass('full_name')?>" value="<?=value('full_name')?>"><br>
<span class="error-text"><?= $errors['full_name'] ?? '' ?></span><br>

Телефон:<br>
<input name="phone" value="<?=value('phone')?>"><br>
<span class="error-text"><?= $errors['phone'] ?? '' ?></span><br>

Email:<br>
<input name="email" value="<?=value('email')?>"><br>
<span class="error-text"><?= $errors['email'] ?? '' ?></span><br>

Дата рождения:<br>
<input type="date" name="birth_date" value="<?=value('birth_date')?>"><br>

Пол:<br>
<label><input type="radio" name="gender" value="male" <?=value('gender')=='male'?'checked':''?>>М</label>
<label><input type="radio" name="gender" value="female" <?=value('gender')=='female'?'checked':''?>>Ж</label><br>

Языки:<br>
<select name="languages[]" multiple>
<?php
$langs = ['PHP','Python','C++','Java'];
$selected = isset($old['languages']) ? $old['languages'] : [];
foreach ($langs as $l) {
    $sel = in_array($l, (array)$selected) ? 'selected' : '';
    echo "<option $sel>$l</option>";
}
?>
</select><br>

Биография:<br>
<textarea name="biography"><?=value('biography')?></textarea><br>

<label>
<input type="checkbox" name="contract" value="1" <?=value('contract_accepted')?'checked':''?>>
Согласен
</label><br>

<button type="submit">Сохранить</button>

</form>

<hr>

<h3>Вход</h3>
<form action="login.php" method="POST">
<input name="login" placeholder="Логин"><br>
<input name="password" placeholder="Пароль"><br>
<button>Войти</button>
</form>

<?php if (isset($_SESSION['user_id'])): ?>
<form method="POST" action="logout.php">
<button>Выйти</button>
</form>
<?php endif; ?>

</div>
</body>
</html>
