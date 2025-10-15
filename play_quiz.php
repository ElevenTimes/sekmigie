<?php
session_start();
include 'navbar.php';
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$quiz_id = $_GET['quiz_id'] ?? null;

// Fetch quiz info
$stmt = $conn->prepare("SELECT q.title, q.description, u.username, u.id AS creator_id FROM quiz q JOIN user u ON q.creator_id = u.id WHERE q.id=?");
$stmt->bind_param("i", $quiz_id);
$stmt->execute();
$result = $stmt->get_result();
$quiz = $result->fetch_assoc();

if (!$quiz) {
    die("Quiz not found.");
}

// Start quiz button
if (isset($_POST['start'])) {
    // Insert new quiz attempt
    $stmt = $conn->prepare("INSERT INTO quiz_attempt (user_id, quiz_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $quiz_id);
    $stmt->execute();
    $attempt_id = $stmt->insert_id;
    header("Location: play_questions.php?attempt_id=$attempt_id");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Play Quiz: <?= htmlspecialchars($quiz['title']) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800">

<div class="max-w-3xl mx-auto mt-12 bg-white p-8 rounded-lg shadow-md">
    <h2 class="text-3xl font-bold mb-4"><?= htmlspecialchars($quiz['title']) ?></h2>
    <p class="text-gray-700 mb-2"><?= htmlspecialchars($quiz['description']) ?></p>
    <p class="text-gray-500 mb-4">Created by: 
        <a href="profile.php?user_id=<?= $quiz['creator_id'] ?>" class="text-blue-600 underline">
            <?= htmlspecialchars($quiz['username']) ?>
        </a>
    </p>

    <form method="post">
        <button type="submit" name="start" class="px-6 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition">
            Start Quiz
        </button>
    </form>
</div>

</body>
</html>
