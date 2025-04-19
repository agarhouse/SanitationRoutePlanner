CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert test admin account (password is 'admin', hashed with bcrypt)
INSERT INTO users (username, password, role)
VALUES (
    'admin',
    '$$2a$12$vSIFQviRdF3YGXUZXd.91eELBDLw8ayaBaRAxs5DzPI02C0Plk99y',
    'admin'
)
ON DUPLICATE KEY UPDATE username=username;