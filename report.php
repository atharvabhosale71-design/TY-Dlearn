
<?php
session_start();
require_once "db_connect.php";

if (!isset($_SESSION["user_id"], $_SESSION["user_role"])) {
    header("Location: login.php");
    exit;
}

$role = $_SESSION["user_role"];
$userId = (int)$_SESSION["user_id"];

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

$baseQuery = "
    SELECT s.*, c.child_name, u.name AS assessor
    FROM screening_assessments s
    JOIN children c ON c.id = s.child_id
    LEFT JOIN users u ON u.id = s.assessed_by
";

if ($role === "parent") {
    // Parents can see only their own children's reports.
    $sql = $baseQuery . "
        WHERE c.parent_id = ?
        ORDER BY s.id DESC
    ";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $userId);

} elseif ($role === "teacher") {
    // Teachers can see reports only for their assigned children.
    $sql = $baseQuery . "
        JOIN teacher_assignments ta
          ON ta.child_id = c.id
         AND ta.teacher_id = ?
        WHERE
            (s.assessment_type = 'parent')
            OR
            (s.assessment_type = 'teacher' AND s.assessed_by = ?)
        ORDER BY s.id DESC
    ";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $userId, $userId);

} elseif ($role === "admin") {
    // Admin can review all reports.
    $sql = $baseQuery . " ORDER BY s.id DESC";

    $stmt = mysqli_prepare($conn, $sql);

} else {
    http_response_code(403);
    exit("Access denied.");
}

if (!$stmt || !mysqli_stmt_execute($stmt)) {
    http_response_code(500);
    exit("Unable to load reports. Please try again.");
}

$records = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>DLearn Assessment Reports</title>
<style>
* {
    box-sizing: border-box;
}

body {
    font-family: Arial, sans-serif;
    background: #f3f6fc;
    color: #24304d;
    margin: 0;
}

header {
    background: #4056a1;
    color: white;
    padding: 20px 6%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

header a {
    color: white;
}

main {
    max-width: 1000px;
    margin: 25px auto;
    padding: 0 16px;
}

.card {
    background: white;
    padding: 22px;
    border-radius: 12px;
    margin-bottom: 18px;
    box-shadow: 0 3px 12px #0000000d;
}

.record {
    border-bottom: 1px solid #ddd;
    padding: 18px 0;
}

.record:last-child {
    border-bottom: none;
}

.record h3 {
    margin-top: 0;
    color: #293b70;
}

.score {
    font-size: 18px;
    font-weight: bold;
}

.badge {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 16px;
    background: #e9edff;
}

small {
    color: #666;
}

p {
    line-height: 1.6;
}

@media (max-width: 600px) {
    .card {
        padding: 16px;
    }
}
</style>
</head>
<body>

<header>
    <h2>DLearn Assessment Reports</h2>
    <nav>
        <a href="dashboard.php">Back to Dashboard</a>
        |
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main>
<div class="card">
    <h2>
        <?php
        if ($role === "parent") {
            echo "My Child's Assessment Reports";
        } elseif ($role === "teacher") {
            echo "Assigned Students' Reports";
        } else {
            echo "All Assessment Reports";
        }
        ?>
    </h2>

    <p>
        These records support educational planning and are not a medical diagnosis.
    </p>

    <?php if (mysqli_num_rows($records) === 0): ?>
        <p>No assessment reports are available yet.</p>
    <?php else: ?>

        <?php while ($r = mysqli_fetch_assoc($records)): ?>
            <div class="record">
                <h3><?= h($r["child_name"]) ?></h3>

                <p>
                    <strong>Assessment Type:</strong>
                    <span class="badge">
                        <?= h(ucfirst($r["assessment_type"])) ?>
                    </span>
                </p>

                <p>
                    <strong>
                        <?= $r["assessment_type"] === "teacher"
                            ? "Teacher Marks:"
                            : "Recorded Score:" ?>
                    </strong>

                    <span class="score">
                        <?= h($r["score"]) ?>
                        <?= $r["assessment_type"] === "teacher"
                            ? " / 10"
                            : "%" ?>
                    </span>
                </p>

                <p>
                    <strong>Summary / Remarks:</strong>
                    <?= nl2br(h($r["summary"])) ?>
                </p>

                <p>
                    <strong>Assessor:</strong>
                    <?= h($r["assessor"] ?: "Parent") ?>
                </p>

                <small>
                    Recorded on: <?= h($r["created_at"]) ?>
                </small>
            </div>
        <?php endwhile; ?>

    <?php endif; ?>
</div>
</main>
</body>
</html>
<?php
mysqli_stmt_close($stmt);
?>
