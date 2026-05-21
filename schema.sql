CREATE TABLE IF NOT EXISTS dmca_reports (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reporter_name VARCHAR(255) NOT NULL,
    reporter_email VARCHAR(255) NOT NULL,
    company_name VARCHAR(255) NOT NULL DEFAULT '',
    original_work TEXT NOT NULL,
    infringing_url TEXT NOT NULL DEFAULT '',
    info_hash CHAR(40) NOT NULL,
    description TEXT NOT NULL DEFAULT '',
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    admin_note TEXT NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
