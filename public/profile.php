<?php

// Start the session
session_start();

// Check if the user is not logged in
$user = array_key_exists('user', $_SESSION) ? $_SESSION['user'] : null;
if(!$user) {
    // Redirect to the sign in page
    header('Location: sign-in.php');
    
    // Stop the script
    die("You are not logged in");
}

// Some variables for the next time the page is displayed
$error = null;

// Check if the user is trying to edit their profile
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the user submitted data from the form and store it in variables
    $profilePicture = array_key_exists('profilePicture', $_FILES) ? $_FILES['profilePicture'] : null;
    $username = strtolower(filter_input(INPUT_POST, 'username'));
    $email = strtolower(filter_input(INPUT_POST, 'email'));

    // Check if the profile picture is empty
    if(!$profilePicture) {
        // Set the error message
        $error = "Please select a profile picture";
    } else {
        // Get the file extension
        $extension = pathinfo($profilePicture['name'], PATHINFO_EXTENSION);

        // Check if the file extension is valid
        if(!in_array($extension, array('jpg', 'jpeg', 'png', 'gif'))) {
            // Set the error message
            $error = "Please select a valid profile picture (jpg, jpeg, png or gif)";
        }

        // Check if the file size is valid
        if($profilePicture['size'] > 5000000) {
            // Set the error message
            $error = "Please select a valid profile picture (less than 5MB)";
        }

        // Check if the file is valid
        if($profilePicture['error'] != UPLOAD_ERR_OK) {
            // Set the error message
            switch($profilePicture['error']) {
                case UPLOAD_ERR_INI_SIZE:
                    $error = "File is too large.";
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    $error = "Submitted form is too large.";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $error = "File was only partially uploaded.";
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $error = "No file was uploaded.";
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $error = "No temporary directory was found.";
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $error = "Failed to write file to disk.";
                    break;
                case UPLOAD_ERR_EXTENSION:
                    $error = "File upload stopped by extension.";
                    break;
                default:
                    $error = "Unknown error.";
                    break;
            }
        } else {
            // Get freeimage.host configuration
            $freeImageHostConfig = include __DIR__ . '/../includes/freeimagehost-config.php';

            // Setup a curl session
            $ch = curl_init('https://freeimage.host/api/1/upload?key='.$freeImageHostConfig['key']);

            // Set the request method to POST
            curl_setopt($ch, CURLOPT_POST, true);

            // Set the request post data
            curl_setopt($ch, CURLOPT_POSTFIELDS, [
                'action' => 'upload',
                'source' => base64_encode(file_get_contents($profilePicture['tmp_name'])),
                'format' => 'json',
            ]);

            // Set the return header to false
            curl_setopt($ch, CURLOPT_HEADER, false);

            // Set the return transfer to true
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            // Execute the curl session
            $response = curl_exec($ch);

            // Close the curl session
            curl_close($ch);

            // Check if the response is valid
            if(!$response) {
                // Set the error message
                $error = "Error from storage server.";
            } else {
                // Decode the response
                $response = json_decode($response, true);

                // Check if the response is valid
                if($response['status_code'] != 200) {
                    // Set the error message
                    $error = $response['error']['message'];
                } else {
                    // Get the image url
                    $imageUrl = $response['image']['url'];

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

                    // Prepare the query to update the user profile picture
                    $stmt = $mysqlConnection->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                    
                    // Bind the parameters
                    $stmt->bind_param('si', $imageUrl, $user['id']);

                    // Execute the query
                    $stmt->execute();

                    // Close the statement
                    $stmt->close();

                    // Close the connection
                    $mysqlConnection->close();

                    // Save the image url in the user
                    $user['profilePicture'] = $imageUrl;

                    // Save the user in the session
                    $_SESSION['user'] = $user;
                }
            }
        } 
    }
}

// PHP Template for the profile page
?>


<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>Profile (<?= htmlspecialchars($user['username']) ?>)</title>
        
        <style>
            body {
                font-family: sans-serif;
            }
        </style>
    </head>

    <body>
        <h1>Edit your profile</h1>

        <?php if($error): ?>
            <div style="color: red;"><?= htmlspecialchars($error) ?></div>
            <br>
        <?php endif; ?>

        <form action="profile.php" method="POST" enctype="multipart/form-data">
            <table>
                <tr>
                    <td colspan="2">
                        <img src="<?= htmlspecialchars($user['profilePicture']) ?>" alt="Users profile picture">
                    </td>
                </tr>

                <tr>
                    <td>
                        <label for="profilePicture">
                            Profile picture
                        </label>
                    </td>

                    <td>
                        <input type="file" id="profilePicture" name="profilePicture">
                    </td>
                </tr>

                <tr>
                    <td>
                        <label for="username">Username</label>
                    </td>
                    
                    <td>
                        <input type="text" id="username" name="username" value="<?= htmlspecialchars($user['username']) ?>" disabled>
                    </td>
                </tr>
                
                <tr>
                    <td>
                        <label for="email">Email</label>
                    </td>

                    <td>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                    </td>
                </tr>

                <tr>
                    <td colspan="2" style="text-align: right">
                        <button type="submit">
                            Edit Profile
                        </button>
                    </td>
                </tr>
            </table>
        </form>
    </body>
</html>