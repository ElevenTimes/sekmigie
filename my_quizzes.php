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

<h2>My Quizzes</h2>
<?php if ($result->num_rows === 0): ?>
    <p>You haven't created any quizzes yet.</p>
<?php else: ?>
    <ul>
        <?php while ($quiz = $result->fetch_assoc()): ?>
            <li>
                <strong><?= htmlspecialchars($quiz['title']) ?></strong>
                (<?= $quiz['is_public'] ? 'Public' : 'Private' ?>) - <?= $quiz['created_at'] ?><br>
                <?= htmlspecialchars($quiz['description']) ?><br>
                <a href="edit_quiz.php?id=<?= $quiz['id'] ?>">Edit</a> | 
                <a href="delete_quiz.php?id=<?= $quiz['id'] ?>" onclick="return confirm('Are you sure?')">Delete</a>
            </li>
            <hr>
        <?php endwhile; ?>
    </ul>
<?php endif; ?>
