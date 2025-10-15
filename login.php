<?php
session_start();
require 'config.php';
include 'validators.php';

$error_msg = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // -------- Validation --------
    $v = validate_username($username);
    if ($v !== true) { $error_msg = $v; }

    if (!$error_msg) {
        $stmt = $conn->prepare("SELECT * FROM user WHERE username=?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row['password_hash'])) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                header("Location: index.php");
                exit();
            } else {
                $error_msg = "Invalid password.";
            }
        } else {
            $error_msg = "User not found.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800">

<div class="max-w-md mx-auto mt-20 bg-white p-8 rounded-lg shadow-md">
    <h2 class="text-3xl font-bold mb-6 text-center">Login</h2>

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
            <label class="block font-semibold mb-1">Password:</label>
            <input type="password" name="password" required
                class="w-full border border-gray-300 p-2 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <button type="submit"
            class="w-full px-6 py-2 bg-blue-600 text-white font-semibold rounded hover:bg-blue-700 transition">Login</button>

        <p class="text-center mt-4 text-gray-600">No account? <a href="register.php" class="text-blue-600 underline">Register</a></p>
    </form>
</div>

</body>
</html>

