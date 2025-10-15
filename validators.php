<?php
// validators.php

function validate_username($username) {
    $username = trim($username);
    if (empty($username)) return "Username cannot be empty.";
    if (strlen($username) < 3) return "Username must be at least 3 characters.";
    return true;
}

function validate_email($email) {
    $email = trim($email);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return "Invalid email format.";
    }
    return true;
}

function validate_password($password) {
    if (!empty($password) && strlen($password) < 8) {
        return "Password must be at least 8 characters.";
    }
    return true;
}
