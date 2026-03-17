<?php
session_start();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Анкета</title>
    <style>
        
        * {margin:0;padding:0;box-sizing:border-box;}
        body {font-family:'Segoe UI',sans-serif;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh;padding:20px;}
        .container {max-width:800px;margin:0 auto;background:white;border-radius:15px;box-shadow:0 20px 60px rgba(0,0,0,0.3);overflow:hidden;}
        .header {background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;padding:30px;text-align:center;}
        .header h1 {font-size:2em;margin-bottom:10px;}
        .content {padding:30px;}
        .form-group {margin-bottom:25px;}
        label {display:block;margin-bottom:8px;font-weight:600;color:#333;}
        input[type="text"], input[type="tel"], input[type="email"], input[type="date"], select, textarea {
            width:100%;padding:12px 15px;border:2px solid #e0e0e0;border-radius:8px;font-size:16px;transition:all 0.3s ease;
        }
        input:focus, select:focus, textarea:focus {outline:none;border-color:#667eea;box-shadow:0 0 0 3px rgba(102,126,234,0.1);}
        .radio-group {display:flex;gap:20px;flex-wrap:wrap;}
        .radio-option {display:flex;align-items:center;gap:8px;}
        .radio-option input[type="radio"] {width:18px;height:18px;cursor:pointer;}
        .radio-option label {margin:0;cursor:pointer;}
        select[multiple] {height:150px;}
        .checkbox-group {display:flex;align-items:center;gap:10px;}
        .checkbox-group input[type="checkbox"] {width:18px;height:18px;cursor:pointer;}
        .checkbox-group label {margin:0;cursor:pointer;}
        .btn-submit {background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;border:none;padding:15px 30px;font-size:18px;font-weight:600;border-radius:8px;cursor:pointer;width:100%;transition:transform 0.3s ease, box-shadow 0.3s ease;}
        .btn-submit:hover {transform:translateY(-2px);box-shadow:0 10px 30px rgba(102,126,234,0.4);}
        .btn-submit:active {transform:translateY(0);}
        .alert {padding:15px;border-radius:8px;margin-bottom:20px;}
        .alert-success {background:#d4edda;color:#155724;border:1px solid #c3e6cb;}
        .alert-error {background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;}
        .error-list {margin-top:10px;padding-left:20px;}
        .error-list li {color:#721c24;margin-bottom:5px;}
        @media (max-width:600px){.container{border-radius:0;}.content{padding:20px;}.radio-group{flex-direction:column;gap:10px;}}
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Анкета</h1>
        <p>Пожалуйста, заполните все поля формы</p>
    </div>
    
    <div class="content">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['errors']) && !empty($_SESSION['errors'])): ?>
            <div class="alert alert-error">
                <strong>Пожалуйста, исправьте следующие ошибки:</strong>
                <ul class="error-list">
                    <?php foreach ($_SESSION['errors'] as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php unset($_SESSION['errors']); ?>
        <?php endif; ?>
        
        <form action="process.php" method="POST">
            <div class="form-group">
                <label for="full_name">ФИО *</label>
                <input type="text" id="full_name" name="full_name" required 
                       value="<?php echo isset($_SESSION['old']['full_name']) ? htmlspecialchars($_SESSION['old']['full_name']) : ''; ?>"
                       placeholder="Введите ваше ФИО">
            </div>
            
            <div class="form-group">
                <label for="phone">Телефон *</label>
                <input type="tel" id="phone" name="phone" required 
                       value="<?php echo isset($_SESSION['old']['phone']) ? htmlspecialchars($_SESSION['old']['phone']) : ''; ?>"
                       placeholder="+7 (999) 999-99-99">
            </div>
            
            <div class="form-group">
                <label for="email">E-mail *</label>
                <input type="email" id="email" name="email" required 
                       value="<?php echo isset($_SESSION['old']['email']) ? htmlspecialchars($_SESSION['old']['email']) : ''; ?>"
                       placeholder="example@domain.com">
            </div>
            
            <div class="form-group">
                <label for="birth_date">Дата рождения *</label>
                <input type="date" id="birth_date" name="birth_date" required 
                       value="<?php echo isset($_SESSION['old']['birth_date']) ? htmlspecialchars($_SESSION['old']['birth_date']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label>Пол *</label>
                <div class="radio-group">
                    <div class="radio-option">
                        <input type="radio" id="gender_male" name="gender" value="male" required
                               <?php echo (isset($_SESSION['old']['gender']) && $_SESSION['old']['gender'] == 'male') ? 'checked' : ''; ?>>
                        <label for="gender_male">Мужской</label>
                    </div>
                    <div class="radio-option">
                        <input type="radio" id="gender_female" name="gender" value="female" required
                               <?php echo (isset($_SESSION['old']['gender']) && $_SESSION['old']['gender'] == 'female') ? 'checked' : ''; ?>>
                        <label for="gender_female">Женский</label>
                    </div>
                    <div class="radio-option">
                        <input type="radio" id="gender_other" name="gender" value="other" required
                               <?php echo (isset($_SESSION['old']['gender']) && $_SESSION['old']['gender'] == 'other') ? 'checked' : ''; ?>>
                        <label for="gender_other">Другой</label>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="languages">Любимые языки программирования *</label>
                <select id="languages" name="languages[]" multiple required>
                    <?php
                    $langs = ['Pascal','C','C++','JavaScript','PHP','Python','Java','Haskell','Clojure','Prolog','Scala','Go'];
                    foreach ($langs as $lang) {
                        $selected = (isset($_SESSION['old']['languages']) && in_array($lang,$_SESSION['old']['languages'])) ? 'selected' : '';
                        echo "<option value=\"$lang\" $selected>$lang</option>";
                    }
                    ?>
                </select>
                <small>Для выбора нескольких языков удерживайте Ctrl (Cmd на Mac)</small>
            </div>
            
            <div class="form-group">
                <label for="biography">Биография</label>
                <textarea id="biography" name="biography" rows="6" placeholder="Расскажите о себе"><?php echo isset($_SESSION['old']['biography']) ? htmlspecialchars($_SESSION['old']['biography']) : ''; ?></textarea>
            </div>
            
            <div class="form-group">
                <div class="checkbox-group">
                    <input type="checkbox" id="contract" name="contract" value="1" required
                           <?php echo (isset($_SESSION['old']['contract']) && $_SESSION['old']['contract'] == '1') ? 'checked' : ''; ?>>
                    <label for="contract">Я ознакомлен(а) с контрактом *</label>
                </div>
            </div>
            
            <button type="submit" class="btn-submit">Сохранить</button>
        </form>
    </div>
</div>

<?php if (isset($_SESSION['old'])) unset($_SESSION['old']); ?>
</body>
</html>
