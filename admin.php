```php
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

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    ($_POST["action"] ?? "") === "assign_student"
) {
    $teacherId = filter_input(INPUT_POST, "teacher_id", FILTER_VALIDATE_INT);
    $childId = filter_input(INPUT_POST, "child_id", FILTER_VALIDATE_INT);

    if (!$teacherId || !$childId) {
        $message = "Please select both a teacher and a child.";
        $messageType = "error";
    } else {
        $teacherCheck = mysqli_prepare(
            $conn,
            "SELECT id FROM users WHERE id = ? AND role = 'teacher' LIMIT 1"
        );
        mysqli_stmt_bind_param($teacherCheck, "i", $teacherId);
        mysqli_stmt_execute($teacherCheck);
        $teacherResult = mysqli_stmt_get_result($teacherCheck);
        $validTeacher = mysqli_num_rows($teacherResult) === 1;
        mysqli_stmt_close($teacherCheck);

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
    "SELECT id, name, email FROM users
     WHERE role = 'teacher' ORDER BY name"
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
     FROM users ORDER BY id DESC LIMIT 50"
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
<title>DLearn | Admin Dashboard</title>

<style>
:root {
    --red: #e11d48;
    --dark-red: #9f1239;
    --pink: #fb4775;
    --navy: #17152f;
    --text: #24223b;
    --muted: #77768d;
    --border: #eee7ed;
    --white: #ffffff;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: "Segoe UI", Arial, sans-serif;
    background: #f7f4f8;
    color: var(--text);
    min-height: 100vh;
}

/* Top header */
header {
    background: linear-gradient(110deg, #991b3b, #e11d48 65%, #fb4775);
    color: white;
    padding: 23px 5%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 18px;
    flex-wrap: wrap;
    box-shadow: 0 6px 22px rgba(159, 18, 57, .25);
}

.brand {
    display: flex;
    align-items: center;
    gap: 13px;
}

.brand-icon {
    width: 48px;
    height: 48px;
    border-radius: 15px;
    display: grid;
    place-items: center;
    background: rgba(255,255,255,.2);
    border: 1px solid rgba(255,255,255,.35);
    font-size: 25px;
    font-weight: 900;
}

header h2 {
    font-size: 23px;
    font-weight: 800;
    letter-spacing: .2px;
}

.header-subtitle {
    color: #ffe2e9;
    font-size: 12px;
    margin-top: 4px;
}

.header-links {
    display: flex;
    gap: 11px;
    flex-wrap: wrap;
}

.header-links a {
    color: white;
    text-decoration: none;
    padding: 10px 16px;
    border-radius: 10px;
    font-weight: 700;
    font-size: 13px;
    border: 1px solid rgba(255,255,255,.4);
    background: rgba(255,255,255,.12);
    transition: .2s;
}

.header-links a:hover {
    background: white;
    color: var(--dark-red);
    transform: translateY(-2px);
}

/* Main content */
main {
    max-width: 1500px;
    margin: 0 auto;
    padding: 34px 5% 55px;
}

.page-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    flex-wrap: wrap;
    margin-bottom: 25px;
}

.page-heading h1 {
    font-size: 29px;
    font-weight: 850;
    color: var(--navy);
}

.page-heading p {
    color: var(--muted);
    font-size: 14px;
    margin-top: 7px;
}

.admin-badge {
    background: #ffe1e8;
    color: #a20f39;
    border: 1px solid #ffb7c8;
    border-radius: 30px;
    padding: 10px 16px;
    font-size: 12px;
    font-weight: 800;
    letter-spacing: .6px;
}

/* Status messages */
.message {
    padding: 15px 18px;
    margin-bottom: 23px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 700;
}

.message.success {
    color: #075e42;
    background: #d5f8e8;
    border-left: 5px solid #059669;
}

.message.error {
    color: #991b1b;
    background: #ffe0e0;
    border-left: 5px solid #dc2626;
}

/* Statistics cards */
.cards {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 22px;
    margin-bottom: 28px;
}

.stat-card {
    position: relative;
    overflow: hidden;
    color: white;
    padding: 25px;
    border-radius: 19px;
    min-height: 160px;
    box-shadow: 0 10px 25px rgba(31, 25, 55, .13);
    transition: transform .25s, box-shadow .25s;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(31, 25, 55, .2);
}

.stat-card.users {
    background: linear-gradient(135deg, #4338ca, #7c3aed);
}

.stat-card.children {
    background: linear-gradient(135deg, #be123c, #fb4775);
}

.stat-card.assessments {
    background: linear-gradient(135deg, #047857, #10b981);
}

.stat-card::after {
    content: "";
    position: absolute;
    width: 130px;
    height: 130px;
    border-radius: 50%;
    background: rgba(255,255,255,.1);
    right: -32px;
    top: -36px;
}

.stat-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    position: relative;
    z-index: 1;
}

.stat-card h3 {
    font-size: 14px;
    font-weight: 700;
    color: rgba(255,255,255,.92);
}

.stat-icon {
    width: 43px;
    height: 43px;
    border-radius: 13px;
    display: grid;
    place-items: center;
    background: rgba(255,255,255,.19);
    font-size: 22px;
}

.stat-number {
    position: relative;
    z-index: 1;
    font-size: 39px;
    line-height: 1.2;
    margin-top: 15px;
    font-weight: 900;
}

/* Content sections */
.card {
    background: white;
    border: 1px solid var(--border);
    border-radius: 18px;
    padding: 26px;
    margin-bottom: 25px;
    box-shadow: 0 5px 18px rgba(42, 23, 51, .045);
    min-width: 0;
}

.section-heading {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 10px;
}

.section-icon {
    width: 42px;
    height: 42px;
    flex-shrink: 0;
    display: grid;
    place-items: center;
    color: white;
    background: linear-gradient(135deg, #be123c, #fb4775);
    border-radius: 12px;
    font-size: 21px;
}

.card h2 {
    color: var(--navy);
    font-size: 20px;
    font-weight: 800;
}

.card > p {
    font-size: 13px;
    color: var(--muted);
    line-height: 1.7;
    margin: 10px 0 20px;
}

.helper-text {
    background: #fff1f4;
    border: 1px solid #ffd0db;
    color: #8d1736;
    padding: 12px 15px;
    border-radius: 10px;
    font-size: 13px;
    margin: 17px 0;
    line-height: 1.6;
}

/* Assignment form */
.form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 20px;
    margin: 20px 0;
}

label {
    display: block;
    font-size: 13px;
    color: #38344b;
    font-weight: 800;
    margin-bottom: 9px;
}

select {
    width: 100%;
    min-height: 48px;
    padding: 12px 14px;
    border: 1.5px solid #ddd4df;
    border-radius: 11px;
    background: #fff;
    color: #29243a;
    font-family: inherit;
    font-size: 14px;
    outline: none;
    transition: border .2s, box-shadow .2s;
}

select:focus {
    border-color: var(--red);
    box-shadow: 0 0 0 4px rgba(225,29,72,.12);
}

button {
    border: none;
    border-radius: 11px;
    padding: 14px 22px;
    background: linear-gradient(110deg, #be123c, #f02f68);
    color: white;
    font-family: inherit;
    font-size: 14px;
    font-weight: 800;
    cursor: pointer;
    box-shadow: 0 6px 14px rgba(225,29,72,.22);
    transition: .2s;
}

button:hover {
    transform: translateY(-2px);
    box-shadow: 0 9px 19px rgba(225,29,72,.32);
}

code {
    background: #ffe6ed;
    color: #9f1239;
    padding: 3px 7px;
    border-radius: 5px;
}

/* Tables */
.table-wrap {
    width: 100%;
    overflow-x: auto;
    margin-top: 20px;
    border: 1px solid #eee5ed;
    border-radius: 12px;
}

table {
    width: 100%;
    min-width: 650px;
    border-collapse: collapse;
    font-size: 13px;
    text-align: left;
}

th {
    padding: 16px 17px;
    color: white;
    background: #28213e;
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .5px;
    white-space: nowrap;
}

td {
    padding: 15px 17px;
    border-bottom: 1px solid #f0eaf0;
    color: #454056;
    vertical-align: top;
    line-height: 1.6;
}

tbody tr:nth-child(even) {
    background: #fff8fa;
}

tbody tr:hover {
    background: #ffeaf0;
}

tbody tr:last-child td {
    border-bottom: none;
}

.empty-row {
    text-align: center;
    padding: 25px;
    color: #777187;
    font-weight: 600;
}

/* Role labels */
.role {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
}

.role.admin {
    background: #e8e2ff;
    color: #5b21b6;
}

.role.parent {
    background: #d9f5ff;
    color: #075985;
}

.role.teacher {
    background: #d5f8e8;
    color: #065f46;
}

.role.other {
    background: #f0eaf0;
    color: #51475c;
}

.footer {
    text-align: center;
    color: #81788b;
    font-size: 12px;
    padding: 10px 0 0;
}

@media (max-width: 850px) {
    main {
        padding: 27px 4% 40px;
    }

    .cards {
        grid-template-columns: 1fr;
        gap: 15px;
    }

    .stat-card {
        min-height: 135px;
    }

    .form-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }

    .card {
        padding: 20px;
    }
}

@media (max-width: 520px) {
    header {
        padding: 18px 4%;
    }

    header h2 {
        font-size: 18px;
    }

    .brand-icon {
        width: 41px;
        height: 41px;
    }

    .header-links {
        width: 100%;
    }

    .header-links a {
        text-align: center;
        flex: 1;
    }

    .page-heading h1 {
        font-size: 24px;
    }

    .card h2 {
        font-size: 17px;
    }

    .card {
        padding: 16px;
    }

    button {
        width: 100%;
    }
}
</style>
</head>

<body>

<header>
    <div class="brand">
        <div class="brand-icon">D</div>
        <div>
            <h2>DLearn Admin</h2>
            <div class="header-subtitle">Screening & Learning Support Platform</div>
        </div>
    </div>

    <nav class="header-links">
        <a href="dashboard.php">← Dashboard</a>
        <a href="logout.php">Logout ↗</a>
    </nav>
</header>

<main>

    <div class="page-heading">
        <div>
            <h1>Admin Overview</h1>
            <p>Manage users, child profiles, teacher assignments and assessments.</p>
        </div>
        <span class="admin-badge">● ADMIN PANEL</span>
    </div>

    <?php if ($message !== ""): ?>
        <div class="message <?= h($messageType) ?>">
            <?= h($message) ?>
        </div>
    <?php endif; ?>

    <!-- Dashboard statistics -->
    <div class="cards">
        <div class="stat-card users">
            <div class="stat-top">
                <h3>Total Registered Users</h3>
                <div class="stat-icon">👥</div>
            </div>
            <div class="stat-number"><?= $totalUsers ?></div>
        </div>

        <div class="stat-card children">
            <div class="stat-top">
                <h3>Child Profiles</h3>
                <div class="stat-icon">🧒</div>
            </div>
            <div class="stat-number"><?= $totalChildren ?></div>
        </div>

        <div class="stat-card assessments">
            <div class="stat-top">
                <h3>Total Assessments</h3>
                <div class="stat-icon">📊</div>
            </div>
            <div class="stat-number"><?= $totalAssessments ?></div>
        </div>
    </div>

    <!-- Assign teacher -->
    <section class="card">
        <div class="section-heading">
            <div class="section-icon">👩‍🏫</div>
            <h2>Assign Student to Teacher</h2>
        </div>

        <p>Select a teacher and a child. Teachers will only be able to assess children assigned to them.</p>

        <?php if (!$teachers || mysqli_num_rows($teachers) === 0): ?>
            <div class="helper-text">
                No teacher accounts found. Create a user with the
                <code>teacher</code> role first.
            </div>

        <?php elseif (!$children || mysqli_num_rows($children) === 0): ?>
            <div class="helper-text">
                No child profiles found. A parent must add a child first.
            </div>

        <?php else: ?>
            <?php
            mysqli_data_seek($teachers, 0);
            mysqli_data_seek($children, 0);
            ?>

            <form method="POST">
                <input type="hidden" name="action" value="assign_student">

                <div class="form-grid">
                    <div>
                        <label for="teacher_id">SELECT TEACHER</label>
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
                        <label for="child_id">SELECT CHILD</label>
                        <select name="child_id" id="child_id" required>
                            <option value="">Choose a child</option>
                            <?php while ($c = mysqli_fetch_assoc($children)): ?>
                                <option value="<?= (int)$c["id"] ?>">
                                    <?= h($c["child_name"]) ?> — Parent:
                                    <?= h($c["parent_name"]) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <button type="submit">＋ Assign Student</button>
            </form>
        <?php endif; ?>
    </section>

    <!-- Current assignments -->
    <section class="card">
        <div class="section-heading">
            <div class="section-icon">📋</div>
            <h2>Current Teacher Assignments</h2>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Teacher</th>
                        <th>Child</th>
                        <th>Parent</th>
                        <th>Assigned At</th>
                    </tr>
                </thead>
                <tbody>
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
                    <tr>
                        <td colspan="4" class="empty-row">
                            No student assignments yet.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Registered users -->
    <section class="card">
        <div class="section-heading">
            <div class="section-icon">👥</div>
            <h2>Registered Users</h2>
        </div>
        <p>Latest 50 registered user accounts.</p>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($users && mysqli_num_rows($users) > 0): ?>
                    <?php while ($u = mysqli_fetch_assoc($users)): ?>
                        <?php
                        $roleClass = in_array(
                            strtolower((string)$u["role"]),
                            ["admin", "parent", "teacher"],
                            true
                        ) ? strtolower((string)$u["role"]) : "other";
                        ?>
                        <tr>
                            <td><?= h($u["name"]) ?></td>
                            <td><?= h($u["email"]) ?></td>
                            <td>
                                <span class="role <?= h($roleClass) ?>">
                                    <?= h($u["role"]) ?>
                                </span>
                            </td>
                            <td><?= h($u["created_at"]) ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="empty-row">No users found.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Assessment records -->
    <section class="card">
        <div class="section-heading">
            <div class="section-icon">📈</div>
            <h2>Assessment Records</h2>
        </div>
        <p>Latest 50 child screening and assessment records.</p>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Child</th>
                        <th>Type</th>
                        <th>Score</th>
                        <th>Assessed By</th>
                        <th>Remarks / Summary</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($assessments && mysqli_num_rows($assessments) > 0): ?>
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
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="empty-row">
                            No assessment records found.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <div class="footer">
        © <?= date("Y") ?> DLearn — Admin Dashboard
    </div>

</main>
</body>
</html>
```