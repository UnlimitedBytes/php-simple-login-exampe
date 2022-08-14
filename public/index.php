<?php

// Start the session
session_start();

// Check if the user is logged in
$user = array_key_exists('user', $_SESSION) ? $_SESSION['user'] : null;

// PHP Template for the home page
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>My website</title>

        <style>
            body {
                font-family: sans-serif;
            }
        </style>
    </head>

    <body>
        <h1>Welcome to my website<?= $user ? ', ' . htmlspecialchars($user['username']) : '' ?></h1>
        <p>This is a little website I created for myself.</p>
        <?php if (!$user): ?>
            <p>You can <a href="sign-in.php">sign in</a> if you already have an account or <a href="sign-up.php">sign up</a> otherwise.</p>
        <?php else: ?>
            <p>You can <a href="profile.php">edit your profile</a> or <a href="sign-out.php">sign out</a>.</p>
        <?php endif; ?>
    </body>
</html>