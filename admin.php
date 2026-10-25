
<?php
session_start();
require_once "db_connect.php";

if (
    !isset($_SESSION["user_id"], $_SESSION["user_role"]) ||
    $_SESSION["user_role"] !== "admin"
) {
    http_response_code(403);
    exit("Admin access required.");
}

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function countRows($conn, $sql) {
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        return 0;
    }
    return (int)mysqli_fetch_row($result)[0];
}

$message = "";
$messageType = "success";

/* Assign a child to a teacher */
if ($_SERVER["REQUEST_METHOD"] === "POST" &&
    ($_POST["action"] ?? "") === "assign_student") {

    $teacherId = filter_input(INPUT_POST, "teacher_id", FILTER_VALIDATE_INT);
    $childId = filter_input(INPUT_POST, "child_id", FILTER_VALIDATE_INT);

    if (!$teacherId || !$childId) {
        $message = "Please select both a teacher and a child.";
        $messageType = "error";
    } else {
        // Confirm selected user is a teacher.
        $teacherCheck = mysqli_prepare(
            $conn,
            "SELECT id FROM users WHERE id = ? AND role = 'teacher' LIMIT 1"
        );
        mysqli_stmt_bind_param($teacherCheck, "i", $teacherId);
        mysqli_stmt_execute($teacherCheck);
        $teacherResult = mysqli_stmt_get_result($teacherCheck);
        $validTeacher = mysqli_num_rows($teacherResult) === 1;
        mysqli_stmt_close($teacherCheck);

        // Confirm selected child exists.
        $childCheck = mysqli_prepare(
            $conn,
            "SELECT id FROM children WHERE id = ? LIMIT 1"
        );
        mysqli_stmt_bind_param($childCheck, "i", $childId);
        mysqli_stmt_execute($childCheck);
        $childResult = mysqli_stmt_get_result($childCheck);
        $validChild = mysqli_num_rows($childResult) === 1;
        mysqli_stmt_close($childCheck);

        if (!$validTeacher || !$validChild) {
            $message = "The selected teacher or child is invalid.";
            $messageType = "error";
        } else {
            $insert = mysqli_prepare(
                $conn,
                "INSERT INTO teacher_assignments (teacher_id, child_id)
                 VALUES (?, ?)"
            );
            mysqli_stmt_bind_param($insert, "ii", $teacherId, $childId);

            if (mysqli_stmt_execute($insert)) {
                $message = "Student assigned to teacher successfully.";
            } elseif (mysqli_stmt_errno($insert) === 1062) {
                $message = "This student is already assigned to that teacher.";
                $messageType = "error";
            } else {
                $message = "Unable to assign student. Please check the database.";
                $messageType = "error";
            }
            mysqli_stmt_close($insert);
        }
    }
}

$totalUsers = countRows($conn, "SELECT COUNT(*) FROM users");
$totalChildren = countRows($conn, "SELECT COUNT(*) FROM children");
$totalAssessments = countRows($conn, "SELECT COUNT(*) FROM screening_assessments");

$teachers = mysqli_query(
    $conn,
    "SELECT id, name, email
     FROM users
     WHERE role = 'teacher'
     ORDER BY name"
);

$children = mysqli_query(
    $conn,
    "SELECT c.id, c.child_name, u.name AS parent_name
     FROM children c
     JOIN users u ON u.id = c.parent_id
     ORDER BY c.child_name"
);

$assignments = mysqli_query(
    $conn,
    "SELECT ta.id, t.name AS teacher_name, c.child_name,
            p.name AS parent_name, ta.assigned_at
     FROM teacher_assignments ta
     JOIN users t ON t.id = ta.teacher_id
     JOIN children c ON c.id = ta.child_id
     JOIN users p ON p.id = c.parent_id
     ORDER BY ta.id DESC"
);

$users = mysqli_query(
    $conn,
    "SELECT id, name, email, role, created_at
     FROM users
     ORDER BY id DESC LIMIT 50"
);

$assessments = mysqli_query(
    $conn,
    "SELECT s.id, c.child_name, s.assessment_type, s.score,
            s.summary, s.created_at, u.name AS assessor_name
     FROM screening_assessments s
     JOIN children c ON c.id = s.child_id
     LEFT JOIN users u ON u.id = s.assessed_by
     ORDER BY s.id DESC LIMIT 50"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>DLearn Admin</title>
<style>
*{box-sizing:border-box}
body{font-family:Arial,sans-serif;background:#f3f6fc;color:#24304d;margin:0}
header{background:#293b70;color:white;padding:20px 6%;display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap}
header a{color:white}
main{max-width:1150px;margin:25px auto;padding:0 16px}
.cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:15px;margin-bottom:22px}
.card{background:white;padding:20px;border-radius:10px;margin-bottom:20px;overflow-x:auto;box-shadow:0 3px 12px #0000000d}
table{border-collapse:collapse;width:100%;background:white}
th,td{padding:10px;border:1px solid #ddd;text-align:left;overflow-wrap:anywhere}
th{background:#e9edfa}
select,button{padding:11px;border:1px solid #ccd2e0;border-radius:6px;font:inherit}
select{width:100%;margin:6px 0 14px}
button{background:#4056a1;color:white;cursor:pointer;border:0}
button:hover{background:#293b70}
.message{padding:12px;border-radius:6px;margin:12px 0}
.success{background:#e4f7e9;color:#17652b}
.error{background:#ffe8e8;color:#922626}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:15px}
small{color:#65708a}
@media(max-width:600px){.form-grid{grid-template-columns:1fr}header{display:block}header a{display:inline-block;margin-top:8px}}
</style>
</head>
<body>
<header>
    <h2>DLearn Admin Dashboard</h2>
    <div>
        <a href="dashboard.php">Back to Dashboard</a> |
        <a href="logout.php">Logout</a>
    </div>
</header>

<main>
<h1>Admin Overview</h1>

<?php if ($message !== ""): ?>
    <div class="message <?= h($messageType) ?>">
        <?= h($message) ?>
    </div>
<?php endif; ?>

<div class="cards">
    <div class="card"><h3>Total Users</h3><h2><?= $totalUsers ?></h2></div>
    <div class="card"><h3>Child Profiles</h3><h2><?= $totalChildren ?></h2></div>
    <div class="card"><h3>Assessments</h3><h2><?= $totalAssessments ?></h2></div>
</div>

<div class="card">
    <h2>Assign Student to Teacher</h2>
    <p>Select a teacher and a child. Teachers will only be able to assess children assigned to them.</p>

    <?php if (mysqli_num_rows($teachers) === 0): ?>
        <p>No teacher accounts found. Create a user with the <code>teacher</code> role first.</p>
    <?php elseif (mysqli_num_rows($children) === 0): ?>
        <p>No child profiles found. A parent must add a child first.</p>
    <?php else: ?>
        <?php
        // Reset result pointers for the form after checking row counts.
        mysqli_data_seek($teachers, 0);
        mysqli_data_seek($children, 0);
        ?>
        <form method="POST">
            <input type="hidden" name="action" value="assign_student">

            <div class="form-grid">
                <div>
                    <label for="teacher_id">Select Teacher</label>
                    <select name="teacher_id" id="teacher_id" required>
                        <option value="">Choose a teacher</option>
                        <?php while ($t = mysqli_fetch_assoc($teachers)): ?>
                            <option value="<?= (int)$t["id"] ?>">
                                <?= h($t["name"]) ?> (<?= h($t["email"]) ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div>
                    <label for="child_id">Select Child</label>
                    <select name="child_id" id="child_id" required>
                        <option value="">Choose a child</option>
                        <?php while ($c = mysqli_fetch_assoc($children)): ?>
                            <option value="<?= (int)$c["id"] ?>">
                                <?= h($c["child_name"]) ?> — Parent: <?= h($c["parent_name"]) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <button type="submit">Assign Student</button>
        </form>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Current Teacher Assignments</h2>
    <table>
        <tr>
            <th>Teacher</th><th>Child</th><th>Parent</th><th>Assigned At</th>
        </tr>
        <?php if ($assignments && mysqli_num_rows($assignments) > 0): ?>
            <?php while ($a = mysqli_fetch_assoc($assignments)): ?>
                <tr>
                    <td><?= h($a["teacher_name"]) ?></td>
                    <td><?= h($a["child_name"]) ?></td>
                    <td><?= h($a["parent_name"]) ?></td>
                    <td><?= h($a["assigned_at"]) ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="4">No student assignments yet.</td></tr>
        <?php endif; ?>
    </table>
</div>

<div class="card">
    <h2>Registered Users</h2>
    <table>
        <tr><th>Name</th><th>Email</th><th>Role</th><th>Created</th></tr>
        <?php while ($u = mysqli_fetch_assoc($users)): ?>
            <tr>
                <td><?= h($u["name"]) ?></td>
                <td><?= h($u["email"]) ?></td>
                <td><?= h($u["role"]) ?></td>
                <td><?= h($u["created_at"]) ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>

<div class="card">
    <h2>Assessment Records</h2>
    <table>
        <tr>
            <th>Child</th><th>Type</th><th>Score</th><th>Assessed By</th><th>Remarks / Summary</th><th>Date</th>
        </tr>
        <?php while ($a = mysqli_fetch_assoc($assessments)): ?>
            <tr>
                <td><?= h($a["child_name"]) ?></td>
                <td><?= h($a["assessment_type"]) ?></td>
                <td><?= h($a["score"]) ?></td>
                <td><?= h($a["assessor_name"] ?? "Not specified") ?></td>
                <td><?= h($a["summary"]) ?></td>
                <td><?= h($a["created_at"]) ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>

</main>
</body>
</html>
