CREATE DATABASE IF NOT EXISTS fashionGallery CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fashionGallery;

CREATE TABLE IF NOT EXISTS images (
  id INT AUTO_INCREMENT PRIMARY KEY,
  filename VARCHAR(255) NOT NULL,
  caption VARCHAR(255),
  category VARCHAR(100),
  likes INT DEFAULT 0,
  upload_date DATETIME
);
