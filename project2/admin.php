<?php
// ============================================================
// admin.php — Панель администратора (Задание 6)
// HTTP Basic Authorization
// ============================================================
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/validate.php';

// --- HTTP Basic Auth ---
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] 
           ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] 
           ?? '';

$phpAuthUser = $_SERVER['PHP_AUTH_USER'] ?? '';
$phpAuthPw   = $_SERVER['PHP_AUTH_PW']   ?? '';

// Пробуем достать из заголовка вручную (для CGI)
if ($authHeader && preg_match('/^Basic\s+(.+)$/i', $authHeader, $m)) {
    [$phpAuthUser, $phpAuthPw] = explode(':', base64_decode($m[1]), 2);
}

if (!$phpAuthUser || !verifyAdmin($phpAuthUser, $phpAuthPw)) {
    header('WWW-Authenticate: Basic realm="Drupal-coder Admin"');
    header('HTTP/1.0 401 Unauthorized');
    echo '<h2>401 Unauthorized</h2><p>Необходима авторизация.</p>';
    exit;
}

$allLangs  = getAllLanguages();
$validIds  = array_column($allLangs, 'id');
$message   = '';
$editApp   = null;
$errors    = [];

// --- Удаление ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'delete' && !empty($_POST['id'])) {
        deleteApplication((int) $_POST['id']);
        $message = 'Запись удалена.';
    }

    // --- Сохранение редактирования ---
    if ($_POST['action'] === 'save_edit' && !empty($_POST['id'])) {
        $id   = (int) $_POST['id'];
        $data = [
            'full_name' => $_POST['full_name']  ?? '',
            'phone'     => $_POST['phone']      ?? '',
            'email'     => $_POST['email']      ?? '',
            'birthdate' => $_POST['birthdate']  ?? '',
            'gender'    => $_POST['gender']     ?? '',
            'languages' => $_POST['languages']  ?? [],
            'biography' => $_POST['biography']  ?? '',
            'agreed'    => '1',
        ];
        $errors = validateForm($data, $validIds);
        if (empty($errors)) {
            updateApplication($id, $data);
            replaceApplicationLanguages($id, array_map('intval', $data['languages']));
            $message = 'Запись обновлена.';
        } else {
            $editApp = getApplicationById($id);
        }
    }
}

// --- Открыть форму редактирования ---
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['edit'])) {
    $editApp = getApplicationById((int) $_GET['edit']);
}

$applications = getAllApplications();
$langStats    = getLanguageStats();

// ============================================================
// Вспомогательная функция рендера поля с ошибкой
// ============================================================
function adminFg(string $name, string $label, string $type, array $app, array $post, array $errors, string $extra = ''): void
{
    $val    = !empty($post) ? ($post[$name] ?? '') : ($app[$name] ?? '');
    $hasErr = isset($errors[$name]);
    echo '<div class="fg' . ($hasErr ? ' fg-error' : '') . '">';
    echo "<label>$label</label>";
    echo "<input type=\"$type\" name=\"$name\" value=\"" . htmlspecialchars($val) . "\" $extra>";
    if ($hasErr) echo '<span class="err">' . htmlspecialchars($errors[$name]) . '</span>';
    echo '</div>';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Администратор — Drupal-coder</title>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
body { font-family:'Montserrat',sans-serif; background:#F6F7FB; color:#111827; font-size:14px; }
.top-bar { background:#050C33; color:#fff; padding:14px 32px; display:flex; align-items:center; justify-content:space-between; }
.top-bar h1 { font-size:18px; font-weight:700; }
.top-bar span { font-size:13px; color:#93c5fd; }
.wrap { max-width:1200px; margin:32px auto; padding:0 20px; }
.section-title { font-size:20px; font-weight:700; color:#050C33; margin-bottom:16px; }
.msg { background:#f0fdf4; border:1.5px solid #86efac; color:#166534; border-radius:8px; padding:12px 16px; margin-bottom:20px; font-weight:600; }

/* Table */
.table-wrap { background:#fff; border-radius:12px; box-shadow:0 2px 12px rgba(5,12,51,.06); overflow:hidden; margin-bottom:40px; }
table { width:100%; border-collapse:collapse; }
th { background:#F1F5F9; padding:12px 14px; text-align:left; font-weight:700; font-size:13px; color:#374151; border-bottom:2px solid #E5E5E5; white-space:nowrap; }
td { padding:11px 14px; border-bottom:1px solid #F3F4F6; vertical-align:top; }
tr:last-child td { border-bottom:none; }
tr:hover td { background:#FAFAFA; }
.badge { display:inline-block; background:#EFF6FF; color:#1D4ED8; border-radius:4px; padding:2px 8px; font-size:12px; font-weight:600; }
.btn-sm { border:none; border-radius:6px; padding:6px 12px; font-size:12px; font-weight:600; font-family:'Montserrat',sans-serif; cursor:pointer; text-decoration:none; display:inline-block; }
.btn-edit { background:#DBEAFE; color:#1D4ED8; }
.btn-edit:hover { background:#BFDBFE; }
.btn-del { background:#FEE2E2; color:#DC2626; }
.btn-del:hover { background:#FECACA; }

/* Stats */
.stats-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:14px; margin-bottom:40px; }
.stat-card { background:#fff; border-radius:10px; box-shadow:0 2px 10px rgba(5,12,51,.06); padding:18px 20px; display:flex; align-items:center; gap:14px; }
.stat-bar { height:8px; border-radius:4px; background:#F14D34; min-width:4px; transition:width .4s; }
.stat-info strong { font-size:22px; font-weight:700; color:#050C33; }
.stat-info span  { font-size:12px; color:#6B7280; display:block; }

/* Edit form */
.edit-card { background:#fff; border-radius:12px; box-shadow:0 2px 12px rgba(5,12,51,.08); padding:32px; margin-bottom:40px; }
.edit-card h2 { font-size:18px; font-weight:700; color:#050C33; margin-bottom:24px; }
.form-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.fg { display:flex; flex-direction:column; gap:5px; }
.fg.full { grid-column:1/-1; }
.fg label { font-size:12px; font-weight:600; color:#374151; }
.fg input, .fg select, .fg textarea { border:1.5px solid #E5E5E5; border-radius:7px; padding:10px 12px; font-family:inherit; font-size:13px; transition:border-color .2s; }
.fg input:focus, .fg select:focus, .fg textarea:focus { outline:none; border-color:#F14D34; }
.fg-error input, .fg-error select, .fg-error textarea { border-color:#ef4444; }
.err { color:#dc2626; font-size:11px; }
.radio-g { display:flex; gap:16px; }
.radio-g label { display:flex; align-items:center; gap:5px; font-weight:400; cursor:pointer; }
.btn-save { background:#F14D34; color:#fff; border:none; border-radius:8px; padding:12px 28px; font-size:14px; font-weight:700; font-family:'Montserrat',sans-serif; cursor:pointer; }
.btn-save:hover { background:#d63c25; }
.btn-cancel { background:#F3F4F6; color:#374151; border:none; border-radius:8px; padding:12px 20px; font-size:14px; font-weight:600; font-family:'Montserrat',sans-serif; cursor:pointer; text-decoration:none; display:inline-block; }

@media(max-width:768px){ .form-grid{ grid-template-columns:1fr; } table{ font-size:12px; } }
</style>
</head>
<body>
<div class="top-bar">
    <h1>🛡️ Панель администратора — Drupal-coder</h1>
    <span>Вы вошли как: <strong><?= htmlspecialchars($_SERVER['PHP_AUTH_USER']) ?></strong></span>
</div>

<div class="wrap">
    <?php if ($message): ?>
        <div class="msg">✅ <?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <!-- Форма редактирования -->
    <?php if ($editApp): ?>
    <div class="edit-card">
        <h2>✏️ Редактирование записи #<?= $editApp['id'] ?></h2>
        <form method="POST" action="admin.php">
            <input type="hidden" name="action" value="save_edit">
            <input type="hidden" name="id" value="<?= $editApp['id'] ?>">
            <div class="form-grid">
                <?php
                $postData = $_SERVER['REQUEST_METHOD']==='POST' && !empty($errors) ? $_POST : [];
                adminFg('full_name', 'ФИО',   'text', $editApp, $postData, $errors, 'required maxlength="150"');
                adminFg('phone',    'Телефон','tel',  $editApp, $postData, $errors, 'required');
                adminFg('email',    'E-mail', 'email',$editApp, $postData, $errors, 'required');
                adminFg('birthdate','Дата рождения','date',$editApp, $postData, $errors, 'required');
                ?>
                <!-- Пол -->
                <div class="fg <?= isset($errors['gender'])?'fg-error':'' ?>">
                    <label>Пол</label>
                    <div class="radio-g">
                        <?php foreach(['male'=>'Мужской','female'=>'Женский'] as $val=>$lbl): ?>
                        <?php $cur = !empty($postData) ? ($postData['gender']??'') : ($editApp['gender']??''); ?>
                        <label><input type="radio" name="gender" value="<?=$val?>" <?=$cur===$val?'checked':''?>> <?=$lbl?></label>
                        <?php endforeach; ?>
                    </div>
                    <?php if(isset($errors['gender'])): ?><span class="err"><?=htmlspecialchars($errors['gender'])?></span><?php endif; ?>
                </div>
                <!-- Языки -->
                <div class="fg full <?= isset($errors['languages'])?'fg-error':'' ?>">
                    <label>Языки программирования (Ctrl/Cmd + клик)</label>
                    <select name="languages[]" multiple style="height:140px">
                        <?php
                        $selLangs = !empty($postData) ? array_map('intval',$postData['languages']??[]) : $editApp['language_ids'];
                        foreach($allLangs as $l): $sel=in_array((int)$l['id'],$selLangs)?'selected':''; ?>
                        <option value="<?=$l['id']?>" <?=$sel?>><?=htmlspecialchars($l['name'])?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if(isset($errors['languages'])): ?><span class="err"><?=htmlspecialchars($errors['languages'])?></span><?php endif; ?>
                </div>
                <!-- Биография -->
                <div class="fg full <?= isset($errors['biography'])?'fg-error':'' ?>">
                    <label>Биография</label>
                    <?php $bio = !empty($postData)?($postData['biography']??''):($editApp['biography']??''); ?>
                    <textarea name="biography" rows="4" maxlength="5000"><?=htmlspecialchars($bio)?></textarea>
                    <?php if(isset($errors['biography'])): ?><span class="err"><?=htmlspecialchars($errors['biography'])?></span><?php endif; ?>
                </div>
            </div>
            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="submit" class="btn-save">💾 Сохранить</button>
                <a href="admin.php" class="btn-cancel">Отмена</a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- Список заявок -->
    <p class="section-title">Заявки пользователей (<?= count($applications) ?>)</p>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>ФИО</th>
                    <th>Телефон</th>
                    <th>E-mail</th>
                    <th>Дата рождения</th>
                    <th>Пол</th>
                    <th>Логин</th>
                    <th>Языки</th>
                    <th>Дата заявки</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($applications)): ?>
                <tr><td colspan="10" style="text-align:center;color:#9ca3af;padding:32px">Заявок пока нет</td></tr>
                <?php endif; ?>
                <?php foreach ($applications as $a): ?>
                <tr>
                    <td><?= $a['id'] ?></td>
                    <td><?= htmlspecialchars($a['full_name']) ?></td>
                    <td><?= htmlspecialchars($a['phone']) ?></td>
                    <td><?= htmlspecialchars($a['email']) ?></td>
                    <td><?= htmlspecialchars($a['birthdate']) ?></td>
                    <td><?= $a['gender']==='male'?'Муж.':'Жен.' ?></td>
                    <td><span class="badge"><?= htmlspecialchars($a['login']) ?></span></td>
                    <td style="max-width:200px;font-size:12px"><?= htmlspecialchars($a['languages'] ?? '—') ?></td>
                    <td><?= date('d.m.Y H:i', strtotime($a['created_at'])) ?></td>
                    <td>
                        <a href="admin.php?edit=<?= $a['id'] ?>" class="btn-sm btn-edit">✏️ Ред.</a>
                        <form method="POST" action="admin.php" style="display:inline" onsubmit="return confirm('Удалить запись?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $a['id'] ?>">
                            <button type="submit" class="btn-sm btn-del">🗑️ Удалить</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Статистика по языкам -->
    <p class="section-title">Статистика: любимые языки программирования</p>
    <?php $maxCount = max(1, ...array_column($langStats, 'user_count')); ?>
    <div class="stats-grid">
        <?php foreach ($langStats as $s): ?>
        <div class="stat-card">
            <div class="stat-bar" style="width:<?= round($s['user_count']/$maxCount*80)+4 ?>px"></div>
            <div class="stat-info">
                <strong><?= $s['user_count'] ?></strong>
                <span><?= htmlspecialchars($s['name']) ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
</body>
</html>
