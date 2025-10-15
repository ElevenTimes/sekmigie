<?php
session_start();
include 'navbar.php';
require 'config.php';

// Only-logged in users
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$success_msg = '';
$error_msg = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $is_public = isset($_POST['is_public']) ? 1 : 0;
    $creator_id = $_SESSION['user_id'];

    if (empty($title)) {
        $error_msg = "Quiz title cannot be empty.";
    } else {
        // Insert quiz
        $stmt = $conn->prepare("INSERT INTO quiz (title, description, creator_id, is_public) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssii", $title, $description, $creator_id, $is_public);
        if (!$stmt->execute()) die("Error creating quiz: " . $stmt->error);
        $quiz_id = $stmt->insert_id;

        // Insert questions
        if (!empty($_POST['questions']) && is_array($_POST['questions'])) {
            foreach ($_POST['questions'] as $q) {
                $q_title = trim($q['title'] ?? '');
                $q_text  = trim($q['text'] ?? '');
                $q_type  = $q['type'] ?? 'multiple_choice';

                $stmt2 = $conn->prepare("INSERT INTO question (quiz_id, title, text, type) VALUES (?, ?, ?, ?)");
                $stmt2->bind_param("isss", $quiz_id, $q_title, $q_text, $q_type);
                if (!$stmt2->execute()) die("Error adding question: " . $stmt2->error);
                $question_id = $stmt2->insert_id;

                // Multiple choice (possibly multiple correct answers)
                if ($q_type === 'multiple_choice' && !empty($q['options']) && is_array($q['options'])) {
                    foreach ($q['options'] as $opt) {
                        $opt_text = trim($opt['text'] ?? '');
                        if ($opt_text === '') continue;
                        $is_correct = isset($opt['is_correct']) ? 1 : 0;
                        $stmt3 = $conn->prepare("INSERT INTO answer_option (question_id, text, is_correct) VALUES (?, ?, ?)");
                        $stmt3->bind_param("isi", $question_id, $opt_text, $is_correct);
                        $stmt3->execute();
                    }
                }

                // True/False — store two options 'True' and 'False' marking correct based on selected radio
                elseif ($q_type === 'true_false') {
                    $true_correct = isset($q['correct_tf']) && $q['correct_tf'] === 'true' ? 1 : 0;
                    $false_correct = $true_correct ? 0 : 1;
                    $stmt3 = $conn->prepare("INSERT INTO answer_option (question_id, text, is_correct) VALUES (?, ?, ?)");
                    $tf_values = [
                        ['text' => 'True', 'is_correct' => $true_correct],
                        ['text' => 'False', 'is_correct' => $false_correct],
                    ];
                    foreach ($tf_values as $val) {
                        $stmt3->bind_param("isi", $question_id, $val['text'], $val['is_correct']);
                        $stmt3->execute();
                    }
                }

                // Text input — store the expected text as an answer_option (is_correct = 1)
                elseif ($q_type === 'text_input') {
                    $correct_text = trim($q['correct_text'] ?? '');
                    if ($correct_text !== '') {
                        $stmt3 = $conn->prepare("INSERT INTO answer_option (question_id, text, is_correct) VALUES (?, ?, 1)");
                        $stmt3->bind_param("is", $question_id, $correct_text);
                        $stmt3->execute();
                    }
                }
            }
        }

        $success_msg = "Quiz created successfully!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Create Quiz</title>
<script src="https://cdn.tailwindcss.com"></script>

<style>
body {
    font-family: "Segoe UI", Roboto, sans-serif;
    background: #f4f6fa;
    color: #333;
    margin: 0;
    padding: 0;
}

.container {
    max-width: 900px;
    margin: 50px auto;
    background: #fff;
    padding: 30px 40px;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

h2 {
    text-align: center;
    margin-bottom: 25px;
    color: #1e1e2f;
}

form label {
    font-weight: 600;
}

input[type="text"],
textarea,
select {
    width: 100%;
    padding: 10px 12px;
    margin-top: 6px;
    margin-bottom: 15px;
    border: 1px solid #ccc;
    border-radius: 8px;
    transition: border-color 0.2s, box-shadow 0.2s;
}

input[type="text"]:focus,
textarea:focus,
select:focus {
    border-color: #4a90e2;
    box-shadow: 0 0 5px rgba(74, 144, 226, 0.4);
    outline: none;
}

button {
    background-color: #4a90e2;
    color: white;
    border: none;
    padding: 10px 16px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: background-color 0.2s, transform 0.1s;
}

button:hover {
    background-color: #357ABD;
}

button:active {
    transform: scale(0.98);
}

#questionsContainer {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.question {
    background: #fafafa;
    border: 1px solid #ddd;
    border-radius: 12px;
    padding: 20px;
    position: relative;
    box-shadow: 0 3px 8px rgba(0,0,0,0.05);
    transition: box-shadow 0.2s ease-in-out, transform 0.15s;
}

.question:hover {
    box-shadow: 0 6px 14px rgba(0,0,0,0.08);
}

.question-number {
    font-size: 1.2em;
    font-weight: 700;
    margin-bottom: 10px;
    color: #4a90e2;
}

.question .drag-handle {
    position: absolute;
    top: 15px;
    right: 15px;
    cursor: grab;
    color: #999;
}

.question .drag-handle:hover {
    color: #4a90e2;
}

.options, .truefalse, .textinput {
    background: #fff;
    border-radius: 8px;
    padding: 10px 14px;
    border: 1px solid #e0e0e0;
    margin-top: 10px;
}

hr {
    border: none;
    border-top: 1px solid #eee;
    margin: 20px 0;
}

.remove-btn {
    background-color: #e74c3c;
    margin-top: 10px;
}

.remove-btn:hover {
    background-color: #c0392b;
}

.actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 25px;
}
</style>

</head>
<body class="bg-gray-100 text-gray-800">

<div class="max-w-5xl mx-auto mt-10 bg-white p-8 rounded-lg shadow-md">
    <div class="container">
    <h2>Create a New Quiz</h2>

    <?php if ($success_msg): ?>
        <div class="bg-green-100 text-green-700 p-3 rounded mb-4"><?= htmlspecialchars($success_msg) ?></div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="bg-red-100 text-red-700 p-3 rounded mb-4"><?= htmlspecialchars($error_msg) ?></div>
    <?php endif; ?>

    <form method="post" id="quizForm">
        <div>
            <label class="block font-semibold mb-1">Quiz Title</label>
            <input name="title" required class="w-full border border-gray-300 p-2 rounded" />
        </div>

        <div>
            <label class="block font-semibold mb-1">Description</label>
            <textarea name="description" rows="3" class="w-full border border-gray-300 p-2 rounded"></textarea>
        </div>

        <div class="flex items-center">
            <input id="is_public" type="checkbox" name="is_public" checked class="mr-2">
            <label for="is_public" class="font-semibold">Public</label>
        </div>

        <h2 class="text-xl font-bold mt-6">Questions</h2>
        <div id="questionsContainer" class="space-y-4"></div>

        <div class="actions">
            <button type="button" onclick="addQuestion()">Add Question</button>
            <button type="submit">Create Quiz</button>
        </div>
    </form>
</div>

<script>
let questionIndex = 0;

function addQuestion() {
    const container = document.getElementById('questionsContainer');
    const div = document.createElement('div');
    div.classList.add('question');
    div.setAttribute('draggable', 'true');
    div.dataset.index = questionIndex;

    div.innerHTML = `
        <h3 class="question-number">Question ${questionIndex + 1}</h3>°
        <span class="drag-handle" title="Drag to reorder">☰</span>
        <label>Question Title:</label><br>
        <input type="text" name="questions[${questionIndex}][title]" required><br>
        <label>Question Text (optional):</label><br>
        <input type="text" name="questions[${questionIndex}][text]"><br>
        <label>Type:</label>
        <select name="questions[${questionIndex}][type]" onchange="toggleOptions(this, ${questionIndex})">
            <option value="multiple_choice">Multiple Choice</option>
            <option value="true_false">True/False</option>
            <option value="text_input">Text Input</option>
        </select>

        <!-- multiple choice -->
        <div class="options" id="options-${questionIndex}">
            <h4>Options</h4>
            <div>
                <input type="text" name="questions[${questionIndex}][options][0][text]" placeholder="Option 1">
                <label><input type="checkbox" name="questions[${questionIndex}][options][0][is_correct]"> Correct</label>
            </div>
            <div>
                <input type="text" name="questions[${questionIndex}][options][1][text]" placeholder="Option 2">
                <label><input type="checkbox" name="questions[${questionIndex}][options][1][is_correct]"> Correct</label>
            </div>
            <button type="button" onclick="addOption(${questionIndex})">Add Option</button>
        </div>

        <!-- true/false -->
        <div class="truefalse" id="truefalse-${questionIndex}" style="display:none;">
            <h4>True / False</h4>
            <label><input type="radio" name="questions[${questionIndex}][correct_tf]" value="true"> True</label>
            <label><input type="radio" name="questions[${questionIndex}][correct_tf]" value="false"> False</label>
        </div>

        <!-- text input -->
        <div class="textinput" id="textinput-${questionIndex}" style="display:none;">
            <h4>Correct Answer</h4>
            <input type="text" name="questions[${questionIndex}][text_correct]" placeholder="Enter correct answer">
        </div>

        <input type="hidden" name="questions[${questionIndex}][order_index]" value="${questionIndex}">

        <button type="button" onclick="removeQuestion(this)">Remove Question</button>
        <hr>
    `;

    // enable drag events
    div.addEventListener('dragstart', handleDragStart);
    div.addEventListener('dragover', handleDragOver);
    div.addEventListener('drop', handleDrop);

    container.appendChild(div);
    questionIndex++;
    updateQuestionNumbers();
}

function toggleOptions(select, index) {
    const optionsDiv = document.getElementById(`options-${index}`);
    const tfDiv = document.getElementById(`truefalse-${index}`);
    const textDiv = document.getElementById(`textinput-${index}`);

    optionsDiv.style.display = 'none';
    tfDiv.style.display = 'none';
    textDiv.style.display = 'none';

    if (select.value === 'multiple_choice') {
        optionsDiv.style.display = 'block';
    } else if (select.value === 'true_false') {
        tfDiv.style.display = 'block';
    } else if (select.value === 'text_input') {
        textDiv.style.display = 'block';
    }
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

function removeQuestion(button) {
    button.closest('.question').remove();
    reindexQuestions();
    updateQuestionNumbers();
}

function reindexQuestions() {
    const questions = document.querySelectorAll('.question');
    questionIndex = 0;
    questions.forEach(q => {
        q.dataset.index = questionIndex;
        q.querySelectorAll('input, select, textarea').forEach(el => {
            if (el.name) el.name = el.name.replace(/questions\[\d+\]/, `questions[${questionIndex}]`);
        });
        q.querySelectorAll('[id]').forEach(el => {
            el.id = el.id.replace(/\d+$/, questionIndex);
        });
        q.querySelector('input[name*="[order_index]"]').value = questionIndex;
        questionIndex++;
    });
}

function updateQuestionNumbers() {
    document.querySelectorAll('.question-number').forEach((h, i) => {
        h.textContent = `Question ${i + 1}`;
    });
}

/* --- DRAG & DROP HANDLERS --- */
let draggedItem = null;

function handleDragStart(e) {
    draggedItem = this;
    this.style.opacity = '0.5';
}

function handleDragOver(e) {
    e.preventDefault();
    const container = document.getElementById('questionsContainer');
    const target = e.target.closest('.question');
    if (target && target !== draggedItem) {
        const rect = target.getBoundingClientRect();
        const next = (e.clientY - rect.top) / rect.height > 0.5;
        container.insertBefore(draggedItem, next ? target.nextSibling : target);
    }
}

function handleDrop(e) {
    e.preventDefault();
    draggedItem.style.opacity = '1';
    draggedItem = null;
    reindexQuestions();
    updateQuestionNumbers();
}
</script>



</body>
</html>





