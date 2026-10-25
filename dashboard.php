
<?php
session_start();

if (!isset($_SESSION["user_id"], $_SESSION["user_role"])) {
    header("Location: login.php");
    exit;
}

$role = $_SESSION["user_role"];
$allowedRoles = ["parent", "teacher", "admin"];

if (!in_array($role, $allowedRoles, true)) {
    http_response_code(403);
    exit("Access denied. Please contact the administrator.");
}

$name = htmlspecialchars(
    $_SESSION["user_name"] ?? "User",
    ENT_QUOTES,
    "UTF-8"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>DLearn Dashboard</title>
<link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body>

<header>
    <h2>DLearn</h2>
    <div class="header-links">
        Welcome, <?= $name ?>
        <a href="index.php">Home</a>
        <a href="logout.php">Logout</a>
    </div>
</header>

<main>
    <section class="welcome">
        <h1>
            <?php
            if ($role === "admin") {
                echo "Admin Dashboard";
            } elseif ($role === "teacher") {
                echo "Teacher Dashboard";
            } else {
                echo "Parent Dashboard";
            }
            ?>
        </h1>

        <p>DLearn — Dyslexia Screening and Learning Support Platform</p>
        <span class="role"><?= ucfirst($role) ?> Account</span>
    </section>

    <div class="cards">

        <?php if ($role === "parent"): ?>

            <div class="card">
                <h3>Child Profiles</h3>
                <p>Add your child and manage their details.</p>
                <a href="child_profile.php">Manage Children</a>
            </div>

            <div class="card">
                <h3>Screening Questionnaire</h3>
                <p>Record observations about your child's reading and learning.</p>
                <a href="screening.php">Start Screening</a>
            </div>

            <div class="card">
                <h3>Learning Area</h3>
                <p>Access available learning activities for your child.</p>
                <a href="learning.php">Open Learning Area</a>
            </div>

            <div class="card">
                <h3>Assessment Reports</h3>
                <p>View saved screening summaries and assessment records.</p>
                <a href="report.php">View Reports</a>
            </div>

            <div class="card">
                <h3>Progress Tracking</h3>
                <p>Review recorded screening scores over time.</p>
                <a href="progress.php">View Progress</a>
            </div>

        <?php elseif ($role === "teacher"): ?>

            <div class="card">
                <h3>Assigned Students</h3>
                <p>View students assigned to you for assessment.</p>
                <a href="teacher_assessment.php">View Assigned Students</a>
            </div>

            <div class="card">
                <h3>Student Assessment</h3>
                <p>Check student answers, enter marks and add assessment remarks.</p>
                <a href="teacher_assessment.php">Assess Students</a>
            </div>

            <div class="card">
                <h3>Assessment Reports</h3>
                <p>Prepare assessment reports for parents.</p>
                <a href="teacher_assessment.php">Manage Assessments</a>
            </div>

        <?php elseif ($role === "admin"): ?>

            <div class="card">
                <h3>User & Child Management</h3>
                <p>View registered users and child profile totals.</p>
                <a href="admin.php">Open Admin Overview</a>
            </div>

            <div class="card">
                <h3>Screening & Reports</h3>
                <p>Review saved screening and assessment records.</p>
                <a href="report.php">View All Reports</a>
            </div>

            <div class="card">
                <h3>Learning Activities</h3>
                <p>Preview the current learning activities.</p>
                <a href="learning.php">Open Learning Page</a>
            </div>

            <div class="card">
                <h3>Student Assignments</h3>
                <p>Manage students and teacher assignments when configured.</p>
                <a href="admin.php">Open Admin Overview</a>
            </div>

        <?php endif; ?>

    </div>
</main>

<footer>
    <p>© <?= date("Y") ?> DLearn</p>
    <p>Educational screening only; not a medical diagnosis.</p>
</footer>

</body>
</html>
