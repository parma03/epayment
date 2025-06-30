<?php
session_start();

header('Content-Type: application/json');

// Check if user is logged in and has valid user_id
$logged_in = isset($_SESSION['id_user']) && !empty($_SESSION['id_user']) && $_SESSION['id_user'] != 0;

echo json_encode([
    'logged_in' => $logged_in,
    'id_user' => $logged_in ? $_SESSION['id_user'] : null,
    'role' => $logged_in && isset($_SESSION['role']) ? $_SESSION['role'] : null
]);
?>