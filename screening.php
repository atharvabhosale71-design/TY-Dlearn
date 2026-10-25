
<?php
session_start();
require_once "db_connect.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["user_role"] !== "parent") {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION["user_id"];
$message = "";
$result_text = "";
$score = 0;

$stmt = mysqli_prepare($conn, "SELECT id, child_name FROM children WHERE parent_id = ? ORDER BY child_name");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$children = mysqli_stmt_get_result($stmt);

$child_list = [];
while ($row = mysqli_fetch_assoc($children)) {
    $child_list[] = $row;
}
mysqli_stmt_close($stmt);

$questions = [
    "Does the child have difficulty recognizing letters?",
    "Does the child confuse similar-looking letters such as b and d?",
    "Does the child find it difficult to connect letters with their sounds?",
    "Does the child struggle to read simple words?",
    "Does the child frequently make spelling mistakes?",
    "Does the child find copying words or sentences difficult?",
    "Does the child need extra time to complete reading tasks?",
    "Does the child have difficulty remembering written instructions?",
    "Does the child avoid reading activities?",
    "Does the child need additional support with reading and writing?"
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $child_id = filter_input(INPUT_POST, "child_id", FILTER_VALIDATE_INT);
    $answers = $_POST["answers"] ?? [];
    $valid = $child_id && count($answers) === count($questions);

    if ($valid) {
        foreach ($answers as $answer) {
            if (!in_array((string)$answer, ["0", "1", "2"], true)) {
                $valid = false;
                break;
            }
        }
    }

    $owned = false;
    if ($valid) {
        $check = mysqli_prepare($conn, "SELECT id FROM children WHERE id = ? AND parent_id = ?");
        mysqli_stmt_bind_param($check, "ii", $child_id, $user_id);
        mysqli_stmt_execute($check);
        $owned = mysqli_stmt_get_result($check)->num_rows > 0;
        mysqli_stmt_close($check);
    }

    if (!$valid || !$owned) {
        $message = "Please select your child and answer every question.";
    } else {
        $score = array_sum(array_map("intval", $answers));
        $percentage = round(($score / (count($questions) * 2)) * 100);

        if ($score <= 6) {
            $result_text = "Few reported difficulties. Continue supporting the child's learning and monitor progress.";
        } elseif ($score <= 13) {
            $result_text = "Some learning difficulties were reported. Consider discussing these observations with the child's teacher.";
        } else {
            $result_text = "Several learning difficulties were reported. Consider consulting a qualified educational specialist for further assessment.";
        }

        $answer_json = json_encode(array_values($answers));
        $summary = "Educational screening only; not a diagnosis. " . $result_text;

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO screening_assessments
             (child_id, assessed_by, assessment_type, answers, score, summary)
             VALUES (?, ?, 'parent', ?, ?, ?)"
        );
        mysqli_stmt_bind_param(
            $stmt, "iisis", $child_id, $user_id, $answer_json, $percentage, $summary
        );

        if (mysqli_stmt_execute($stmt)) {
            $message = "Screening saved successfully!";
        } else {
            $message = "Could not save the screening. Please try again.";
        }
        mysqli_stmt_close($stmt);
    }
}

$history_stmt = mysqli_prepare(
    $conn,
    "SELECT s.score, s.summary, s.created_at, c.child_name
     FROM screening_assessments s
     JOIN children c ON c.id = s.child_id
     WHERE c.parent_id = ? AND s.assessment_type = 'parent'
     ORDER BY s.id DESC LIMIT 10"
);
mysqli_stmt_bind_param($history_stmt, "i", $user_id);
mysqli_stmt_execute($history_stmt);
$history = mysqli_stmt_get_result($history_stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>DLearn Screening</title>
<link rel="stylesheet" href="assets/css/screening.css">
</head>
<body>
<header>
<h2>DLearn — Screening</h2>
<a href="dashboard.php">← Dashboard</a>
</header>
<main>
<div class="card">
<h2>Parent Screening Questionnaire</h2>
<p>This questionnaire records learning observations. It does not diagnose dyslexia.</p>

<?php if ($message): ?>
<p class="notice"><?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?></p>
<?php endif; ?>

<?php if ($result_text): ?>
<div class="notice">
<h3>Screening Summary</h3>
<p>Observation score: <?= (int)$score ?> / 20</p>
<p><?= htmlspecialchars($result_text, ENT_QUOTES, "UTF-8") ?></p>
<p>Please interpret these responses with a teacher or qualified specialist. This score is not a validated diagnostic threshold.</p>
</div>
<?php endif; ?>

<?php if (count($child_list) === 0): ?>
<p>Please add a child profile before starting screening.</p>
<a href="child_profile.php">Add Child Profile</a>
<?php else: ?>
<form method="POST">
<label for="child_id"><strong>Select Child</strong></label>
<select id="child_id" name="child_id" required>
<option value="">Choose child</option>
<?php foreach ($child_list as $child): ?>
<option value="<?= (int)$child["id"] ?>"><?= htmlspecialchars($child["child_name"], ENT_QUOTES, "UTF-8") ?></option>
<?php endforeach; ?>
</select>

<?php foreach ($questions as $i => $question): ?>
<div class="question">
<strong><?= ($i + 1) . ". " . htmlspecialchars($question, ENT_QUOTES, "UTF-8") ?></strong>
<?php foreach ([0 => "Never / Not observed", 1 => "Sometimes", 2 => "Often"] as $value => $label): ?>
<label>
<input type="radio" name="answers[<?= $i ?>]" value="<?= $value ?>" required>
<?= htmlspecialchars($label, ENT_QUOTES, "UTF-8") ?>
</label>
<?php endforeach; ?>
</div>
<?php endforeach; ?>

<button type="submit">Submit Screening</button>
</form>
<?php endif; ?>
</div>

<div class="card">
<h2>Previous Screening Records</h2>
<?php if (mysqli_num_rows($history) === 0): ?>
<p>No screening records yet.</p>
<?php else: ?>
<?php while ($record = mysqli_fetch_assoc($history)): ?>
<div class="question">
<strong><?= htmlspecialchars($record["child_name"], ENT_QUOTES, "UTF-8") ?></strong>
<p>Recorded score: <?= (int)$record["score"] ?>%</p>
<p><?= htmlspecialchars($record["summary"], ENT_QUOTES, "UTF-8") ?></p>
<small><?= htmlspecialchars($record["created_at"], ENT_QUOTES, "UTF-8") ?></small>
</div>
<?php endwhile; ?>
<?php endif; ?>
</div>
</main>
</body>
</html>
