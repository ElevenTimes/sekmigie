<?php
session_start();
include 'navbar.php';
require 'config.php';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);

// Fetch quizzes created by other users
$stmt = $conn->prepare("SELECT q.id, q.title, u.username FROM quiz q JOIN user u ON q.creator_id = u.id ORDER BY q.created_at DESC");
$stmt->execute();
$result = $stmt->get_result();
$quizzes = $result->fetch_all(MYSQLI_ASSOC);

$stmt = $conn->prepare("
    SELECT q.id, q.title, q.creator_id, u.username 
    FROM quiz q 
    JOIN user u ON q.creator_id = u.id 
    ORDER BY q.created_at DESC
");

?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Kahoot Project</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800">

<div class="flex flex-col items-center justify-center mt-20">
    <!-- Create Quiz Button -->
    <?php if ($isLoggedIn): ?>
        <a href="create_quiz.php" class="px-6 py-3 bg-blue-600 text-white font-bold rounded shadow hover:bg-blue-700 transition">
            Create Quiz
        </a>
    <?php else: ?>
        <p class="mb-4 text-red-500">Please <a href="login.php" class="underline">login</a> to create a quiz.</p>
    <?php endif; ?>

    <!-- Quizzes Forum -->
    <div class="mt-10 w-full max-w-3xl">
        <h2 class="text-xl font-bold mb-4">Quizzes by other users</h2>
        <?php foreach ($quizzes as $quiz): ?>
            <li class="p-4 bg-white rounded shadow flex justify-between items-center">
                <div>
                    <span class="font-semibold"><?= htmlspecialchars($quiz['title']) ?></span>
                    <span class="text-gray-500 text-sm ml-2">by 
                        <a href="profile.php?user_id=<?= $quiz['creator_id'] ?>" class="text-blue-600 underline">
                            <?= htmlspecialchars($quiz['username']) ?>
                        </a>
                    </span>
                </div>
                <?php if ($isLoggedIn): ?>
                    <a href="play_quiz.php?quiz_id=<?= $quiz['id'] ?>" class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 transition">
                        Play
                    </a>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>

    </div>
</div>

</body>
</html>


