<?php
session_start();
require 'config.php';
include 'validators.php';

$success_msg = '';
$error_msg = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // -------- Validations --------
    $v = validate_username($username);
    if ($v !== true) { $error_msg = $v; }
    $v = validate_email($email);
    if ($v !== true) { $error_msg = $v; }
    $v = validate_password($password);
    if ($v !== true) { $error_msg = $v; }

    // If no errors, insert user
    if (!$error_msg) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO user (username, email, password_hash) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $email, $password_hash);

        if ($stmt->execute()) {
            $user_id = $stmt->insert_id;
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            header("Location: index.php");
            exit();
        } else {
            $error_msg = "Failed to register. Username or email may already exist.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800">

<div class="max-w-md mx-auto mt-20 bg-white p-8 rounded-lg shadow-md">
    <h2 class="text-3xl font-bold mb-6 text-center">Register</h2>

    <?php if ($success_msg): ?>
        <div class="bg-green-100 text-green-700 p-3 rounded mb-4"><?= $success_msg ?></div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="bg-red-100 text-red-700 p-3 rounded mb-4"><?= $error_msg ?></div>
    <?php endif; ?>

    <form method="post" class="space-y-4">
        <div>
            <label class="block font-semibold mb-1">Username:</label>
            <input type="text" name="username" value="<?= isset($username) ? htmlspecialchars($username) : '' ?>" required
                class="w-full border border-gray-300 p-2 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div>
            <label class="block font-semibold mb-1">Email:</label>
            <input type="email" name="email" value="<?= isset($email) ? htmlspecialchars($email) : '' ?>" required
                class="w-full border border-gray-300 p-2 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div>
            <label class="block font-semibold mb-1">Password:</label>
            <input type="password" name="password" required
                class="w-full border border-gray-300 p-2 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
            <p class="text-gray-500 text-sm mt-1">Min 8 characters.</p>
        </div>

        <button type="submit"
            class="w-full px-6 py-2 bg-blue-600 text-white font-semibold rounded hover:bg-blue-700 transition">Register</button>

        <p class="text-center mt-4 text-gray-600">Already have an account? <a href="login.php" class="text-blue-600 underline">Login</a></p>
    </form>
</div>

</body>
</html>

