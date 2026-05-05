-- ============================================================
-- Drupal-coder: База данных в 3-й нормальной форме
-- Задание 3–8
-- ============================================================

-- Таблица языков программирования (справочник)
CREATE TABLE IF NOT EXISTS languages (
    id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(50)  NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO languages (name) VALUES
    ('Pascal'), ('C'), ('C++'), ('JavaScript'), ('PHP'),
    ('Python'), ('Java'), ('Haskell'), ('Clojure'),
    ('Prolog'), ('Scala'), ('Go')
ON DUPLICATE KEY UPDATE name = name;

-- Таблица заявок пользователей
CREATE TABLE IF NOT EXISTS applications (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name     VARCHAR(150) NOT NULL,
    phone         VARCHAR(20)  NOT NULL,
    email         VARCHAR(255) NOT NULL,
    birthdate     DATE         NOT NULL,
    gender        ENUM('male','female') NOT NULL,
    biography     TEXT         NOT NULL,
    login         VARCHAR(50)  NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Таблица связи «заявка — языки» (один ко многим)
CREATE TABLE IF NOT EXISTS application_languages (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    application_id INT UNSIGNED NOT NULL,
    language_id    INT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_app_lang (application_id, language_id),
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (language_id)    REFERENCES languages(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Таблица администраторов (Задание 6)
CREATE TABLE IF NOT EXISTS admins (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    login         VARCHAR(50)  NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Администратор по умолчанию: login=admin, password=admin123
-- Хэш сгенерирован через password_hash('admin123', PASSWORD_BCRYPT)
INSERT INTO admins (login, password_hash) VALUES
    ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')
ON DUPLICATE KEY UPDATE id = id;
