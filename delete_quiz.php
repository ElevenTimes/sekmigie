<?php
session_start();
require 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect unauthenticated users
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$quiz_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($quiz_id <= 0) {
    // Redirect if no valid quiz ID is provided
    header("Location: my_quizzes.php");
    exit();
}

// Start transaction for atomic deletion
$conn->begin_transaction();
$success = true;

try {
    // 1. Verify ownership of the quiz before proceeding with any deletion
    $stmt = $conn->prepare("SELECT creator_id FROM quiz WHERE id = ?");
    $stmt->bind_param("i", $quiz_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $quiz = $result->fetch_assoc();
    $stmt->close();

    if (!$quiz || $quiz['creator_id'] != $user_id) {
        // Quiz not found or user is not the creator
        $success = false;
        throw new Exception("Unauthorized deletion attempt or quiz not found.");
    }

    // --- Start Deletion Steps (Order is crucial for foreign keys) ---
    
    // NOTE: Based on your table structure (db_init.php):
    // - quiz_attempt links to quiz (ON DELETE CASCADE)
    // - user_answer links to quiz_attempt (ON DELETE CASCADE)
    // - answer_option links to question (ON DELETE CASCADE)
    // - question links to quiz (ON DELETE CASCADE)
    // Due to the cascading deletions defined in your database structure, 
    // simply deleting the 'quiz' entry should automatically handle most dependencies.
    // However, explicit deletion ensures proper handling and clearer error tracking.

    // 2. Delete all user answers associated with attempts on this quiz 
    //    (Linked via quiz_attempt.id)
    $stmt = $conn->prepare("DELETE FROM user_answer WHERE attempt_id IN (SELECT id FROM quiz_attempt WHERE quiz_id = ?)");
    $stmt->bind_param("i", $quiz_id);
    if (!$stmt->execute()) { $success = false; throw new Exception("Failed to delete user answers."); }
    $stmt->close();
    
    // 3. Delete all quiz attempts associated with the quiz (from 'quiz_attempt' table)
    //    (Was incorrectly targeting 'quiz_result' before)
    $stmt = $conn->prepare("DELETE FROM quiz_attempt WHERE quiz_id = ?");
    $stmt->bind_param("i", $quiz_id);
    if (!$stmt->execute()) { $success = false; throw new Exception("Failed to delete quiz attempts."); }
    $stmt->close();

    // 4. Delete all answer options associated with the quiz's questions (from 'answer_option' table)
    //    (Was incorrectly targeting 'option' before)
    $stmt = $conn->prepare("DELETE FROM answer_option WHERE question_id IN (SELECT id FROM question WHERE quiz_id = ?)");
    $stmt->bind_param("i", $quiz_id);
    if (!$stmt->execute()) { $success = false; throw new Exception("Failed to delete options."); }
    $stmt->close();

    // 5. Delete all questions associated with the quiz (from 'question' table)
    $stmt = $conn->prepare("DELETE FROM question WHERE quiz_id = ?");
    $stmt->bind_param("i", $quiz_id);
    if (!$stmt->execute()) { $success = false; throw new Exception("Failed to delete questions."); }
    $stmt->close();

    // 6. Delete the quiz itself (from 'quiz' table)
    $stmt = $conn->prepare("DELETE FROM quiz WHERE id = ? AND creator_id = ?");
    $stmt->bind_param("ii", $quiz_id, $user_id);
    if (!$stmt->execute()) { $success = false; throw new Exception("Failed to delete quiz."); }
    $stmt->close();

    // If all steps succeeded, commit the transaction
    $conn->commit();
    $_SESSION['message'] = "Quiz deleted successfully!";

} catch (Exception $e) {
    // If any step failed, rollback the transaction
    $conn->rollback();
    error_log("Quiz Deletion Error: " . $e->getMessage());
    $_SESSION['error'] = "Failed to delete the quiz. Please try again. Error: " . $e->getMessage();
}

// Redirect back to the quizzes list
header("Location: my_quizzes.php");
exit();
?>