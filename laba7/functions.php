<?php

// Получить все заявки с языками
function getApplications($pdo) {
    return $pdo->query("
        SELECT
            a.id, a.name, a.email, a.phone,
            GROUP_CONCAT(l.name ORDER BY l.name SEPARATOR ', ') AS languages
        FROM applications a
        LEFT JOIN application_languages al ON a.id  = al.application_id
        LEFT JOIN languages l              ON al.language_id = l.id
        GROUP BY a.id
        ORDER BY a.id DESC
    ")->fetchAll();
}

// Получить все языки
function getLanguages($pdo) {
    return $pdo->query("SELECT * FROM languages ORDER BY name")->fetchAll();
}

// Получить языки конкретной заявки
function getApplicationLanguages($pdo, $application_id) {
    $stmt = $pdo->prepare("
        SELECT language_id FROM application_languages WHERE application_id = ?
    ");
    $stmt->execute([$application_id]);
    return array_column($stmt->fetchAll(), 'language_id');
}
