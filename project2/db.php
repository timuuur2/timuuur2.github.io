<?php
// ============================================================
// db.php — Подключение к БД и вспомогательные функции
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'u82322');   // 
define('DB_PASS', '6121845'); // 
define('DB_NAME', 'u82322');  // 

/**
 * Возвращает единственный экземпляр PDO (Singleton).
 */
function getDB(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_NAME),
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    }
    return $pdo;
}

/**
 * Возвращает список всех языков программирования из справочника.
 * @return array<int, array{id: int, name: string}>
 */
function getAllLanguages(): array
{
    $stmt = getDB()->query('SELECT id, name FROM languages ORDER BY id');
    return $stmt->fetchAll();
}

/**
 * Сохраняет новую заявку и возвращает её ID.
 */
function insertApplication(array $data, string $login, string $passwordHash): int
{
    $db = getDB();
    $sql = 'INSERT INTO applications
                (full_name, phone, email, birthdate, gender, biography, login, password_hash)
            VALUES
                (:full_name, :phone, :email, :birthdate, :gender, :biography, :login, :password_hash)';
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':full_name'     => $data['full_name'],
        ':phone'         => $data['phone'],
        ':email'         => $data['email'],
        ':birthdate'     => $data['birthdate'],
        ':gender'        => $data['gender'],
        ':biography'     => $data['biography'],
        ':login'         => $login,
        ':password_hash' => $passwordHash,
    ]);
    return (int) $db->lastInsertId();
}

/**
 * Привязывает языки программирования к заявке.
 * @param int[] $languageIds
 */
function insertApplicationLanguages(int $appId, array $languageIds): void
{
    $db   = getDB();
    $stmt = $db->prepare(
        'INSERT IGNORE INTO application_languages (application_id, language_id) VALUES (:app_id, :lang_id)'
    );
    foreach ($languageIds as $langId) {
        $stmt->execute([':app_id' => $appId, ':lang_id' => (int) $langId]);
    }
}

/**
 * Обновляет данные заявки (без логина и пароля).
 */
function updateApplication(int $id, array $data): void
{
    $sql = 'UPDATE applications SET
                full_name = :full_name,
                phone     = :phone,
                email     = :email,
                birthdate = :birthdate,
                gender    = :gender,
                biography = :biography
            WHERE id = :id';
    $stmt = getDB()->prepare($sql);
    $stmt->execute([
        ':full_name' => $data['full_name'],
        ':phone'     => $data['phone'],
        ':email'     => $data['email'],
        ':birthdate' => $data['birthdate'],
        ':gender'    => $data['gender'],
        ':biography' => $data['biography'],
        ':id'        => $id,
    ]);
}

/**
 * Перезаписывает языки программирования для заявки.
 * @param int[] $languageIds
 */
function replaceApplicationLanguages(int $appId, array $languageIds): void
{
    $db = getDB();
    $db->prepare('DELETE FROM application_languages WHERE application_id = :id')
       ->execute([':id' => $appId]);
    insertApplicationLanguages($appId, $languageIds);
}

/**
 * Возвращает заявку по ID вместе с языками.
 */
function getApplicationById(int $id): ?array
{
    $stmt = getDB()->prepare(
        'SELECT a.*, GROUP_CONCAT(al.language_id) AS language_ids
         FROM applications a
         LEFT JOIN application_languages al ON al.application_id = a.id
         WHERE a.id = :id
         GROUP BY a.id'
    );
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    if (!$row) return null;
    $row['language_ids'] = $row['language_ids'] ? array_map('intval', explode(',', $row['language_ids'])) : [];
    return $row;
}

/**
 * Возвращает заявку по логину.
 */
function getApplicationByLogin(string $login): ?array
{
    $stmt = getDB()->prepare(
        'SELECT a.*, GROUP_CONCAT(al.language_id) AS language_ids
         FROM applications a
         LEFT JOIN application_languages al ON al.application_id = a.id
         WHERE a.login = :login
         GROUP BY a.id'
    );
    $stmt->execute([':login' => $login]);
    $row = $stmt->fetch();
    if (!$row) return null;
    $row['language_ids'] = $row['language_ids'] ? array_map('intval', explode(',', $row['language_ids'])) : [];
    return $row;
}

/**
 * Возвращает все заявки (для панели администратора).
 */
function getAllApplications(): array
{
    $stmt = getDB()->query(
        'SELECT a.id, a.full_name, a.phone, a.email, a.birthdate, a.gender, a.login, a.created_at,
                GROUP_CONCAT(l.name ORDER BY l.name SEPARATOR ", ") AS languages
         FROM applications a
         LEFT JOIN application_languages al ON al.application_id = a.id
         LEFT JOIN languages l ON l.id = al.language_id
         GROUP BY a.id
         ORDER BY a.created_at DESC'
    );
    return $stmt->fetchAll();
}

/**
 * Удаляет заявку (каскадно удаляет языки).
 */
function deleteApplication(int $id): void
{
    getDB()->prepare('DELETE FROM applications WHERE id = :id')->execute([':id' => $id]);
}

/**
 * Возвращает статистику использования языков.
 */
function getLanguageStats(): array
{
    $stmt = getDB()->query(
        'SELECT l.name, COUNT(al.application_id) AS user_count
         FROM languages l
         LEFT JOIN application_languages al ON al.language_id = l.id
         GROUP BY l.id, l.name
         ORDER BY user_count DESC'
    );
    return $stmt->fetchAll();
}

/**
 * Проверяет учётные данные администратора.
 */
function verifyAdmin(string $login, string $password): bool
{
    $stmt = getDB()->prepare('SELECT password_hash FROM admins WHERE login = :login');
    $stmt->execute([':login' => $login]);
    $row = $stmt->fetch();
    return $row && password_verify($password, $row['password_hash']);
}

/**
 * Генерирует уникальный логин.
 */
function generateUniqueLogin(): string
{
    do {
        $login = 'user_' . strtolower(substr(bin2hex(random_bytes(4)), 0, 8));
        $stmt  = getDB()->prepare('SELECT id FROM applications WHERE login = :login');
        $stmt->execute([':login' => $login]);
    } while ($stmt->fetch());
    return $login;
}
