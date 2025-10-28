<?php
session_start();
// Note: Assuming 'navbar.php' and 'config.php' are available
require 'config.php';
include 'navbar.php'; // ADDED NAVBAR

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = $_SESSION['message'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['message'], $_SESSION['error']); // Clear messages

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Quizzes</title>
    <!-- Load Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Custom font and base styles */
        body {
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif;
        }
        /* Retaining custom class for the card hover effect, adjusted for new border color */
        .quiz-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .quiz-card:hover {
            /* Lift card and increase shadow slightly on hover */
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">

<!-- Centered, flexible container matching index.php -->
<div class="flex flex-col items-center justify-center mt-12 px-4">
    <div class="w-full max-w-4xl">
        
        <!-- Header with Create Button -->
        <div class="flex justify-between items-center mb-8 border-b pb-4">
            <h2 class="text-3xl font-bold text-gray-900">My Quizzes</h2>
            <!-- Button style updated to match index.php's primary blue theme -->
            <a href="create_quiz.php" class="bg-blue-600 text-white px-5 py-2 text-sm font-semibold rounded-full shadow-lg hover:bg-blue-700 transition duration-300 transform hover:scale-105">
                + Create New Quiz
            </a>
        </div>

        <!-- Alert Messages (Converted to Tailwind) -->
        <?php if ($message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-xl relative mb-4" role="alert">
                <span class="block sm:inline"><?= htmlspecialchars($message) ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl relative mb-4" role="alert">
                <span class="block sm:inline"><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($result->num_rows === 0): ?>
            <!-- Alert Info Conversion -->
            <div class="bg-blue-50 border-l-4 border-blue-400 text-blue-800 p-5 rounded-lg text-center shadow-md">
                <p class="mb-3 text-lg">You haven't created any quizzes yet.</p>
                <a href="create_quiz.php" class="inline-block bg-blue-100 text-blue-700 border border-blue-400 px-4 py-2 rounded-full font-semibold hover:bg-blue-200 transition duration-150">Create Your First Quiz</a>
            </div>
        <?php else: ?>
            <!-- Quiz List Container (using space-y-4) -->
            <div class="space-y-4">
                <?php while ($quiz = $result->fetch_assoc()): ?>
                    <div class="w-full">
                        <!-- Quiz Card Style matching index.php list item -->
                        <div class="bg-white quiz-card p-5 rounded-xl shadow-lg border border-gray-200 hover:shadow-xl hover:border-blue-600 transition duration-300 flex justify-between items-start">
                            
                            <!-- Left Content: Title, Status, Date, Description -->
                            <div class="flex flex-col text-left pr-4">
                                <div class="flex items-center mb-2">
                                    <!-- Title -->
                                    <h5 class="text-xl font-extrabold text-gray-900 leading-snug mr-3"><?= htmlspecialchars($quiz['title']) ?></h5>
                                    <!-- Badge -->
                                    <span class="inline-block px-3 py-1 text-xs font-bold rounded-full
                                        <?= $quiz['is_public'] ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-800' ?>">
                                        <?= $quiz['is_public'] ? 'Public' : 'Private' ?>
                                    </span>
                                </div>
                                <!-- Date -->
                                <p class="text-gray-500 mb-2 text-sm">Created on <?= date("F j, Y", strtotime($quiz['created_at'])) ?></p>
                                <!-- Description -->
                                <p class="text-gray-700 mb-0 mt-2 text-sm"><?= nl2br(htmlspecialchars($quiz['description'])) ?></p>
                            </div>
                            
                            <!-- Right Content: Actions -->
                            <div class="flex flex-col space-y-2 mt-1 shrink-0">
                                <!-- Edit Button (Style changed) -->
                                <a href="edit_quiz.php?id=<?= $quiz['id'] ?>" class="text-sm font-semibold rounded-lg text-center px-4 py-2 border border-blue-500 text-blue-600 hover:bg-blue-50 transition duration-150 shadow-sm">
                                    Edit
                                </a>
                                <!-- Delete Button (Style changed) -->
                                <a href="delete_quiz.php?id=<?= $quiz['id'] ?>" class="text-sm font-semibold rounded-lg text-center px-4 py-2 bg-red-600 text-white hover:bg-red-700 transition duration-150 shadow-md" onclick="return confirm('Are you sure you want to delete this quiz?')">
                                    Delete
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>