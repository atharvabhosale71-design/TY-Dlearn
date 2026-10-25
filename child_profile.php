
<?php
session_start();
require_once "db_connect.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["user_role"] !== "parent") {
    header("Location: login.php");
    exit;
}

$message = "";
$parent_id = (int) $_SESSION["user_id"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $child_name = trim($_POST["child_name"]);
    $dob = $_POST["date_of_birth"] ?: null;
    $gender = $_POST["gender"] ?: null;
    $school = trim($_POST["school_name"]);

    if ($child_name === "") {
        $message = "Please enter the child's name.";
    } else {
        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO children (parent_id, child_name, date_of_birth, gender, school_name)
             VALUES (?, ?, ?, ?, ?)"
        );

        mysqli_stmt_bind_param(
            $stmt, "issss", $parent_id, $child_name, $dob, $gender, $school
        );

        if (mysqli_stmt_execute($stmt)) {
            $message = "Child profile saved successfully!";
        } else {
            $message = "Could not save profile. Please try again.";
        }

        mysqli_stmt_close($stmt);
    }
}

$stmt = mysqli_prepare(
    $conn,
    "SELECT id, child_name, date_of_birth, gender, school_name, created_at
     FROM children WHERE parent_id = ? ORDER BY id DESC"
);
mysqli_stmt_bind_param($stmt, "i", $parent_id);
mysqli_stmt_execute($stmt);
$children = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Child Profile | DLearn</title>
<style>
body{font-family:Arial;background:#f3f6fc;margin:0;color:#24304d}
header{background:#4056a1;color:white;padding:18px 7%}
header a{color:white}
main{max-width:750px;margin:30px auto;padding:20px}
.card{background:white;padding:24px;border-radius:12px;margin-bottom:22px;box-shadow:0 3px 12px #0001}
input,select,button{box-sizing:border-box;width:100%;padding:11px;margin:8px 0 15px;border:1px solid #ccc;border-radius:6px}
button{background:#4056a1;color:white;border:0;cursor:pointer}
.child{border-top:1px solid #ddd;padding:12px 0}
</style>
</head>
<body>
<header>
<h2>DLearn — Child Profile</h2>
<a href="dashboard.php">← Back to Dashboard</a>
</header>
<main>
<div class="card">
<h2>Add Child Details</h2>
<p><?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?></p>
<form method="POST">
<label>Child's Name</label>
<input type="text" name="child_name" maxlength="100" required>

<label>Date of Birth</label>
<input type="date" name="date_of_birth" max="<?= date('Y-m-d') ?>">

<label>Gender</label>
<select name="gender">
<option value="">Select gender (optional)</option>
<option value="Male">Male</option>
<option value="Female">Female</option>
<option value="Other">Other</option>
</select>

<label>School Name</label>
<input type="text" name="school_name" maxlength="150">

<button type="submit">Save Child Profile</button>
</form>
</div>

<div class="card">
<h2>Saved Children</h2>
<?php if (mysqli_num_rows($children) === 0): ?>
<p>No child profiles added yet.</p>
<?php else: ?>
<?php while ($child = mysqli_fetch_assoc($children)): ?>
<div class="child">
<strong><?= htmlspecialchars($child["child_name"], ENT_QUOTES, "UTF-8") ?></strong><br>
Date of Birth: <?= htmlspecialchars($child["date_of_birth"] ?: "Not provided", ENT_QUOTES, "UTF-8") ?><br>
Gender: <?= htmlspecialchars($child["gender"] ?: "Not provided", ENT_QUOTES, "UTF-8") ?><br>
School: <?= htmlspecialchars($child["school_name"] ?: "Not provided", ENT_QUOTES, "UTF-8") ?>
</div>
<?php endwhile; ?>
<?php endif; ?>
</div>
</main>
</body>
</html>
