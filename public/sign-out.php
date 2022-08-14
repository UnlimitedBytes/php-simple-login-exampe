<?php

// Start the session
session_start();

// Check if the user is logged in
$user = array_key_exists('user', $_SESSION) ? $_SESSION['user'] : null;

// If the user is not logged in
if(!$user) {
    // Redirect to the home page
    header('Location: index.php');
    
    // Stop the script
    die("You are not logged in");
}

// Log the user out
unset($_SESSION['user']);

// Redirect to the home page
header('Location: index.php');

// Stop the script
die("You are logged out");
