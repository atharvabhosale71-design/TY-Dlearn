
<?php
session_start();
require_once "db_connect.php";

$allowedRoles = ["parent", "teacher", "admin"];

if (isset($_SESSION["user_id"], $_SESSION["user_role"])) {
    if (in_array($_SESSION["user_role"], $allowedRoles, true)) {
        header("Location: dashboard.php");
        exit;
    }

    $_SESSION = [];
    session_destroy();
    session_start();
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === "") {
        $message = "Please enter a valid email and password.";
    } else {
        $stmt = mysqli_prepare(
            $conn,
            "SELECT id, name, email, password, role
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);

            if (!$user || !password_verify($password, $user["password"])) {
                $message = "Invalid email or password.";
            } elseif (!in_array($user["role"], $allowedRoles, true)) {
                $message = "This account role is not supported.";
            } else {
                session_regenerate_id(true);

                $_SESSION["user_id"] = (int)$user["id"];
                $_SESSION["user_name"] = $user["name"];
                $_SESSION["user_role"] = $user["role"];

                mysqli_stmt_close($stmt);

                header("Location: dashboard.php");
                exit;
            }

            mysqli_stmt_close($stmt);
        } else {
            $message = "Unable to process login. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | DLearn</title>
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>

<div class="login-box">
    <div class="logo">DLearn</div>

    <h2>Welcome Back!</h2>

    <p class="subtitle">
        Login to access your DLearn account.
    </p>

    <?php if ($message !== ""): ?>
        <div class="message">
            <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <label for="email">Email Address</label>
        <input
            type="email"
            id="email"
            name="email"
            placeholder="Enter your email"
            maxlength="150"
            autocomplete="username"
            required
        >

        <label for="password">Password</label>
        <input
            type="password"
            id="password"
            name="password"
            placeholder="Enter your password"
            autocomplete="current-password"
            required
        >

        <button type="submit">Login</button>
    </form>

    <div class="links">
        <p>Don't have an account?</p>
        <a href="register.php">Register as Parent</a>
        <br>
        <a href="index.php">Back to Home</a>
    </div>
</div>

</body>
</html>
