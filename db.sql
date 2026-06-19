CREATE DATABASE IF NOT EXISTS apuracao_impostos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE apuracao_impostos;
CREATE TABLE IF NOT EXISTS leituras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    arquivo VARCHAR(500) NOT NULL,
    data_lida DATETIME DEFAULT CURRENT_TIMESTAMP,
    competencia VARCHAR(7),
    resumo JSON NOT NULL,
    INDEX idx_competencia (competencia)
);