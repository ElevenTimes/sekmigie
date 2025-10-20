<?php
session_start();
include 'navbar.php';
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch quizzes created by this user
$stmt = $conn->prepare("SELECT id, title, description, is_public, created_at FROM quiz WHERE creator_id=? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Quizzes</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800">

<div class="max-w-5xl mx-auto mt-12 bg-white p-8 rounded-lg shadow-md">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-3xl font-bold">My Quizzes</h2>
        <a href="create_quiz.php" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
            + Create New Quiz
        </a>
    </div>

    <?php if ($result->num_rows === 0): ?>
        <div class="text-center py-12 bg-gray-50 rounded-lg border border-gray-200">
            <p class="text-gray-600 mb-4">You haven’t created any quizzes yet.</p>
            <a href="create_quiz.php" class="px-5 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                Create Your First Quiz
            </a>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php while ($quiz = $result->fetch_assoc()): ?>
                <div class="p-6 border border-gray-200 rounded-lg shadow-sm hover:shadow-md transition">
                    <div class="flex justify-between items-center mb-2">
                        <h3 class="text-xl font-semibold"><?= htmlspecialchars($quiz['title']) ?></h3>
                        <span class="px-3 py-1 rounded-full text-sm <?= $quiz['is_public'] ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-700' ?>">
                            <?= $quiz['is_public'] ? 'Public' : 'Private' ?>
                        </span>
                    </div>
                    <p class="text-gray-500 text-sm mb-2">
                        Created on <?= date("F j, Y", strtotime($quiz['created_at'])) ?>
                    </p>
                    <p class="text-gray-700"><?= nl2br(htmlspecialchars($quiz['description'])) ?></p>

                    <div class="flex gap-3 mt-4">
                        <a href="edit_quiz.php?id=<?= $quiz['id'] ?>"
                           class="px-4 py-1 border border-blue-600 text-blue-600 rounded hover:bg-blue-600 hover:text-white transition">
                            Edit
                        </a>
                        <a href="delete_quiz.php?id=<?= $quiz['id'] ?>"
                           onclick="return confirm('Are you sure you want to delete this quiz?')"
                           class="px-4 py-1 border border-red-600 text-red-600 rounded hover:bg-red-600 hover:text-white transition">
                            Delete
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>

</body>
</html>

