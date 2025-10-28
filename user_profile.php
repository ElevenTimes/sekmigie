<?php
session_start();
include 'navbar.php';
require 'config.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$viewed_user_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'] ?? null;

// Fetch user info
$stmt = $conn->prepare("SELECT id, username, email, pfp, created_at FROM user WHERE id=?");
$stmt->bind_param("i", $viewed_user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    echo "<div class='text-center mt-20 text-gray-600'>User not found.</div>";
    exit();
}

// Fetch user’s public quizzes
$stmt = $conn->prepare("SELECT id, title, description, is_public, created_at FROM quiz WHERE creator_id=? AND is_public=1 ORDER BY created_at DESC");
$stmt->bind_param("i", $viewed_user_id);
$stmt->execute();
$quizzes = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($user['username']) ?>'s Profile</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800">

<div class="max-w-4xl mx-auto mt-12 bg-white p-8 rounded-lg shadow-md">
    <div class="flex flex-col items-center">
        <?php if ($user['pfp']): ?>
            <img src="<?= htmlspecialchars($user['pfp']) ?>" class="w-24 h-24 rounded-full object-cover mb-3">
        <?php else: ?>
            <div class="w-24 h-24 rounded-full bg-gray-300 flex items-center justify-center text-2xl font-semibold text-gray-700 mb-3">
                <?= strtoupper(substr($user['username'], 0, 1)) ?>
            </div>
        <?php endif; ?>

        <h2 class="text-3xl font-bold mb-1"><?= htmlspecialchars($user['username']) ?></h2>
        <p class="text-gray-500 text-sm mb-4">Joined on <?= date("F j, Y", strtotime($user['created_at'])) ?></p>

        <?php if ($user_id === $viewed_user_id): ?>
            <a href="profile.php" class="text-blue-600 hover:underline mb-6">Go to my profile</a>
        <?php endif; ?>
    </div>

    <div class="mt-8">
        <h3 class="text-2xl font-semibold mb-4"><?= htmlspecialchars($user['username']) ?>'s Public Quizzes</h3>

        <?php if ($quizzes->num_rows === 0): ?>
            <p class="text-gray-500">No public quizzes available.</p>
        <?php else: ?>
            <div class="space-y-4">
                <?php while ($quiz = $quizzes->fetch_assoc()): ?>
                    <div class="p-5 border border-gray-200 rounded-lg hover:shadow-md transition">
                        <h4 class="text-xl font-semibold mb-1"><?= htmlspecialchars($quiz['title']) ?></h4>
                        <p class="text-gray-500 text-sm mb-2">Created on <?= date("F j, Y", strtotime($quiz['created_at'])) ?></p>
                        <p class="text-gray-700 mb-3"><?= nl2br(htmlspecialchars($quiz['description'])) ?></p>
                        <a href="play_quiz.php?quiz_id=<?= $quiz['id'] ?>" class="px-4 py-2 bg-green-500 text-white rounded-lg shadow hover:bg-green-600 transition">
                            Play Quiz
                        </a>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
