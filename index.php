<?php
session_start();
include 'navbar.php';
require 'config.php';

$isLoggedIn = isset($_SESSION['user_id']);

// Allowed sorting options
$allowed_sorts = [
    'title' => 'q.title',
    'created_at' => 'q.created_at',
    'updated_at' => 'q.updated_at'
];

// Determine sort field (default: created_at)
$sort = isset($_GET['sort']) && isset($allowed_sorts[$_GET['sort']]) ? $_GET['sort'] : 'created_at';

// Determine sort order (default: DESC)
$order = isset($_GET['order']) && strtolower($_GET['order']) === 'asc' ? 'ASC' : 'DESC';
$orderBy = $allowed_sorts[$sort] . " " . $order;

// Fetch quizzes
$stmt = $conn->prepare("
    SELECT q.id, q.title, q.creator_id, q.created_at, q.updated_at, u.username 
    FROM quiz q 
    JOIN user u ON q.creator_id = u.id 
    ORDER BY $orderBy
");
$stmt->execute();
$result = $stmt->get_result();
$quizzes = $result->fetch_all(MYSQLI_ASSOC);

// Determine opposite order for next click
$nextOrder = ($order === 'ASC') ? 'desc' : 'asc';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Quiz Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    body {
        font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif;
    }
  </style>
</head>
<body class="bg-gray-50 text-gray-800">

<div class="flex flex-col items-center justify-center mt-12 px-4">
    <div class="w-full max-w-4xl text-center mb-10">
        <?php if ($isLoggedIn): ?>
            <a href="create_quiz.php" class="px-10 py-4 bg-purple-700 text-white text-lg font-extrabold rounded-xl shadow-lg hover:bg-purple-800 transition duration-300 transform hover:scale-105">
                + Create A New Quiz
            </a>
        <?php else: ?>
            <p class="text-xl text-gray-700">Welcome to My Kahoot App!</p>
            <p class="mt-2 text-red-600">Please <a href="login.php" class="underline font-semibold hover:text-red-700">login</a> to create or play a quiz.</p>
        <?php endif; ?>
    </div>

    <div class="w-full max-w-4xl">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800 border-b pb-2">Popular Quizzes</h2>

            <!-- Sorting Buttons -->
            <div class="flex gap-2">
                <?php
                $buttons = [
                    'title' => 'Title',
                    'created_at' => 'Created',
                    'updated_at' => 'Updated'
                ];
                foreach ($buttons as $key => $label):
                    $isActive = $sort === $key;
                    $arrow = $isActive ? ($order === 'ASC' ? '↑' : '↓') : '';
                ?>
                    <a href="?sort=<?= $key ?>&order=<?= $isActive ? $nextOrder : 'asc' ?>"
                       class="px-3 py-2 text-sm font-semibold rounded-lg border <?= $isActive ? 'bg-purple-700 text-white border-purple-700' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-100' ?>">
                        <?= $label ?> <?= $arrow ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        
        <?php if (empty($quizzes)): ?>
            <p class="text-center text-gray-500 py-10 bg-white rounded-lg shadow-md">No quizzes have been created yet. Be the first!</p>
        <?php else: ?>
            <ul class="space-y-4">
                <?php foreach ($quizzes as $quiz): ?>
                    <li class="bg-white p-5 rounded-xl shadow-lg border border-gray-200 hover:shadow-xl hover:border-purple-300 transition duration-300 flex justify-between items-center">
                        <div class="flex flex-col text-left">
                            <span class="text-xl font-bold text-gray-900 mb-1"><?= htmlspecialchars($quiz['title']) ?></span>
                            <span class="text-sm text-gray-500">
                                Created by 
                                <a href="profile.php?user_id=<?= $quiz['creator_id'] ?>" class="text-purple-600 font-semibold hover:underline">
                                    @<?= htmlspecialchars($quiz['username']) ?>
                                </a>
                            </span>
                        </div>
                        <?php if ($isLoggedIn): ?>
                            <a href="play_quiz.php?quiz_id=<?= $quiz['id'] ?>" class="flex items-center px-5 py-2 bg-green-500 text-white font-semibold rounded-lg shadow hover:bg-green-600 transition transform hover:scale-105">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.878v4.244a1 1 0 001.555.832l3.197-2.132c.28-.187.28-.616 0-.803z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                Play Now
                            </a>
                        <?php else: ?>
                            <span class="text-sm text-red-500">Login to Play</span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

</body>
</html>

