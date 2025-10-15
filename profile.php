<?php
session_start();
include 'navbar.php';
require 'config.php';
include 'validators.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['password'];

    // -------- Validations --------
    $v = validate_username($username);
    if ($v !== true) { $error_msg = $v; }
    $v = validate_email($email);
    if ($v !== true) { $error_msg = $v; }
    $v = validate_password($new_password);
    if ($v !== true) { $error_msg = $v; }

    if (!$error_msg) {
        // Verify current password
        $stmt = $conn->prepare("SELECT password_hash FROM user WHERE id=?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($password_hash_db);
        $stmt->fetch();
        $stmt->close();

        if (!password_verify($current_password, $password_hash_db)) {
            $error_msg = "Current password is incorrect!";
        } else {
            // Handle new password
            $password_sql = "";
            if (!empty($new_password)) {
                $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $password_sql = ", password_hash='$new_password_hash'";
            }

            // Handle profile picture
            $pfp_sql = "";
            if (isset($_FILES['pfp']) && $_FILES['pfp']['error'] === 0) {
                $upload_dir = 'uploads/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                $ext = pathinfo($_FILES['pfp']['name'], PATHINFO_EXTENSION);
                $filename = "pfp_{$user_id}." . $ext;
                $target = $upload_dir . $filename;

                if (move_uploaded_file($_FILES['pfp']['tmp_name'], $target)) {
                    $pfp_sql = ", pfp='$target'";
                } else {
                    $error_msg = "Failed to upload profile picture.";
                }
            }

            // Update DB if no error
            if (!$error_msg) {
                $sql = "UPDATE user SET username=?, email=? $password_sql $pfp_sql WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssi", $username, $email, $user_id);
                $stmt->execute();
                $stmt->close();

                $_SESSION['username'] = $username;
                $success_msg = "Profile updated successfully!";
            }
        }
    }
}

// Fetch user data
$stmt = $conn->prepare("SELECT username, email, pfp, created_at, updated_at FROM user WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Profile</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800">

<div class="max-w-4xl mx-auto mt-12 bg-white p-8 rounded-lg shadow-md">
    <h2 class="text-3xl font-bold mb-6 text-center">My Profile</h2>

    <?php if ($success_msg): ?>
        <div class="bg-green-100 text-green-700 p-3 rounded mb-4"><?= $success_msg ?></div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="bg-red-100 text-red-700 p-3 rounded mb-4"><?= $error_msg ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="space-y-4">
        <div>
            <label class="block font-semibold mb-1">Username:</label>
            <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required
                class="w-full border border-gray-300 p-2 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div>
            <label class="block font-semibold mb-1">Email:</label>
            <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required
                class="w-full border border-gray-300 p-2 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div>
            <label class="block font-semibold mb-1">Current Password (required to update):</label>
            <input type="password" name="current_password" required
                class="w-full border border-gray-300 p-2 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div>
            <label class="block font-semibold mb-1">New Password (leave empty to keep current):</label>
            <input type="password" name="password"
                class="w-full border border-gray-300 p-2 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
            <p class="text-gray-500 text-sm mt-1">Min 8 characters.</p>
        </div>

        <div>
            <label class="block font-semibold mb-1">Profile Picture:</label>
            <?php if ($user['pfp']): ?>
                <img id="pfpPreview" src="<?= $user['pfp'] ?>" class="w-24 h-24 object-cover rounded-full mb-2">
            <?php else: ?>
                <img id="pfpPreview" class="w-24 h-24 object-cover rounded-full mb-2 hidden">
            <?php endif; ?>
            <input type="file" name="pfp" class="block" accept="image/*" onchange="previewPFP(event)">
        </div>

        <div class="flex justify-between items-center mt-4">
            <button type="submit"
                class="px-6 py-2 bg-blue-600 text-white font-semibold rounded hover:bg-blue-700 transition">Update Profile</button>
            <a href="my_quizzes.php"
                class="px-6 py-2 bg-gray-300 text-gray-800 font-semibold rounded hover:bg-gray-400 transition">My Quizzes</a>
        </div>
    </form>

    <div class="mt-6 text-gray-500 text-sm">
        <p>Account created at: <?= $user['created_at'] ?></p>
        <p>Last updated at: <?= $user['updated_at'] ?></p>
    </div>
</div>

<script>
// Live preview for profile picture
function previewPFP(event) {
    const output = document.getElementById('pfpPreview');
    output.src = URL.createObjectURL(event.target.files[0]);
    output.classList.remove('hidden');
}
</script>

</body>
</html>






