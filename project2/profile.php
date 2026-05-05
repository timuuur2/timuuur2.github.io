<?php
// ============================================================
// profile.php — Профиль пользователя (Задание 5)
// ============================================================
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/validate.php';
session_start();

if (empty($_SESSION['app_id'])) {
    header('Location: login.php');
    exit;
}

$appId      = (int) $_SESSION['app_id'];
$app        = getApplicationById($appId);
$allLangs   = getAllLanguages();
$validIds   = array_column($allLangs, 'id');

// Показываем новые учётные данные только один раз
$newCreds = $_SESSION['new_credentials'] ?? null;
unset($_SESSION['new_credentials']);

$errors   = [];
$success  = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'full_name' => $_POST['full_name']  ?? '',
        'phone'     => $_POST['phone']      ?? '',
        'email'     => $_POST['email']      ?? '',
        'birthdate' => $_POST['birthdate']  ?? '',
        'gender'    => $_POST['gender']     ?? '',
        'languages' => $_POST['languages']  ?? [],
        'biography' => $_POST['biography']  ?? '',
        'agreed'    => '1', // редактирование — согласие уже дано
    ];

    $errors = validateForm($data, $validIds);
    if (empty($errors)) {
        updateApplication($appId, $data);
        replaceApplicationLanguages($appId, array_map('intval', $data['languages']));
        $app     = getApplicationById($appId);
        $success = true;
    }
}

$genderMap = ['male' => 'Мужской', 'female' => 'Женский'];

function fieldVal(array $app, array $post, string $key): string
{
    return htmlspecialchars($_SERVER['REQUEST_METHOD'] === 'POST' ? ($post[$key] ?? '') : ($app[$key] ?? ''));
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Личный кабинет — Drupal-coder</title>
<link rel="stylesheet" href="styles.css">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
body { background:#F6F7FB; }
.profile-wrap { max-width:760px; margin:40px auto; padding:0 20px; }
.profile-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:32px; }
.profile-header h1 { font-family:'Montserrat',sans-serif; font-size:28px; font-weight:700; color:#050C33; margin:0; }
.profile-header a { color:#F14D34; font-size:14px; text-decoration:none; }
.profile-header a:hover { text-decoration:underline; }
.card { background:#fff; border-radius:12px; box-shadow:0 2px 16px rgba(5,12,51,.07); padding:36px; margin-bottom:24px; }
.card h2 { font-size:18px; font-weight:700; color:#050C33; margin:0 0 20px; }
.creds-box { background:#f0fdf4; border:1.5px solid #86efac; border-radius:10px; padding:20px 24px; }
.creds-box p { margin:4px 0; color:#166534; font-size:15px; }
.creds-box strong { font-weight:700; }
.creds-box .warn { color:#92400e; font-size:13px; margin-top:10px; background:#fffbeb; border:1px solid #fcd34d; border-radius:6px; padding:8px 12px; }
.form-grid { display:grid; grid-template-columns:1fr 1fr; gap:18px; }
.form-group { display:flex; flex-direction:column; gap:6px; }
.form-group.full { grid-column:1/-1; }
.form-group label { font-size:13px; font-weight:600; color:#374151; }
.form-group input, .form-group select, .form-group textarea {
    border:1.5px solid #E5E5E5; border-radius:8px; padding:11px 13px;
    font-size:14px; font-family:inherit; transition:border-color .2s;
}
.form-group input:focus, .form-group select:focus, .form-group textarea:focus {
    outline:none; border-color:#F14D34;
}
.form-group select[multiple] { height:160px; }
.form-group.error input, .form-group.error select, .form-group.error textarea { border-color:#ef4444; }
.error-msg { color:#dc2626; font-size:12px; }
.radio-group { display:flex; gap:20px; }
.radio-group label { display:flex; align-items:center; gap:6px; font-weight:400; font-size:14px; cursor:pointer; }
.btn-submit { background:#F14D34; color:#fff; border:none; border-radius:8px; padding:13px 32px; font-size:15px; font-weight:700; font-family:'Montserrat',sans-serif; cursor:pointer; transition:background .2s; }
.btn-submit:hover { background:#d63c25; }
.success-msg { background:#f0fdf4; border:1.5px solid #86efac; color:#166534; border-radius:10px; padding:14px 18px; margin-bottom:20px; font-weight:600; }
.logout-link { color:#6b7280; font-size:13px; text-decoration:none; }
.logout-link:hover { color:#F14D34; }
@media(max-width:600px){ .form-grid{ grid-template-columns:1fr; } }
</style>
</head>
<body>
<div class="profile-wrap">
    <div class="profile-header">
        <h1>Личный кабинет</h1>
        <div style="display:flex;gap:16px;align-items:center;">
            <a href="index.php">← На главную</a>
            <a href="logout.php" class="logout-link">Выйти</a>
        </div>
    </div>

    <?php if ($newCreds): ?>
    <div class="card">
        <h2>🎉 Заявка успешно отправлена!</h2>
        <div class="creds-box">
            <p>Ваши данные для входа:</p>
            <p><strong>Логин:</strong> <?= htmlspecialchars($newCreds['login']) ?></p>
            <p><strong>Пароль:</strong> <?= htmlspecialchars($newCreds['password']) ?></p>
            <div class="warn">⚠️ Сохраните логин и пароль — они показываются только один раз!</div>
        </div>
    </div>
    <?php endif; ?>

    <div class="card">
        <h2>Редактировать данные</h2>
        <?php if ($success): ?>
            <div class="success-msg">✅ Данные успешно обновлены.</div>
        <?php endif; ?>

        <form method="POST" action="profile.php">
            <div class="form-grid">
                <?php
                $postData = $_POST;
                function fg(string $name, string $label, string $type, array $app, array $post, array $errors, string $extra = ''): void
                {
                    $val = $_SERVER['REQUEST_METHOD'] === 'POST' ? ($post[$name] ?? '') : ($app[$name] ?? '');
                    $hasErr = isset($errors[$name]);
                    echo '<div class="form-group' . ($hasErr ? ' error' : '') . '">';
                    echo "<label for=\"$name\">$label</label>";
                    echo "<input type=\"$type\" id=\"$name\" name=\"$name\" value=\"" . htmlspecialchars($val) . "\" $extra>";
                    if ($hasErr) echo '<span class="error-msg">' . htmlspecialchars($errors[$name]) . '</span>';
                    echo '</div>';
                }
                fg('full_name', 'ФИО', 'text', $app, $postData, $errors, 'required maxlength="150"');
                fg('phone',     'Телефон', 'tel', $app, $postData, $errors, 'required');
                fg('email',     'E-mail',  'email', $app, $postData, $errors, 'required');
                fg('birthdate', 'Дата рождения', 'date', $app, $postData, $errors, 'required');
                ?>

                <!-- Пол -->
                <div class="form-group <?= isset($errors['gender']) ? 'error' : '' ?>">
                    <label>Пол</label>
                    <div class="radio-group">
                        <?php foreach(['male'=>'Мужской','female'=>'Женский'] as $val=>$lbl): ?>
                        <?php $cur = $_SERVER['REQUEST_METHOD']==='POST' ? ($postData['gender']??'') : ($app['gender']??''); ?>
                        <label>
                            <input type="radio" name="gender" value="<?= $val ?>" <?= $cur===$val?'checked':'' ?>>
                            <?= $lbl ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <?php if(isset($errors['gender'])): ?><span class="error-msg"><?= htmlspecialchars($errors['gender']) ?></span><?php endif; ?>
                </div>

                <!-- Языки -->
                <div class="form-group full <?= isset($errors['languages']) ? 'error' : '' ?>">
                    <label for="languages">Любимый язык программирования (можно несколько)</label>
                    <select name="languages[]" id="languages" multiple required>
                        <?php
                        $selLangs = $_SERVER['REQUEST_METHOD']==='POST' 
                            ? array_map('intval', $postData['languages'] ?? [])
                            : $app['language_ids'];
                        foreach ($allLangs as $l):
                            $sel = in_array((int)$l['id'], $selLangs) ? 'selected' : '';
                        ?>
                        <option value="<?= $l['id'] ?>" <?= $sel ?>><?= htmlspecialchars($l['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color:#6b7280">Зажмите Ctrl (Windows) или Cmd (Mac) для множественного выбора</small>
                    <?php if(isset($errors['languages'])): ?><span class="error-msg"><?= htmlspecialchars($errors['languages']) ?></span><?php endif; ?>
                </div>

                <!-- Биография -->
                <div class="form-group full <?= isset($errors['biography']) ? 'error' : '' ?>">
                    <label for="biography">Биография</label>
                    <?php $bio = $_SERVER['REQUEST_METHOD']==='POST' ? ($postData['biography']??'') : ($app['biography']??''); ?>
                    <textarea id="biography" name="biography" rows="5" required maxlength="5000"><?= htmlspecialchars($bio) ?></textarea>
                    <?php if(isset($errors['biography'])): ?><span class="error-msg"><?= htmlspecialchars($errors['biography']) ?></span><?php endif; ?>
                </div>
            </div>

            <div style="margin-top:24px;">
                <button type="submit" class="btn-submit">Сохранить изменения</button>
            </div>
        </form>
    </div>

    <div style="text-align:center; color:#9ca3af; font-size:12px;">
        Логин: <strong><?= htmlspecialchars($app['login']) ?></strong> &nbsp;|&nbsp;
        Заявка №<?= $appId ?> от <?= date('d.m.Y H:i', strtotime($app['created_at'])) ?>
    </div>
</div>
</body>
</html>
