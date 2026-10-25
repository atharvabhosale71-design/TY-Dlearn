
<?php
require_once "db_connect.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    if ($name === "" || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
        $message = "Enter valid details. Password must be at least 8 characters.";
    } else {
        $check = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($check, "s", $email);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) > 0) {
            $message = "Email already registered.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $role = "parent";

            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)"
            );
            mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $hash, $role);

            if (mysqli_stmt_execute($stmt)) {
                $message = "Registration successful! You can now login.";
            } else {
                $message = "Registration failed. Please try again.";
            }
            mysqli_stmt_close($stmt);
        }
        mysqli_stmt_close($check);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Parent Registration | DLearn</title>
<style>
body{font-family:Arial;background:#f3f6fc;display:flex;justify-content:center;align-items:center;min-height:100vh;margin:0}
.box{background:white;padding:30px;border-radius:12px;width:340px;box-shadow:0 4px 18px #0001}
h2{text-align:center;color:#293b70}
input,button{box-sizing:border-box;width:100%;padding:12px;margin:9px 0;border:1px solid #ccc;border-radius:6px}
button{background:#4056a1;color:white;border:0;cursor:pointer}
p{text-align:center;overflow-wrap:anywhere}
a{color:#4056a1}
</style>
</head>
<body>
<div class="box">
<h2>DLearn Registration</h2>
<p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
<form method="POST">
<input type="text" name="name" placeholder="Full Name" maxlength="100" required>
<input type="email" name="email" placeholder="Email Address" maxlength="150" required>
<input type="password" name="password" placeholder="Password (minimum 8 characters)" minlength="8" required>
<button type="submit">Register</button>
</form>
<p>Already registered? <a href="login.php">Login here</a></p>
<p><a href="index.php">Back to Home</a></p>
</div>
</body>
</html>
