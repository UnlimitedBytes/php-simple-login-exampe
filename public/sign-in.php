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

// Check if the user is trying to sign in
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the user submitted data from the form and store it in variables
    $username = strtolower(filter_input(INPUT_POST, 'username'));
    $password = filter_input(INPUT_POST, 'password');

    // Check if the username is empty
    if(!$username) {
        // Set the error message
        $error = "Please enter a username";
    }
    // Check if the password is empty
    else if(!$password) {
        // Set the error message
        $error = "Please enter a password";
    }
    // When everything looks fine
    else {
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

        // Prepare the query to get the user with the given username
        $stmt = $mysqlConnection->prepare("SELECT username, email, profile_picture as profilePicture, password FROM users WHERE username = ?");

        // Bind the parameters
        $stmt->bind_param("s", $username);

        // Execute the query
        $stmt->execute();

        // Get the result
        $result = $stmt->get_result();

        // Close the statement
        $stmt->close();

        // Close the connection
        $mysqlConnection->close();

        // Check if the user doesn't exist
        if($result->num_rows < 1) {
            $error = "Username or password is invalid.";
        } 
        // Check if multiple users exist
        else if($result->num_rows > 1) {
            $error = "Database corrupted. Please contact the administrator.";
        }
        // When the user exists only one time
        else {
            // Get the user data
            $user = $result->fetch_assoc();

            // Check if the password is incorrect
            if(!password_verify($password, $user['password'])) {
                $error = "Username or password is invalid.";
            } else {
                // Unset password for security reasons
                unset($password);
                unset($user['password']);

                // Store the user in the session
                $_SESSION['user'] = $user;

                // Redirect to the home page
                header('Location: index.php');

                // Stop the script
                die("Successfully signed in! Redirecting to home page..");
            }
        }
    }
}

// PHP Template for the sign in page
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>Sign In</title>
        
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

        <form action="sign-in.php" method="POST">
            <table>
                <tr>
                    <td>
                        <label for="username">Username:</label>
                    </td>

                    <td>
                        <input type="text" id="username" name="username" value="<?= htmlspecialchars($username) ?>">
                    </td>
                </tr>

                <tr>
                    <td>
                        <label for="password">Password:</label>
                    </td>
                    <td>
                        <input type="password" id="password" name="password">
                    </td>
                </tr>

                <tr>
                    <td colspan="2" style="text-align: right">
                        <button type="submit">
                            Sign In
                        </button>
                    </td>
                </tr>
            </table>
        </form>
    </body>
</html>