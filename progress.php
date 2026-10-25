
<?php
session_start();
require_once "db_connect.php";

if (!isset($_SESSION["user_id"]) || ($_SESSION["user_role"] ?? "") !== "parent") {
    header("Location: login.php");
    exit;
}

$parentId = (int) $_SESSION["user_id"];

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

/* Fetch this parent's children */
$stmt = mysqli_prepare($conn,
    "SELECT id, child_name FROM children WHERE parent_id = ? ORDER BY child_name");
mysqli_stmt_bind_param($stmt, "i", $parentId);
mysqli_stmt_execute($stmt);
$children = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

/* Parent screening history */
$stmt = mysqli_prepare($conn,
    "SELECT c.child_name, s.score, s.created_at
     FROM screening_assessments s
     INNER JOIN children c ON c.id = s.child_id
     WHERE c.parent_id = ? AND s.assessment_type = 'parent'
     ORDER BY s.created_at ASC");
mysqli_stmt_bind_param($stmt, "i", $parentId);
mysqli_stmt_execute($stmt);
$screenings = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

/* Learning activity history */
$stmt = mysqli_prepare($conn,
    "SELECT c.child_name, r.activity_key, r.score,
            r.total_questions, r.completed_at
     FROM learning_activity_results r
     INNER JOIN children c ON c.id = r.child_id
     WHERE r.parent_id = ? AND c.parent_id = ?
     ORDER BY r.completed_at ASC");
mysqli_stmt_bind_param($stmt, "ii", $parentId, $parentId);
mysqli_stmt_execute($stmt);
$activities = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$activityNames = [
    "letter-match" => "Letter Match",
    "picture-match" => "Picture Match",
    "word-builder" => "Word Builder",
    "sentence-time" => "Sentence Time"
];

$completedCount = count($activities);
$totalPercent = 0;

foreach ($activities as $activity) {
    if ((int)$activity["total_questions"] > 0) {
        $totalPercent +=
            ((int)$activity["score"] / (int)$activity["total_questions"]) * 100;
    }
}

$averageScore = $completedCount > 0
    ? round($totalPercent / $completedCount)
    : 0;

/* Prepare chart data */
$screeningLabels = [];
$screeningScores = [];

foreach ($screenings as $item) {
    $screeningLabels[] = $item["child_name"] . " · " .
        date("d M Y", strtotime($item["created_at"]));
    $screeningScores[] = (float)$item["score"];
}

$learningLabels = [];
$learningScores = [];

foreach ($activities as $item) {
    $learningLabels[] =
        $item["child_name"] . " · " .
        ($activityNames[$item["activity_key"]] ?? "Activity") . " · " .
        date("d M Y", strtotime($item["completed_at"]));

    $total = (int)$item["total_questions"];
    $learningScores[] = $total > 0
        ? round(((int)$item["score"] / $total) * 100, 1)
        : 0;
}

$screeningLabelsJson = json_encode(
    $screeningLabels,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
);
$screeningScoresJson = json_encode($screeningScores);
$learningLabelsJson = json_encode(
    $learningLabels,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
);
$learningScoresJson = json_encode($learningScores);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Progress Tracking | DLearn</title>
<link rel="stylesheet" href="assets/css/progress.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<header class="topbar">
    <a class="brand" href="dashboard.php">
        <span class="brand-mark">D</span> DLearn
    </a>
    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="learning.php">Learning Area</a>
        <a href="report.php">Reports</a>
    </nav>
</header>

<main class="container">

    <section class="page-hero">
        <span class="eyebrow">YOUR LEARNING JOURNEY</span>
        <h1>Progress Tracking</h1>
        <p>Celebrate practice, review learning activity results and follow recorded screening history.</p>
    </section>

    <section class="stats-grid">
        <article class="stat-card stat-purple">
            <span class="stat-label">Activities completed</span>
            <strong><?= $completedCount ?></strong>
            <span class="stat-note">Saved learning sessions</span>
        </article>

        <article class="stat-card stat-teal">
            <span class="stat-label">Average activity score</span>
            <strong><?= $completedCount > 0 ? $averageScore . "%" : "—" ?></strong>
            <span class="stat-note">Across completed activities</span>
        </article>

        <article class="stat-card stat-orange">
            <span class="stat-label">Screening records</span>
            <strong><?= count($screenings) ?></strong>
            <span class="stat-note">Parent observations saved</span>
        </article>
    </section>

    <section class="panel">
        <div class="panel-heading">
            <div>
                <span class="eyebrow">PARENT OBSERVATIONS</span>
                <h2>Screening Score History</h2>
            </div>
            <span class="panel-icon purple-icon">↗</span>
        </div>

        <p class="panel-description">
            This chart shows recorded parent screening scores over time.
            It is not a medical diagnosis or a measure of learning improvement.
        </p>

        <?php if (count($screenings) === 0): ?>
            <div class="empty-state">
                No screening records yet. Complete a parent screening to see the history here.
            </div>
        <?php else: ?>
            <div class="chart-container">
                <canvas id="screeningChart"></canvas>
            </div>
        <?php endif; ?>
    </section>

    <section class="panel">
        <div class="panel-heading">
            <div>
                <span class="eyebrow">PRACTICE & LEARNING</span>
                <h2>Learning Activity Scores</h2>
            </div>
            <span class="panel-icon teal-icon">✦</span>
        </div>

        <p class="panel-description">
            Each result shows the percentage of questions answered correctly in that activity.
        </p>

        <?php if (count($activities) === 0): ?>
            <div class="empty-state">
                No learning results yet. Open the Learning Area, complete an activity and save the result.
            </div>
        <?php else: ?>
            <div class="chart-container">
                <canvas id="learningChart"></canvas>
            </div>
        <?php endif; ?>
    </section>

    <section class="panel">
        <div class="panel-heading">
            <div>
                <span class="eyebrow">RECENT RESULTS</span>
                <h2>Completed Activities</h2>
            </div>
        </div>

        <?php if (count($activities) === 0): ?>
            <div class="empty-state">Your completed activities will appear here.</div>
        <?php else: ?>
            <div class="results-list">
                <?php foreach (array_reverse($activities) as $item): ?>
                    <?php
                    $total = (int)$item["total_questions"];
                    $score = (int)$item["score"];
                    $percent = $total > 0 ? round(($score / $total) * 100) : 0;
                    ?>
                    <article class="result-row">
                        <div class="result-icon">✓</div>
                        <div class="result-info">
                            <strong><?= h($item["child_name"]) ?> — 
                                <?= h($activityNames[$item["activity_key"]] ?? "Learning Activity") ?>
                            </strong>
                            <span><?= h(date("d M Y, g:i A", strtotime($item["completed_at"]))) ?></span>
                            <div class="mini-progress">
                                <span style="width: <?= $percent ?>%"></span>
                            </div>
                        </div>
                        <strong class="result-score"><?= $score ?>/<?= $total ?></strong>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <p class="disclaimer">
        DLearn supports educational practice and parent observation. Screening scores
        and activity results do not diagnose dyslexia or replace a qualified assessment.
    </p>
</main>

<script>
const screeningLabels = <?= $screeningLabelsJson ?: "[]" ?>;
const screeningScores = <?= $screeningScoresJson ?: "[]" ?>;
const learningLabels = <?= $learningLabelsJson ?: "[]" ?>;
const learningScores = <?= $learningScoresJson ?: "[]" ?>;

function createProgressChart(canvasId, labels, scores, datasetLabel, color) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;

    new Chart(canvas, {
        type: "line",
        data: {
            labels: labels,
            datasets: [{
                label: datasetLabel,
                data: scores,
                borderColor: color,
                backgroundColor: color + "22",
                fill: true,
                tension: 0.3,
                pointRadius: 5,
                pointHoverRadius: 7,
                pointBackgroundColor: color,
                pointBorderColor: "#ffffff",
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: "index"
            },
            plugins: {
                legend: {
                    display: true,
                    labels: {
                        usePointStyle: true,
                        boxWidth: 8
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ": " + context.parsed.y + "%";
                        }
                    }
                }
            },
            scales: {
                y: {
                    min: 0,
                    max: 100,
                    ticks: {
                        callback: value => value + "%"
                    },
                    grid: {
                        color: "#eeeeF7"
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        maxRotation: 45,
                        minRotation: 0
                    }
                }
            }
        }
    });
}

createProgressChart(
    "screeningChart",
    screeningLabels,
    screeningScores,
    "Recorded screening score",
    "#6552dc"
);

createProgressChart(
    "learningChart",
    learningLabels,
    learningScores,
    "Correct answers",
    "#159b83"
);
</script>

</body>
</html>
