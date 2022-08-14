<?php

// Start the session
session_start();

// Check if the user is already logged in
$user = array_key_exists('user', $_SESSION) ? $_SESSION['user'] : null;
if($user) {
    // Redirect to the home page
    header('Location: index.php');
    
    // Stop the script
    die("You are already logged in");
}

// Some variables for the next time the form is displayed
$error = null;
$username = null;
$email = null;

// Check if the user is trying to sign up
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the user submitted data from the form and store it in variables
    $username = strtolower(filter_input(INPUT_POST, 'username'));
    $email = strtolower(filter_input(INPUT_POST, 'email'));
    $password = filter_input(INPUT_POST, 'password');
    $passwordConfirm = filter_input(INPUT_POST, 'passwordConfirm');

    // Check if the username is empty
    if(!$username) {
        // Set the error message
        $error = "Please enter a username";
    } 
    // Check if username is valid
    else if(!preg_match('/^[a-z0-9]+$/', $username)) {
        // Set the error message
        $error = "Please enter a valid username (letters and numbers only)";
    }
    // Check if the email is empty
    else if(!$email) {
        // Set the error message
        $error = "Please enter an email";
    }
    // Check if the email is valid
    else if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Set the error message
        $error = "Please enter a valid email";
    }
    // Check if the password is empty
    else if(!$password) {
        // Set the error message
        $error = "Please enter a password";
    }
    // Check if the password is strong enough
    else if(strlen($password) < 8) {
        // Set the error message
        $error = "Please enter a password with at least 8 characters";
    }
    // Check if the password and password confirmation do not match
    else if($password !== $passwordConfirm) {
        // Set the error message
        $error = "Passwords do not match";
    }
    // When everything looks fine
    else {
        // Unset passwordConfirm for security reasons
        unset($passwordConfirm);
        unset($_POST['passwordConfirm']);

        // Hash the password
        $password = password_hash($password, PASSWORD_DEFAULT);
        
        // Get the database configuration
        $mysqlConfig = include __DIR__ . '/../includes/mysql-config.php';

        // Connect to the database
        $mysqlConnection = new mysqli($mysqlConfig['host'], $mysqlConfig['username'], $mysqlConfig['password'], $mysqlConfig['database']);

        // Check connection
        if ($mysqlConnection->connect_error) {
            // Log the error
            error_log($mysqlConnection->connect_error);

            // Stop the script
            die("Connection to database failed!");
        }

        // Prepare the query
        $stmt = $mysqlConnection->prepare("SELECT COUNT(id) FROM users WHERE username = ? OR email = ?");

        // Bind the parameters
        $stmt->bind_param("ss", $username, $email);

        // Execute the query
        $stmt->execute();

        // Get the amount of users with the same username or email
        $amount = $stmt->get_result()->fetch_row()[0];

        // Close the statement
        $stmt->close();

        if($amount > 0) {
            $error = "Username or email already exists";
        } else {
            // Prepare the query to insert the user
            $stmt = $mysqlConnection->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");

            // Bind the parameters
            $stmt->bind_param("sss", $username, $email, $password);

            // Execute the query
            $stmt->execute();

            // Close the statement
            $stmt->close();

            // Get the user id of the user that was just inserted
            $userId = $mysqlConnection->insert_id;

            // Prepare the query to get the users data from the database
            $stmt = $mysqlConnection->prepare("SELECT username, email, profile_picture AS profilePicture FROM users WHERE id = ?");

            // Bind the parameters
            $stmt->bind_param("i", $userId);

            // Execute the query
            $stmt->execute();

            // Get the user
            $user = $stmt->get_result()->fetch_assoc();

            // Close the statement
            $stmt->close();

            // Close the connection
            $mysqlConnection->close();

            // Store the user in the session
            $_SESSION['user'] = $user;

            // Redirect to the home page
            header('Location: index.php');
            
            // Stop the script
            die("Successfully signed up! Redirecting to home page..");
        }
            
        // Close the mysql connection
        $mysqlConnection->close();
    }
}

// PHP Template for the sign up page
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>Sign Up</title>
        
        <style>
            body {
                font-family: sans-serif;
            }
        </style>
    </head>

    <body>
        <?php if($error): ?>
            <div style="color: red;"><?= htmlspecialchars($error) ?></div>
            <br>
        <?php endif; ?>

        <form action="sign-up.php" method="POST">
            <table>
                <tr>
                    <td>
                        <label for="username">Username</label>
                    </td>

                    <td>
                        <input type="text" id="username" name="username" value="<?= htmlspecialchars($username) ?>">
                    </td>
                </tr>

                <tr>
                    <td>
                        <label for="email">Email</label>
                    </td>

                    <td>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>">
                    </td>
                </tr>

                <tr>
                    <td>
                        <label for="password">Password</label>
                    </td>

                    <td>
                        <input type="password" id="password" name="password">
                    </td>
                </tr>

                <tr>
                    <td>
                        <label for="passwordConfirm">Confirm Password</label>
                    </td>

                    <td>
                        <input type="password" id="passwordConfirm" name="passwordConfirm">
                    </td>
                </tr>

                <tr>
                    <td colspan="2" style="text-align: right">
                        <button type="submit">
                            Sign Up
                        </button>
                    </td>
                </tr>
            </table>
        </form>
    </body>
</html>