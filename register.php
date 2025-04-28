<?php
$host = "localhost";
$db = "crypto_db";
$user = "root";
$pass = ""; // change if needed

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("SELECT Email FROM Users WHERE Email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $message = "⚠️ Email already exists.";
    } else {
        $stmt = $conn->prepare("INSERT INTO Users (Name, Email, PasswordHash) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $password);
        if ($stmt->execute()) {
            $userId = $stmt->insert_id;
            $stmt2 = $conn->prepare("INSERT INTO Wallets (UserID) VALUES (?)");
            $stmt2->bind_param("i", $userId);
            $stmt2->execute();
            $stmt2->close();
            header("Location: login.php?registered=1");
            exit;
        } else {
            $message = "❌ Registration failed.";
        }
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <style>
        body { font-family: Arial; background: #f5f6fa; padding: 50px; }
        .box { background: #fff; width: 350px; padding: 20px; margin: auto; border-radius: 10px; box-shadow: 0 0 10px #ccc; }
        input[type="text"], input[type="email"], input[type="password"] {
            width: 100%; padding: 10px; margin-bottom: 15px; border-radius: 5px; border: 1px solid #ccc;
        }
        input[type="submit"] {
            background: #44bd32; color: #fff; border: none; padding: 10px; width: 100%; border-radius: 5px;
        }
        .msg { margin: 10px 0; color: #e84118; }
    </style>
</head>
<body>
    <div class="box">
        <h2>Register</h2>
        <?php if ($message) echo "<div class='msg'>$message</div>"; ?>
        <form method="POST">
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="submit" value="Register">
        </form>
        <p>Already have an account? <a href="login.php">Login here</a></p>
    </div>
</body>
</html>
