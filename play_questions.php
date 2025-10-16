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
    $total_questions = count($questions);
    $percentage_score = $total_questions > 0 ? round(($score / $total_questions) * 100) : 0;

    // Update quiz_attempt
    $stmt = $conn->prepare("UPDATE quiz_attempt SET score=?, finished_at=NOW() WHERE id=?");
    $stmt->bind_param("ii", $score, $attempt_id);
    $stmt->execute();

    // --- START OF NEW/IMPROVED DESIGN ---
    echo <<<HTML
    <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Finished!</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Ensures the progress ring border is handled correctly */
        .relative {
            box-sizing: border-box; 
        }
    </style>
    </head>
    <body class="bg-gray-100 text-gray-800">
    
    <div class="max-w-xl mx-auto mt-16 bg-white p-8 md:p-10 rounded-xl shadow-2xl text-center">
        <div class="mb-6">
            <svg class="w-16 h-16 mx-auto text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        <h2 class="text-3xl font-extrabold text-gray-900 mb-2">🎉 Quiz Finished!</h2>
        <p class="text-lg text-gray-600 mb-8">You have successfully completed the **{$attempt['quiz_title']}** quiz.</p>

        <div class="flex justify-center items-center mb-10">
        <div 
            class="relative w-32 h-32 rounded-full flex items-center justify-center text-4xl font-bold text-blue-600 border-8 border-gray-200" 
            style="background: conic-gradient(rgb(0 0 0) {$percentage_score}%, rgb(0 0 0) 0%);"
        >
            {$percentage_score}<span class="text-xl">%</span>
        </div>
    </div>
        
        <p class="text-2xl font-semibold text-gray-800 mb-6">
            Your Score: <span class="text-blue-600">{$score}</span> out of {$total_questions}
        </p>

        <a href="index.php" class="w-full sm:w-auto inline-block px-8 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition duration-300 transform hover:scale-105 shadow-lg">
            Go Back to Home
        </a>
    </div>

    </body>
    </html>
HTML;
    // --- END OF NEW/IMPROVED DESIGN ---
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
                <div class="bg-gray-50 p-3 rounded-lg border border-gray-200 hover:bg-blue-50 transition duration-150">
                    <label class="inline-flex items-center w-full cursor-pointer">
                        <input type="radio" name="answer_option_id" value="<?= $opt['id'] ?>" class="mr-3 text-blue-600 focus:ring-blue-500" required>
                        <span class="text-base"><?= htmlspecialchars($opt['text']) ?></span>
                    </label>
                </div>
            <?php endforeach; ?>
        <?php elseif ($question['type'] === 'text_input'): ?>
            <input type="text" name="text_answer" class="w-full border p-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter your answer here..." required>
        <?php endif; ?>

        <button type="submit" class="w-full px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition transform hover:scale-[1.01]">
            Submit Answer
        </button>
    </form>
</div>

</body>
</html>