
<?php
session_start();
require_once "db_connect.php";

if (
    !isset($_SESSION["user_id"], $_SESSION["user_role"]) ||
    $_SESSION["user_role"] !== "teacher"
) {
    http_response_code(403);
    exit("Teacher access required. Please sign in with a teacher account.");
}

$teacherId = (int)$_SESSION["user_id"];
$message = "";
$messageType = "success";

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

$questions = [
    "Recognises common letters",
    "Connects letters with their sounds",
    "Reads age-appropriate words",
    "Spells familiar words",
    "Follows spoken instructions"
];

/* Save assessment only for a child assigned to this teacher. */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $childId = filter_input(INPUT_POST, "child_id", FILTER_VALIDATE_INT);
    $submittedAnswers = $_POST["answers"] ?? [];
    $remarks = trim($_POST["remarks"] ?? "");

    $valid = $childId !== false && $childId !== null && $childId > 0
        && is_array($submittedAnswers)
        && count($submittedAnswers) === count($questions)
        && mb_strlen($remarks) <= 2000;

    $answers = [];

    if ($valid) {
        for ($i = 0; $i < count($questions); $i++) {
            if (
                !array_key_exists($i, $submittedAnswers) ||
                !in_array((string)$submittedAnswers[$i], ["0", "1", "2"], true)
            ) {
                $valid = false;
                break;
            }

            $answers[] = (int)$submittedAnswers[$i];
        }
    }

    // Crucial security check: child must be assigned to this teacher.
    if ($valid) {
        $check = mysqli_prepare(
            $conn,
            "SELECT c.id
             FROM teacher_assignments ta
             JOIN children c ON c.id = ta.child_id
             WHERE ta.teacher_id = ? AND ta.child_id = ?
             LIMIT 1"
        );

        mysqli_stmt_bind_param($check, "ii", $teacherId, $childId);
        mysqli_stmt_execute($check);
        $assignmentResult = mysqli_stmt_get_result($check);
        $isAssigned = mysqli_num_rows($assignmentResult) === 1;
        mysqli_stmt_close($check);

        if (!$isAssigned) {
            $valid = false;
            $message = "You can assess only students assigned to your account.";
            $messageType = "error";
        }
    }

    if (!$valid && $message === "") {
        $message = "Please select an assigned child and answer every question. Remarks must be under 2000 characters.";
        $messageType = "error";
    }

    if ($valid) {
        $score = array_sum($answers);
        $answersJson = json_encode(
            $answers,
            JSON_UNESCAPED_UNICODE
        );

        $summary = "Teacher assessment: " . $score . "/10 marks. "
            . "Educational observations only; not a diagnosis.";

        if ($remarks !== "") {
            $summary .= " Teacher remarks: " . $remarks;
        }

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO screening_assessments
             (child_id, assessed_by, assessment_type, answers, score, summary)
             VALUES (?, ?, 'teacher', ?, ?, ?)"
        );

        if ($stmt) {
            mysqli_stmt_bind_param(
                $stmt,
                "iisis",
                $childId,
                $teacherId,
                $answersJson,
                $score,
                $summary
            );

            if (mysqli_stmt_execute($stmt)) {
                $message = "Assessment saved successfully. Marks: "
                    . $score . "/10.";
                $messageType = "success";
            } else {
                $message = "Unable to save the assessment. Please try again.";
                $messageType = "error";
            }

            mysqli_stmt_close($stmt);
        } else {
            $message = "Unable to prepare the assessment record.";
            $messageType = "error";
        }
    }
}

/* Fetch only this teacher's assigned children. */
$childrenStmt = mysqli_prepare(
    $conn,
    "SELECT c.id, c.child_name, p.name AS parent_name
     FROM teacher_assignments ta
     JOIN children c ON c.id = ta.child_id
     JOIN users p ON p.id = c.parent_id
     WHERE ta.teacher_id = ?
     ORDER BY c.child_name"
);

mysqli_stmt_bind_param($childrenStmt, "i", $teacherId);
mysqli_stmt_execute($childrenStmt);
$children = mysqli_stmt_get_result($childrenStmt);

/* Recent assessments entered by this teacher. */
$recordsStmt = mysqli_prepare(
    $conn,
    "SELECT s.id, c.child_name, s.score, s.summary, s.created_at
     FROM screening_assessments s
     JOIN children c ON c.id = s.child_id
     JOIN teacher_assignments ta
       ON ta.child_id = s.child_id
      AND ta.teacher_id = s.assessed_by
     WHERE s.assessed_by = ?
       AND s.assessment_type = 'teacher'
     ORDER BY s.created_at DESC
     LIMIT 30"
);

mysqli_stmt_bind_param($recordsStmt, "i", $teacherId);
mysqli_stmt_execute($recordsStmt);
$records = mysqli_stmt_get_result($recordsStmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Teacher Assessment | DLearn</title>
<style>
*{box-sizing:border-box}
body{font-family:Arial,sans-serif;background:#f3f6fc;color:#24304d;margin:0}
header{background:#4056a1;color:white;padding:20px 6%;display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap}
header a{color:white}
main{max-width:900px;margin:25px auto;padding:0 16px}
.card{background:white;padding:24px;border-radius:12px;margin-bottom:20px;box-shadow:0 3px 12px #0000000d;overflow-x:auto}
select,textarea,button{font:inherit;padding:12px;border-radius:6px}
select,textarea{width:100%;border:1px solid #ccd2e0;margin:8px 0 16px}
button{width:100%;border:0;background:#4056a1;color:white;cursor:pointer;margin-top:14px}
button:hover{background:#293b70}
.question{padding:16px 0;border-bottom:1px solid #e1e5ef}
.option{display:block;margin:12px 0}
.message{padding:12px;border-radius:6px;margin:12px 0}
.success{background:#e4f7e9;color:#17652b}
.error{background:#ffe8e8;color:#922626}
table{border-collapse:collapse;width:100%}
th,td{padding:10px;border:1px solid #ddd;text-align:left;overflow-wrap:anywhere}
th{background:#e9edfa}
small{color:#65708a}
</style>
</head>
<body>
<header>
    <h2>Teacher Assessment</h2>
    <nav>
        <a href="dashboard.php">Dashboard</a> |
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main>
    <div class="card">
        <h1>Assess Assigned Students</h1>
        <p>Check each student's learning skills, award marks and add remarks for the parent.</p>

        <?php if ($message !== ""): ?>
            <div class="message <?= h($messageType) ?>">
                <?= h($message) ?>
            </div>
        <?php endif; ?>

        <?php if (mysqli_num_rows($children) === 0): ?>
            <p>No students are assigned to you yet. Please contact the administrator.</p>
        <?php else: ?>
            <form method="POST" action="teacher_assessment.php">
                <label for="child_id"><strong>Select Assigned Child</strong></label>
                <select name="child_id" id="child_id" required>
                    <option value="">Choose a child</option>
                    <?php while ($child = mysqli_fetch_assoc($children)): ?>
                        <option value="<?= (int)$child["id"] ?>">
                            <?= h($child["child_name"]) ?>
                            — Parent: <?= h($child["parent_name"]) ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <p><strong>Marking:</strong> 0 = Needs support, 1 = Developing, 2 = Usually demonstrates.</p>

                <?php foreach ($questions as $i => $question): ?>
                    <div class="question">
                        <strong>
                            <?= ($i + 1) . ". " . h($question) ?>
                        </strong>

                        <?php
                        $labels = [
                            0 => "Needs support (0 marks)",
                            1 => "Developing (1 mark)",
                            2 => "Usually demonstrates (2 marks)"
                        ];
                        ?>

                        <?php foreach ($labels as $value => $label): ?>
                            <label class="option">
                                <input
                                    type="radio"
                                    name="answers[<?= $i ?>]"
                                    value="<?= $value ?>"
                                    required
                                >
                                <?= h($label) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>

                <label for="remarks"><strong>Teacher Remarks</strong></label>
                <textarea
                    name="remarks"
                    id="remarks"
                    rows="4"
                    maxlength="2000"
                    placeholder="Enter observations or recommendations for the parent..."
                ></textarea>

                <button type="submit">Save Assessment and Marks</button>
            </form>
        <?php endif; ?>

        <p><small>DLearn provides educational screening and support; these marks do not diagnose dyslexia.</small></p>
    </div>

    <div class="card">
        <h2>My Recent Assessments</h2>
        <table>
            <tr>
                <th>Child</th>
                <th>Marks</th>
                <th>Remarks / Summary</th>
                <th>Date</th>
            </tr>

            <?php if (mysqli_num_rows($records) > 0): ?>
                <?php while ($record = mysqli_fetch_assoc($records)): ?>
                    <tr>
                        <td><?= h($record["child_name"]) ?></td>
                        <td><?= h($record["score"]) ?>/10</td>
                        <td><?= h($record["summary"]) ?></td>
                        <td><?= h($record["created_at"]) ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="4">No teacher assessments recorded yet.</td></tr>
            <?php endif; ?>
        </table>
    </div>
</main>
</body>
</html>
<?php
mysqli_stmt_close($childrenStmt);
mysqli_stmt_close($recordsStmt);
?>
