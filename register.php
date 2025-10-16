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

    // -------- 1. Basic Validations --------
    $v = validate_username($username);
    if ($v !== true) { $error_msg = $v; }
    
    // Only perform email validation if username validation passed (to avoid overwriting $error_msg)
    if (!$error_msg) {
        $v = validate_email($email);
        if ($v !== true) { $error_msg = $v; }
    }
    
    // Only perform password validation if previous validations passed
    if (!$error_msg) {
        $v = validate_password($password);
        if ($v !== true) { $error_msg = $v; }
    }

    // -------- 2. Uniqueness Checks (Database) --------
    
    // Check if the username already exists
    if (!$error_msg) {
        $stmt = $conn->prepare("SELECT id FROM user WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $error_msg = "This username is already taken. Please choose a different one.";
        }
        $stmt->close();
    }
    
    // Check if the email already exists
    if (!$error_msg) {
        $stmt = $conn->prepare("SELECT id FROM user WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $error_msg = "This email address is already registered. Please log in or use a different email.";
        }
        $stmt->close();
    }

    // -------- 3. User Insertion --------
    if (!$error_msg) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Use an error handler for the insertion query just in case (e.g., if unique index constraint on the DB level fails)
        $stmt = $conn->prepare("INSERT INTO user (username, email, password_hash) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $email, $password_hash);

        if ($stmt->execute()) {
            $user_id = $stmt->insert_id;
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            header("Location: index.php");
            exit();
        } else {
            // This is a generic fail-safe error message
            $error_msg = "An unexpected error occurred during registration. Please try again.";
        }
        $stmt->close();
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
<body class="bg-gray-100 text-gray-800 font-sans">

<div class="max-w-md mx-auto mt-20 bg-white p-8 rounded-xl shadow-2xl border border-gray-200">
    <h2 class="text-3xl font-extrabold mb-8 text-center text-blue-800">Create Account</h2>

    <?php if ($success_msg): ?>
        <div class="bg-green-100 text-green-700 p-4 rounded-lg mb-4 font-medium border border-green-200" role="alert"><?= $success_msg ?></div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="bg-red-100 text-red-700 p-4 rounded-lg mb-4 font-medium border border-red-200" role="alert"><?= $error_msg ?></div>
    <?php endif; ?>

    <form method="post" class="space-y-6">
        <div>
            <label for="username" class="block text-sm font-semibold mb-1 text-gray-700">Username</label>
            <input type="text" id="username" name="username" value="<?= isset($username) ? htmlspecialchars($username) : '' ?>" required
                class="w-full border border-gray-300 p-3 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out"
                placeholder="Choose a unique username">
        </div>

        <div>
            <label for="email" class="block text-sm font-semibold mb-1 text-gray-700">Email Address</label>
            <input type="email" id="email" name="email" value="<?= isset($email) ? htmlspecialchars($email) : '' ?>" required
                class="w-full border border-gray-300 p-3 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out"
                placeholder="your.email@example.com">
        </div>

        <div>
            <label for="password" class="block text-sm font-semibold mb-1 text-gray-700">Password</label>
            <input type="password" id="password" name="password" required
                class="w-full border border-gray-300 p-3 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out"
                placeholder="••••••••">
            <p class="text-gray-500 text-xs mt-1">Minimum 8 characters required.</p>
        </div>

        <button type="submit"
            class="w-full px-6 py-3 bg-blue-600 text-white font-bold text-lg rounded-lg hover:bg-blue-700 transition duration-200 ease-in-out shadow-md hover:shadow-lg focus:outline-none focus:ring-4 focus:ring-blue-300">
            Register
        </button>

        <p class="text-center mt-6 text-gray-600 text-sm">
            Already have an account? 
            <a href="login.php" class="text-blue-600 font-semibold hover:text-blue-800 transition">Login Here</a>
        </p>
    </form>
</div>

</body>
</html>
