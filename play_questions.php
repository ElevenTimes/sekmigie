<?php
session_start();
include 'navbar.php';
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$attempt_id = $_GET['attempt_id'] ?? null;

// Fetch attempt info
$stmt = $conn->prepare("
    SELECT qa.id, qa.quiz_id, q.title AS quiz_title
    FROM quiz_attempt qa
    JOIN quiz q ON qa.quiz_id = q.id
    WHERE qa.id=? AND qa.user_id=?
");
$stmt->bind_param("ii", $attempt_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$attempt = $result->fetch_assoc();

if (!$attempt) die("Quiz attempt not found.");

// Fetch questions for this quiz
$stmt = $conn->prepare("SELECT * FROM question WHERE quiz_id=? ORDER BY order_index ASC, id ASC");
$stmt->bind_param("i", $attempt['quiz_id']);
$stmt->execute();
$questions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Track current question
$current_index = $_GET['q'] ?? 0;
$current_index = (int)$current_index;

if ($current_index >= count($questions)) {
    // Finished quiz
    // Calculate score
    $stmt = $conn->prepare("
        SELECT COUNT(*) as correct_count
        FROM user_answer ua
        JOIN answer_option ao ON ua.answer_option_id = ao.id
        WHERE ua.attempt_id=? AND ua.is_correct=1
    ");
    $stmt->bind_param("i", $attempt_id);
    $stmt->execute();
    $score = $stmt->get_result()->fetch_assoc()['correct_count'];

    // Update quiz_attempt
    $stmt = $conn->prepare("UPDATE quiz_attempt SET score=?, finished_at=NOW() WHERE id=?");
    $stmt->bind_param("ii", $score, $attempt_id);
    $stmt->execute();

    echo "<div class='max-w-3xl mx-auto mt-12 bg-white p-8 rounded shadow-md'>
            <h2 class='text-2xl font-bold mb-4'>Quiz Finished!</h2>
            <p>Your score: $score / ".count($questions)."</p>
            <a href='index.php' class='mt-4 inline-block px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition'>Back to Home</a>
          </div>";
    exit();
}

// Handle submitted answer
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $question_id = $questions[$current_index]['id'];
    $answer_id = $_POST['answer_option_id'] ?? null;
    $text_answer = $_POST['text_answer'] ?? null;

    $is_correct = 0;
    if ($answer_id) {
        // Check if selected option is correct
        $stmt = $conn->prepare("SELECT is_correct FROM answer_option WHERE id=?");
        $stmt->bind_param("i", $answer_id);
        $stmt->execute();
        $is_correct = $stmt->get_result()->fetch_assoc()['is_correct'] ? 1 : 0;
    } elseif ($text_answer !== null) {
        // For text_input, you could add automated checking if needed
        $is_correct = 0;
    }

    // Insert user_answer
    $stmt = $conn->prepare("
        INSERT INTO user_answer (attempt_id, question_id, answer_option_id, text_answer, is_correct)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iiisi", $attempt_id, $question_id, $answer_id, $text_answer, $is_correct);
    $stmt->execute();

    // Go to next question
    header("Location: play_questions.php?attempt_id=$attempt_id&q=" . ($current_index + 1));
    exit();
}

// Render current question
$question = $questions[$current_index];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Quiz: <?= htmlspecialchars($attempt['quiz_title']) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800">

<div class="max-w-3xl mx-auto mt-12 bg-white p-8 rounded-lg shadow-md">
    <h2 class="text-2xl font-bold mb-4"><?= htmlspecialchars($attempt['quiz_title']) ?></h2>
    <p class="mb-4 font-semibold">Question <?= $current_index + 1 ?> of <?= count($questions) ?></p>
    <p class="mb-6"><?= htmlspecialchars($question['text']) ?></p>

    <form method="post" class="space-y-4">
        <?php if ($question['type'] === 'multiple_choice' || $question['type'] === 'true_false'): ?>
            <?php
            $stmt = $conn->prepare("SELECT * FROM answer_option WHERE question_id=? ORDER BY order_index ASC");
            $stmt->bind_param("i", $question['id']);
            $stmt->execute();
            $options = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            foreach ($options as $opt): ?>
                <div>
                    <label class="inline-flex items-center">
                        <input type="radio" name="answer_option_id" value="<?= $opt['id'] ?>" class="mr-2" required>
                        <?= htmlspecialchars($opt['text']) ?>
                    </label>
                </div>
            <?php endforeach; ?>
        <?php elseif ($question['type'] === 'text_input'): ?>
            <input type="text" name="text_answer" class="w-full border p-2 rounded focus:outline-none focus:ring-2 focus:ring-blue-500" required>
        <?php endif; ?>

        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
            Submit
        </button>
    </form>
</div>

</body>
</html>
