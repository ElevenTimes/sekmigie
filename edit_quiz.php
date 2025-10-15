<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get quiz ID from URL
if (!isset($_GET['id'])) {
    die("Quiz ID not provided");
}
$quiz_id = intval($_GET['id']);

// Fetch quiz
$stmt = $conn->prepare("SELECT * FROM quiz WHERE id=? AND creator_id=?");
$stmt->bind_param("ii", $quiz_id, $user_id);
$stmt->execute();
$quiz = $stmt->get_result()->fetch_assoc();
if (!$quiz) die("Quiz not found or you don't have permission.");

// Fetch questions and options
$stmt = $conn->prepare("SELECT * FROM question WHERE quiz_id=? ORDER BY id ASC");
$stmt->bind_param("i", $quiz_id);
$stmt->execute();
$questions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

foreach ($questions as &$q) {
    if ($q['type'] === 'multiple_choice' || $q['type'] === 'true_false') {
        $stmt2 = $conn->prepare("SELECT * FROM answer_option WHERE question_id=?");
        $stmt2->bind_param("i", $q['id']);
        $stmt2->execute();
        $q['options'] = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update quiz
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $is_public = isset($_POST['is_public']) ? 1 : 0;

    $stmt = $conn->prepare("UPDATE quiz SET title=?, description=?, is_public=? WHERE id=? AND creator_id=?");
    $stmt->bind_param("ssiii", $title, $description, $is_public, $quiz_id, $user_id);
    $stmt->execute();

    // Update questions
    if (!empty($_POST['questions'])) {
        foreach ($_POST['questions'] as $q_index => $q_data) {
            $q_id = isset($q_data['id']) ? intval($q_data['id']) : 0;
            $q_title = trim($q_data['title']);
            $q_text = trim($q_data['text']);
            $q_type = $q_data['type'];

            if ($q_id > 0) {
                // Update existing question
                $stmt = $conn->prepare("UPDATE question SET title=?, text=?, type=? WHERE id=? AND quiz_id=?");
                $stmt->bind_param("sssii", $q_title, $q_text, $q_type, $q_id, $quiz_id);
                $stmt->execute();
            } else {
                // Insert new question
                $stmt = $conn->prepare("INSERT INTO question (quiz_id, title, text, type) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("isss", $quiz_id, $q_title, $q_text, $q_type);
                $stmt->execute();
                $q_id = $stmt->insert_id;
            }

            // Handle multiple choice options
            if (($q_type === 'multiple_choice' || $q_type === 'true_false') && !empty($q_data['options'])) {
                foreach ($q_data['options'] as $opt) {
                    $opt_id = isset($opt['id']) ? intval($opt['id']) : 0;
                    $opt_text = trim($opt['text']);
                    $is_correct = isset($opt['is_correct']) ? 1 : 0;

                    if ($opt_id > 0) {
                        $stmt = $conn->prepare("UPDATE answer_option SET text=?, is_correct=? WHERE id=? AND question_id=?");
                        $stmt->bind_param("siii", $opt_text, $is_correct, $opt_id, $q_id);
                        $stmt->execute();
                    } else {
                        $stmt = $conn->prepare("INSERT INTO answer_option (question_id, text, is_correct) VALUES (?, ?, ?)");
                        $stmt->bind_param("isi", $q_id, $opt_text, $is_correct);
                        $stmt->execute();
                    }
                }
            }
        }
    }

    echo "<p>Quiz updated successfully! <a href='my_quizzes.php'>Go back to My Quizzes</a></p>";
    exit();
}
?>

<h2>Edit Quiz</h2>

<form method="post" id="quizForm">
    <label>Quiz Title:</label><br>
    <input type="text" name="title" value="<?= htmlspecialchars($quiz['title']) ?>" required><br><br>

    <label>Description:</label><br>
    <textarea name="description"><?= htmlspecialchars($quiz['description']) ?></textarea><br><br>

    <label><input type="checkbox" name="is_public" <?= $quiz['is_public'] ? 'checked' : '' ?>> Public</label><br><br>

    <h3>Questions</h3>
    <div id="questionsContainer"></div>

    <button type="button" onclick="addQuestion()">Add Question</button><br><br>
    <button type="submit">Update Quiz</button>
</form>

<script>
let questionIndex = 0;
let existingQuestions = <?= json_encode($questions) ?>;

function addQuestion(qData=null) {
    const container = document.getElementById('questionsContainer');
    const div = document.createElement('div');
    div.classList.add('question');

    const title = qData ? qData.title : '';
    const text = qData ? qData.text : '';
    const type = qData ? qData.type : 'multiple_choice';
    const qId = qData ? qData.id : 0;
    let optionsHTML = '';

    if (qData && qData.options) {
        qData.options.forEach((opt, i) => {
            optionsHTML += `
            <div>
                <input type="text" name="questions[${questionIndex}][options][${i}][text]" value="${opt.text}" placeholder="Option ${i+1}">
                <label><input type="checkbox" name="questions[${questionIndex}][options][${i}][is_correct]" ${opt.is_correct ? 'checked' : ''}> Correct</label>
                <input type="hidden" name="questions[${questionIndex}][options][${i}][id]" value="${opt.id}">
            </div>
            `;
        });
    } else {
        // Default 2 empty options for new multiple_choice
        optionsHTML = `
            <div>
                <input type="text" name="questions[${questionIndex}][options][0][text]" placeholder="Option 1">
                <label><input type="checkbox" name="questions[${questionIndex}][options][0][is_correct]"> Correct</label>
            </div>
            <div>
                <input type="text" name="questions[${questionIndex}][options][1][text]" placeholder="Option 2">
                <label><input type="checkbox" name="questions[${questionIndex}][options][1][is_correct]"> Correct</label>
            </div>
        `;
    }

    div.innerHTML = `
        <input type="hidden" name="questions[${questionIndex}][id]" value="${qId}">
        <label>Question Title (optional):</label><br>
        <input type="text" name="questions[${questionIndex}][title]" value="${title}"><br>
        <label>Question Text:</label><br>
        <input type="text" name="questions[${questionIndex}][text]" value="${text}" required><br>
        <label>Type:</label>
        <select name="questions[${questionIndex}][type]" onchange="toggleOptions(this, ${questionIndex})">
            <option value="multiple_choice" ${type==='multiple_choice'?'selected':''}>Multiple Choice</option>
            <option value="true_false" ${type==='true_false'?'selected':''}>True/False</option>
            <option value="text_input" ${type==='text_input'?'selected':''}>Text Input</option>
        </select>
        <div class="options" id="options-${questionIndex}" ${type!=='multiple_choice'?'style="display:none;"':''}>
            <h4>Options</h4>
            ${optionsHTML}
            <button type="button" onclick="addOption(${questionIndex})">Add Option</button>
        </div>
        <hr>
    `;

    container.appendChild(div);
    questionIndex++;
}

// Load existing questions on page load
existingQuestions.forEach(q => addQuestion(q));

function toggleOptions(select, index) {
    const optionsDiv = document.getElementById(`options-${index}`);
    if (select.value === 'multiple_choice') optionsDiv.style.display = 'block';
    else optionsDiv.style.display = 'none';
}

function addOption(qIndex) {
    const optionsDiv = document.getElementById(`options-${qIndex}`);
    const optionCount = optionsDiv.querySelectorAll('input[type=text]').length;
    const div = document.createElement('div');
    div.innerHTML = `
        <input type="text" name="questions[${qIndex}][options][${optionCount}][text]" placeholder="Option ${optionCount+1}">
        <label><input type="checkbox" name="questions[${qIndex}][options][${optionCount}][is_correct]"> Correct</label>
    `;
    optionsDiv.insertBefore(div, optionsDiv.querySelector('button'));
}
</script>
