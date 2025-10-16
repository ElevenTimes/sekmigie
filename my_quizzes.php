<?php
session_start();
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8fafc;
            font-family: 'Segoe UI', sans-serif;
        }
        .container {
            max-width: 900px;
            margin-top: 50px;
        }
        .quiz-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.08);
            transition: transform 0.2s ease;
        }
        .quiz-card:hover {
            transform: translateY(-4px);
        }
        .quiz-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .badge {
            font-size: 0.85rem;
        }
        .actions a {
            text-decoration: none;
            margin-right: 10px;
        }
        .actions .btn {
            border-radius: 30px;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-semibold">My Quizzes</h2>
        <a href="create_quiz.php" class="btn btn-primary btn-sm rounded-pill">
            + Create New Quiz
        </a>
    </div>

    <?php if ($result->num_rows === 0): ?>
        <div class="alert alert-info text-center py-4">
            You haven't created any quizzes yet.<br>
            <a href="create_quiz.php" class="btn btn-outline-primary mt-3">Create Your First Quiz</a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php while ($quiz = $result->fetch_assoc()): ?>
                <div class="col-12">
                    <div class="card quiz-card p-4">
                        <div class="quiz-header mb-2">
                            <h5 class="fw-bold mb-0"><?= htmlspecialchars($quiz['title']) ?></h5>
                            <span class="badge <?= $quiz['is_public'] ? 'bg-success' : 'bg-secondary' ?>">
                                <?= $quiz['is_public'] ? 'Public' : 'Private' ?>
                            </span>
                        </div>
                        <p class="text-muted mb-2 small">Created on <?= date("F j, Y", strtotime($quiz['created_at'])) ?></p>
                        <p><?= nl2br(htmlspecialchars($quiz['description'])) ?></p>
                        <div class="actions mt-3">
                            <a href="edit_quiz.php?id=<?= $quiz['id'] ?>" class="btn btn-outline-primary btn-sm">Edit</a>
                            <a href="delete_quiz.php?id=<?= $quiz['id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure you want to delete this quiz?')">Delete</a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
