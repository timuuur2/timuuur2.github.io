<?php
session_start();

// Параметры подключения к БД
$host = 'localhost';
$dbname = 'u82322';
$username = 'u82322';
$password = '6121845';

$errors = [];
$old_data = $_POST;

// --- Функции валидации ---
function validateFullName($name) {
    if (empty($name)) return "Поле ФИО обязательно для заполнения";
    if (strlen($name) > 150) return "ФИО не должно превышать 150 символов";
    if (!preg_match("/^[а-яА-ЯёЁa-zA-Z\s-]+$/u", $name)) return "ФИО должно содержать только буквы, пробелы и дефисы";
    return null;
}
function validatePhone($phone) {
    if (empty($phone)) return "Поле Телефон обязательно для заполнения";
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    if (!preg_match('/^(\+7|8)?[0-9]{10}$/', $phone)) return "Введите корректный номер телефона";
    return null;
}
function validateEmail($email) {
    if (empty($email)) return "Поле E-mail обязательно для заполнения";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return "Введите корректный E-mail адрес";
    if (strlen($email) > 100) return "E-mail не должен превышать 100 символов";
    return null;
}
function validateBirthDate($date) {
    if (empty($date)) return "Поле Дата рождения обязательно для заполнения";
    $timestamp = strtotime($date);
    if (!$timestamp) return "Введите корректную дату рождения";
    $min_date = strtotime('1900-01-01');
    $max_date = strtotime('today');
    if ($timestamp < $min_date || $timestamp > $max_date) return "Дата рождения должна быть между 1900-01-01 и сегодняшним днем";
    return null;
}
function validateGender($gender) {
    $allowed = ['male','female','other'];
    if (empty($gender)) return "Поле Пол обязательно для заполнения";
    if (!in_array($gender,$allowed)) return "Выберите корректное значение пола";
    return null;
}
function validateLanguages($languages) {
    $allowed = ['Pascal','C','C++','JavaScript','PHP','Python','Java','Haskell','Clojure','Prolog','Scala','Go'];
    if (empty($languages) || !is_array($languages)) return "Выберите хотя бы один язык программирования";
    foreach ($languages as $lang) if (!in_array($lang,$allowed)) return "Выбран недопустимый язык программирования";
    return null;
}
function validateBiography($bio) {
    if (!empty($bio) && strlen($bio) > 5000) return "Биография не должна превышать 5000 символов";
    return null;
}
function validateContract($contract) {
    if (!isset($contract) || $contract != '1') return "Необходимо подтвердить ознакомление с контрактом";
    return null;
}

// --- Валидация ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($error = validateFullName($_POST['full_name'] ?? '')) $errors['full_name'] = $error;
    if ($error = validatePhone($_POST['phone'] ?? '')) $errors['phone'] = $error;
    if ($error = validateEmail($_POST['email'] ?? '')) $errors['email'] = $error;
    if ($error = validateBirthDate($_POST['birth_date'] ?? '')) $errors['birth_date'] = $error;
    if ($error = validateGender($_POST['gender'] ?? '')) $errors['gender'] = $error;
    if ($error = validateLanguages($_POST['languages'] ?? [])) $errors['languages'] = $error;
    if ($error = validateBiography($_POST['biography'] ?? '')) $errors['biography'] = $error;
    if ($error = validateContract($_POST['contract'] ?? '')) $errors['contract'] = $error;

    if (!empty($errors)) {
        $_SESSION['errors'] = array_values($errors);
        $_SESSION['old'] = $old_data;
        header('Location: index.php');
        exit;
    }

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4",$username,$password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO applications 
            (full_name, phone, email, birth_date, gender, biography, contract_accepted)
            VALUES (:full_name, :phone, :email, :birth_date, :gender, :biography, :contract)");

        $stmt->execute([
            ':full_name' => trim($_POST['full_name']),
            ':phone' => trim($_POST['phone']),
            ':email' => trim($_POST['email']),
            ':birth_date' => $_POST['birth_date'],
            ':gender' => $_POST['gender'],
            ':biography' => !empty($_POST['biography']) ? trim($_POST['biography']) : null,
            ':contract' => 1
        ]);

        $application_id = $pdo->lastInsertId();

        // --- Исправленная часть для языков ---
        $selected_languages = $_POST['languages'];
        $placeholders = implode(',', array_fill(0, count($selected_languages), '?'));
        $stmt = $pdo->prepare("SELECT id, name FROM programming_languages WHERE name IN ($placeholders)");
        $stmt->execute($selected_languages);
        $languages_map = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $languages_map[$row['name']] = $row['id'];
        }

        $stmt = $pdo->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (:application_id, :language_id)");
        foreach ($selected_languages as $lang_name) {
            if (isset($languages_map[$lang_name])) {
                $stmt->execute([
                    ':application_id' => $application_id,
                    ':language_id' => $languages_map[$lang_name]
                ]);
            }
        }

        $pdo->commit();
        $_SESSION['success'] = "Данные успешно сохранены!";
        header('Location: index.php');
        exit;

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $_SESSION['errors'] = ["Ошибка базы данных: ".$e->getMessage()];
        $_SESSION['old'] = $old_data;
        header('Location: index.php');
        exit;
    }
} else {
    header('Location: index.php');
    exit;
}