<?php
session_start();
$host = "localhost";
$db = "crypto_db";
$user = "root";
$pass = "";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT UserID, Name, PasswordHash FROM Users WHERE Email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 1) {
        $stmt->bind_result($userId, $name, $hash);
        $stmt->fetch();
        if (password_verify($password, $hash)) {
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_name'] = $name;
            header("Location: dashboard.php");
            exit;
        } else {
            $message = "❌ Incorrect password.";
        }
    } else {
        $message = "❌ Email not found.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <style>
        body { font-family: Arial; background: #f5f6fa; padding: 50px; }
        .box { background: #fff; width: 350px; padding: 20px; margin: auto; border-radius: 10px; box-shadow: 0 0 10px #ccc; }
        input[type="email"], input[type="password"] {
            width: 100%; padding: 10px; margin-bottom: 15px; border-radius: 5px; border: 1px solid #ccc;
        }
        input[type="submit"] {
            background: #273c75; color: #fff; border: none; padding: 10px; width: 100%; border-radius: 5px;
        }
        .msg { margin: 10px 0; color: #e84118; }
    </style>
</head>
<body>
    <div class="box">
        <h2>Login</h2>
        <?php
        if (isset($_GET['registered'])) echo "<div class='msg' style='color: green;'>✅ Registered successfully. Please log in.</div>";
        if ($message) echo "<div class='msg'>$message</div>";
        ?>
        <form method="POST">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="submit" value="Login">
        </form>
        <p>Don't have an account? <a href="register.php">Register here</a></p>
    </div>
</body>
</html>
