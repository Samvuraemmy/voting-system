<?php
session_start();
include 'includes/conn.php'; // Include your database connection

// Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Required files
require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $voterId = $_POST['voter_id'];

    // Check if the voter ID exists in the database
    $stmt = $conn->prepare("SELECT * FROM voters WHERE voters_id = ?");
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }

    $stmt->bind_param("s", $voterId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Fetch the voter's information
        $voter = $result->fetch_assoc();

        // Generate a unique reset token (10 characters long)
        $resetToken = bin2hex(random_bytes(5)); // Generates a 10-character token

        // Store the reset token in the database
        $stmt = $conn->prepare("UPDATE voters SET reset_token = ? WHERE voters_id = ?");
        if ($stmt === false) {
            die("Error preparing statement for update: " . $conn->error);
        }

        // Bind parameters and execute
        $stmt->bind_param("si", $resetToken, $voterId);
        if (!$stmt->execute()) {
            die("Error executing statement: " . $stmt->error);
        }

        // Create a reset link
        $resetLink = "http://localhost:40/votesystem/update_password.php?token=" . $resetToken;

        // Send email with the reset link using PHPMailer
        $mail = new PHPMailer(true);
        try {
            // Server settings
            $mail->isSMTP();                              // Send using SMTP
            $mail->Host       = 'smtp.gmail.com';       // Set the SMTP server to send through
            $mail->SMTPAuth   = true;                     // Enable SMTP authentication
            $mail->Username   = 'emmysamvura@gmail.com'; // Your email
            $mail->Password   = 'okjbdbrdunrgowxk';      // Your email password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Enable implicit SSL encryption
            $mail->Port       = 465;                      // TCP port to connect to

            // Recipients
            $mail->setFrom('emmysamvura@gmail.com', 'online voting system');
            $mail->addAddress($voter['email'], $voter['name']); // Use the voter's email

            // Content
            $mail->isHTML(true);  // Set email format to HTML
            $mail->Subject = 'Password Reset Request';
            $mail->Body    = "Click the link to reset your password: <a href='$resetLink'>$resetLink</a><br>";
            $mail->AltBody = "Click the link to reset your password: $resetLink"; // For non-HTML mail clients

            // Send the email
            $mail->send();
            $_SESSION['success'] = "Reset link has been sent to your email.";
        } catch (Exception $e) {
            $_SESSION['error'] = "Failed to send email. Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        $_SESSION['error'] = "Voter ID not found.";
    }

    header('location: reset_password.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <?php include 'includes/header.php'; ?>
</head>
<body class="hold-transition login-page">
<div class="login-box">
    <div class="login-logo">
        <b>Reset Password</b>
    </div>
  
    <div class="login-box-body">
        <p class="login-box-msg">Enter your Voter ID to reset your password</p>

        <form action="reset_password.php" method="POST">
            <div class="form-group has-feedback">
                <input type="text" class="form-control" name="voter_id" placeholder="Voter's ID" required>
                <span class="glyphicon glyphicon-user form-control-feedback"></span>
            </div>
            <div class="row">
                <div class="col-xs-12">
                    <button type="submit" class="btn btn-warning btn-block btn-flat"><i class="fa fa-key"></i> Send Reset Link</button>
                    
                </div>
            </div>
        </form>
    </div>

    <?php
    // Display success or error messages
    if (isset($_SESSION['success'])) {
        echo "<div class='callout callout-success text-center mt20'>" . $_SESSION['success'] . "</div>";
        unset($_SESSION['success']);
    }

    if (isset($_SESSION['error'])) {
        echo "<div class='callout callout-danger text-center mt20'>" . $_SESSION['error'] . "</div>";
        unset($_SESSION['error']);
    }
    ?>
</div>
	
<?php include 'includes/scripts.php' ?>
</body>
</html>  
