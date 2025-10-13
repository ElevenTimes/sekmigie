<?php
// db_init.php
require_once 'config.php'; // Use your existing connection

// --------------------------------------------------
// Create database if it doesn't exist
// --------------------------------------------------
$sql = "CREATE DATABASE IF NOT EXISTS kahoot_singleplayer CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if ($conn->query($sql) === TRUE) {
    echo "Database created or already exists.\n";
} else {
    die("Error creating database: " . $conn->error);
}

// Select the new database
$conn->select_db('kahoot_singleplayer');

// --------------------------------------------------
// Create tables
// --------------------------------------------------

// user table
$sql = "
CREATE TABLE IF NOT EXISTS `user` (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    pfp VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;
";
$conn->query($sql);

// quiz table
$sql = "
CREATE TABLE IF NOT EXISTS `quiz` (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    creator_id BIGINT NOT NULL,
    is_public BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_quiz_creator FOREIGN KEY (creator_id) REFERENCES `user`(id) ON DELETE CASCADE
) ENGINE=InnoDB;
";
$conn->query($sql);

// question table
$sql = "
CREATE TABLE IF NOT EXISTS `question` (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    quiz_id BIGINT NOT NULL,
    title VARCHAR(255),
    text TEXT NOT NULL,
    type ENUM('multiple_choice','true_false','text_input') NOT NULL,
    order_index INT DEFAULT 0,
    CONSTRAINT fk_question_quiz FOREIGN KEY (quiz_id) REFERENCES `quiz`(id) ON DELETE CASCADE
) ENGINE=InnoDB;
";
$conn->query($sql);

// answer_option table
$sql = "
CREATE TABLE IF NOT EXISTS `answer_option` (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    question_id BIGINT NOT NULL,
    text VARCHAR(255) NOT NULL,
    is_correct BOOLEAN DEFAULT FALSE,
    order_index INT DEFAULT 0,
    CONSTRAINT fk_answer_option_question FOREIGN KEY (question_id) REFERENCES `question`(id) ON DELETE CASCADE
) ENGINE=InnoDB;
";
$conn->query($sql);

// quiz_attempt table
$sql = "
CREATE TABLE IF NOT EXISTS `quiz_attempt` (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    quiz_id BIGINT NOT NULL,
    score INT DEFAULT 0,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    finished_at TIMESTAMP NULL,
    CONSTRAINT fk_quiz_attempt_user FOREIGN KEY (user_id) REFERENCES `user`(id) ON DELETE CASCADE,
    CONSTRAINT fk_quiz_attempt_quiz FOREIGN KEY (quiz_id) REFERENCES `quiz`(id) ON DELETE CASCADE
) ENGINE=InnoDB;
";
$conn->query($sql);

// user_answer table
$sql = "
CREATE TABLE IF NOT EXISTS `user_answer` (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    attempt_id BIGINT NOT NULL,
    question_id BIGINT NOT NULL,
    answer_option_id BIGINT DEFAULT NULL,
    text_answer TEXT DEFAULT NULL,
    is_correct BOOLEAN DEFAULT FALSE,
    CONSTRAINT fk_user_answer_attempt FOREIGN KEY (attempt_id) REFERENCES `quiz_attempt`(id) ON DELETE CASCADE,
    CONSTRAINT fk_user_answer_question FOREIGN KEY (question_id) REFERENCES `question`(id) ON DELETE CASCADE,
    CONSTRAINT fk_user_answer_option FOREIGN KEY (answer_option_id) REFERENCES `answer_option`(id) ON DELETE SET NULL
) ENGINE=InnoDB;
";
$conn->query($sql);

echo "All tables created successfully.\n";

$conn->close();
?>

