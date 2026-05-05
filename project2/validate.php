<?php
// ============================================================
// validate.php — Валидация данных формы (Задание 4)
// Все поля проверяются регулярными выражениями.
// ============================================================

/**
 * Валидирует данные формы и возвращает массив ошибок.
 * Ключи совпадают с именами полей.
 *
 * @param array $data      Данные POST
 * @param int[] $validLangIds Допустимые ID языков из БД
 * @return array<string, string>
 */
function validateForm(array $data, array $validLangIds): array
{
    $errors = [];

    // --- ФИО ---
    $name = trim($data['full_name'] ?? '');
    if ($name === '') {
        $errors['full_name'] = 'Поле ФИО обязательно для заполнения.';
    } elseif (!preg_match('/^[\p{L}\s\-]{1,150}$/u', $name)) {
        $errors['full_name'] = 'ФИО должно содержать только буквы, пробелы и дефисы, не длиннее 150 символов.';
    }

    // --- Телефон ---
    $phone = trim($data['phone'] ?? '');
    if ($phone === '') {
        $errors['phone'] = 'Поле Телефон обязательно для заполнения.';
    } elseif (!preg_match('/^\+?[\d\s\-\(\)]{7,20}$/', $phone)) {
        $errors['phone'] = 'Телефон должен содержать цифры, пробелы, скобки и дефисы (7–20 символов). Допустимые символы: 0-9 + - ( )';
    }

    // --- E-mail ---
    $email = trim($data['email'] ?? '');
    if ($email === '') {
        $errors['email'] = 'Поле E-mail обязательно для заполнения.';
    } elseif (!preg_match('/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/', $email)) {
        $errors['email'] = 'Введите корректный e-mail адрес (например: user@example.com).';
    }

    // --- Дата рождения ---
    $birthdate = trim($data['birthdate'] ?? '');
    if ($birthdate === '') {
        $errors['birthdate'] = 'Поле Дата рождения обязательно для заполнения.';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthdate) || !validateDate($birthdate)) {
        $errors['birthdate'] = 'Введите корректную дату рождения в формате ГГГГ-ММ-ДД.';
    } elseif (strtotime($birthdate) >= strtotime('today')) {
        $errors['birthdate'] = 'Дата рождения должна быть в прошлом.';
    }

    // --- Пол ---
    $gender = $data['gender'] ?? '';
    if (!in_array($gender, ['male', 'female'], true)) {
        $errors['gender'] = 'Выберите пол: мужской или женский.';
    }

    // --- Любимый язык программирования ---
    $langs = $data['languages'] ?? [];
    if (!is_array($langs) || count($langs) === 0) {
        $errors['languages'] = 'Выберите хотя бы один язык программирования.';
    } else {
        foreach ($langs as $langId) {
            if (!in_array((int) $langId, $validLangIds, true)) {
                $errors['languages'] = 'Выбраны недопустимые языки программирования.';
                break;
            }
        }
    }

    // --- Биография ---
    $bio = trim($data['biography'] ?? '');
    if ($bio === '') {
        $errors['biography'] = 'Поле Биография обязательно для заполнения.';
    } elseif (mb_strlen($bio) > 5000) {
        $errors['biography'] = 'Биография не должна превышать 5000 символов.';
    } elseif (!preg_match('/^[\p{L}\p{N}\p{P}\p{Z}\s]+$/u', $bio)) {
        $errors['biography'] = 'Биография содержит недопустимые символы. Допустимы буквы, цифры, знаки препинания и пробелы.';
    }

    // --- Согласие ---
    $agreed = !empty($data['agreed']);
    if (!$agreed) {
        $errors['agreed'] = 'Необходимо дать согласие на обработку персональных данных.';
    }

    return $errors;
}

/**
 * Проверяет корректность даты.
 */
function validateDate(string $date): bool
{
    [$y, $m, $d] = array_map('intval', explode('-', $date));
    return checkdate($m, $d, $y);
}

// ============================================================
// Cookie-хелперы (Задание 4)
// ============================================================

/**
 * Сохраняет ошибки валидации и введённые значения в Cookies до конца сессии.
 */
function setErrorCookies(array $errors, array $values): void
{
    setcookie('form_errors', json_encode($errors, JSON_UNESCAPED_UNICODE), 0, '/');
    // Сохраняем введённые значения (кроме пароля) — до конца сессии
    $safe = $values;
    setcookie('form_values', json_encode($safe, JSON_UNESCAPED_UNICODE), 0, '/');
}

/**
 * Читает ошибки из Cookie и сбрасывает их.
 */
function popErrorCookies(): array
{
    $errors = [];
    $values = [];
    if (!empty($_COOKIE['form_errors'])) {
        $errors = json_decode($_COOKIE['form_errors'], true) ?? [];
        setcookie('form_errors', '', time() - 3600, '/');
    }
    if (!empty($_COOKIE['form_values'])) {
        $values = json_decode($_COOKIE['form_values'], true) ?? [];
        setcookie('form_values', '', time() - 3600, '/');
    }
    return [$errors, $values];
}

/**
 * Сохраняет успешно отправленные значения на 1 год (для подстановки по умолчанию).
 */
function setSuccessCookies(array $values): void
{
    setcookie('form_defaults', json_encode($values, JSON_UNESCAPED_UNICODE), time() + 365 * 86400, '/');
}

/**
 * Читает дефолтные значения из Cookie (сохранённые при успешной отправке).
 */
function getDefaultCookies(): array
{
    if (!empty($_COOKIE['form_defaults'])) {
        return json_decode($_COOKIE['form_defaults'], true) ?? [];
    }
    return [];
}

// ============================================================
// JWT-хелперы (Задание 5 / 8)
// ============================================================

define('JWT_SECRET', 'change_this_to_a_random_secret_key_32chars!!');

function jwtEncode(array $payload): string
{
    $header  = base64url_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $payload = base64url_encode(json_encode($payload));
    $sig     = base64url_encode(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));
    return "$header.$payload.$sig";
}

function jwtDecode(string $token): ?array
{
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;
    [$header, $payload, $sig] = $parts;
    $expected = base64url_encode(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));
    if (!hash_equals($expected, $sig)) return null;
    $data = json_decode(base64url_decode($payload), true);
    if (!$data || (isset($data['exp']) && $data['exp'] < time())) return null;
    return $data;
}

function base64url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode(string $data): string
{
    return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', (4 - strlen($data) % 4) % 4));
}
