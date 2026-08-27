CREATE DATABASE IF NOT EXISTS eduadmin
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE eduadmin;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'profesor', 'alumno') DEFAULT 'alumno',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar usuario admin por defecto
-- Password: 894035 (hasheado con bcrypt)
INSERT INTO users (username, email, password, role)
VALUES (
    'admin',
    'admin@eduadmin.com',
    '$2y$10$F4t1hDqn58Q9XQ5eYinssepYrNkaf46A7MM24iIXO6UMlvQCcse2e',
    'admin'
)
ON DUPLICATE KEY UPDATE username = username;
