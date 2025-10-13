<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
?>

<nav class="bg-blue-600 text-white p-4 shadow-md">
    <div class="max-w-6xl mx-auto flex justify-between items-center">
        <!-- Logo / Home link -->
        <a href="index.php" class="font-bold text-xl hover:text-gray-200">My Kahoot App</a>

        <!-- Navigation links -->
        <div class="space-x-4">
            <?php if ($isLoggedIn): ?>
                <a href="profile.php" class="hover:text-gray-200">Profile</a>
                <a href="logout.php" class="hover:text-gray-200">Logout</a>
            <?php else: ?>
                <a href="register.php" class="hover:text-gray-200">Register</a>
                <a href="login.php" class="hover:text-gray-200">Login</a>
            <?php endif; ?>
            <a href="index.php" class="hover:text-gray-200">Home</a>
        </div>
    </div>
</nav>
