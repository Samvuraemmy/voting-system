<?php
session_start();
include 'includes/conn.php'; // Include your database connection

// Check if the token is provided
if (!isset($_GET['token'])) {
    $_SESSION['error'] = "Invalid request.";
    header('Location: reset_password.php'); // Redirect if no token
    exit();
}

$token = $_GET['token'];

// Validate the token
$stmt = $conn->prepare("SELECT * FROM voters WHERE reset_token = ?");
if ($stmt === false) {
    die("Error preparing statement: " . htmlspecialchars($conn->error));
}

$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

// Check if the token is valid
if ($result->num_rows === 0) {
    $_SESSION['error'] = "Invalid or expired reset token. Please request a new password reset.";
    header('Location: reset_password.php'); // Redirect if token is invalid
    exit();
}

// Fetch the voter
$voter = $result->fetch_assoc();
$voterId = htmlspecialchars($voter['voters_id']); // Escape the voter ID for HTML output

// Process the form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    // Check if passwords match
    if ($newPassword !== $confirmPassword) {
        $_SESSION['error'] = "Passwords do not match.";
        header("Location: update_password.php?token=" . urlencode($token)); // Redirect to show error message
        exit();
    }

    // Hash the new password
    $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

    // Update the password in the database and reset the token
    $stmt = $conn->prepare("UPDATE voters SET password = ?, reset_token = NULL WHERE voters_id = ?");
    if ($stmt === false) {
        die("Error preparing statement for update: " . htmlspecialchars($conn->error));
    }

    // Bind parameters and execute
    $stmt->bind_param("ss", $newPasswordHash, $voterId);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Your password has been reset successfully.";
        echo "<script>
               
                window.location.href = 'login.php';
              </script>";
        exit();
    } else {
        $_SESSION['error'] = "Failed to update password. Please try again.";
        header("Location: update_password.php?token=" . urlencode($token)); // Redirect to show error message
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Password</title>
    <?php include 'includes/header.php'; ?>
</head>
<body class="hold-transition login-page">
<div class="login-box">
    <div class="login-logo">
        <b>Update Password</b>
    </div>
  
    <div class="login-box-body">
        <p class="login-box-msg">Voter ID: <?php echo $voterId; ?></p>

        <form action="update_password.php?token=<?php echo htmlspecialchars($token); ?>" method="POST">
            <div class="form-group has-feedback">
                <input type="password" class="form-control" name="new_password" placeholder="New Password" required>
                <span class="glyphicon glyphicon-lock form-control-feedback"></span>
            </div>
            <div class="form-group has-feedback">
                <input type="password" class="form-control" name="confirm_password" placeholder="Confirm Password" required>
                <span class="glyphicon glyphicon-lock form-control-feedback"></span>
            </div>
            <div class="row">
                <div class="col-xs-12">
                    <button type="submit" class="btn btn-warning btn-block btn-flat">Update Password</button>
                </div>
            </div>
        </form>
    </div>

    <?php
        // Display error messages if any
        if (isset($_SESSION['error'])) {
            echo "<div class='callout callout-danger text-center mt20'>" . htmlspecialchars($_SESSION['error']) . "</div>";
            unset($_SESSION['error']);
        }
    ?>
</div>
    
<?php include 'includes/scripts.php'; ?>
</body>
</html>
