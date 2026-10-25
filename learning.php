
<?php
session_start();
require_once "db_connect.php";

if (!isset($_SESSION["user_id"]) || ($_SESSION["user_role"] ?? "") !== "parent") {
    header("Location: login.php");
    exit;
}

$parent_id = (int) $_SESSION["user_id"];
$message = "";
$messageType = "success";

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

/* Get only this parent's children */
$stmt = mysqli_prepare($conn,
    "SELECT id, child_name FROM children WHERE parent_id = ? ORDER BY child_name");
mysqli_stmt_bind_param($stmt, "i", $parent_id);
mysqli_stmt_execute($stmt);
$childrenResult = mysqli_stmt_get_result($stmt);
$children = mysqli_fetch_all($childrenResult, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$validActivities = [
    "letter-match",
    "picture-match",
    "word-builder",
    "sentence-time"
];

/* Save a completed activity */
if ($_SERVER["REQUEST_METHOD"] === "POST" &&
    ($_POST["action"] ?? "") === "save_activity") {

    $child_id = filter_input(INPUT_POST, "child_id", FILTER_VALIDATE_INT);
    $activity = $_POST["activity_key"] ?? "";
    $score = filter_input(INPUT_POST, "score", FILTER_VALIDATE_INT);
    $total = filter_input(INPUT_POST, "total_questions", FILTER_VALIDATE_INT);

    if (!$child_id || !in_array($activity, $validActivities, true) ||
        $score === false || $score === null ||
        $total === false || $total === null ||
        $total < 1 || $score < 0 || $score > $total) {

        $message = "Please complete a valid activity.";
        $messageType = "error";
    } else {
        $check = mysqli_prepare($conn,
            "SELECT id FROM children WHERE id = ? AND parent_id = ?");
        mysqli_stmt_bind_param($check, "ii", $child_id, $parent_id);
        mysqli_stmt_execute($check);
        $ownedChild = mysqli_stmt_get_result($check);
        $childExists = mysqli_num_rows($ownedChild) === 1;
        mysqli_stmt_close($check);

        if (!$childExists) {
            $message = "Please select your own child's profile.";
            $messageType = "error";
        } else {
            $save = mysqli_prepare($conn,
                "INSERT INTO learning_activity_results
                 (parent_id, child_id, activity_key, score, total_questions)
                 VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param(
                $save, "iisii", $parent_id, $child_id, $activity, $score, $total
            );

            if (mysqli_stmt_execute($save)) {
                $message = "Activity completed! Your score has been saved.";
            } else {
                $message = "Could not save the result. Please try again.";
                $messageType = "error";
            }
            mysqli_stmt_close($save);
        }
    }
}

/* Load activity history */
$history = [];
if (count($children) > 0) {
    $historyStmt = mysqli_prepare($conn,
        "SELECT child_id, activity_key, score, total_questions, completed_at
         FROM learning_activity_results
         WHERE parent_id = ?
         ORDER BY completed_at DESC LIMIT 20");
    mysqli_stmt_bind_param($historyStmt, "i", $parent_id);
    mysqli_stmt_execute($historyStmt);
    $historyResult = mysqli_stmt_get_result($historyStmt);
    $history = mysqli_fetch_all($historyResult, MYSQLI_ASSOC);
    mysqli_stmt_close($historyStmt);
}

$activityNames = [
    "letter-match" => "Letter Match",
    "picture-match" => "Picture Match",
    "word-builder" => "Word Builder",
    "sentence-time" => "Sentence Time"
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Learning Studio | DLearn</title>
<link rel="stylesheet" href="assets/css/learning.css">
</head>
<body>

<header class="topbar">
    <a class="brand" href="dashboard.php"><span class="brand-mark">D</span> DLearn</a>
    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="progress.php">Progress</a>
    </nav>
</header>

<main class="container">
    <section class="hero">
        <div class="hero-copy">
            <span class="eyebrow">YOUR LEARNING STUDIO</span>
            <h1>Learn at your own pace<span>.</span></h1>
            <p>Explore letters, words, pictures and reading through short, interactive activities. Every little step counts.</p>
            <div class="hero-note"><span>✦</span> Practise • Explore • Grow</div>
        </div>
        <div class="hero-art" aria-hidden="true">
            <div class="art-card card-one">Aa</div>
            <div class="art-card card-two">ABC</div>
            <div class="art-card card-three">✦</div>
            <div class="art-orbit"></div>
        </div>
    </section>

    <?php if ($message !== ""): ?>
        <div class="notice <?= h($messageType) ?>" role="status"><?= h($message) ?></div>
    <?php endif; ?>

    <?php if (count($children) === 0): ?>
        <section class="panel empty-state">
            <h2>Let's set up your learning space</h2>
            <p>Add a child profile first to start activities and save learning results.</p>
            <a class="primary-btn" href="child_profile.php">Open Child Profiles</a>
        </section>
    <?php else: ?>

    <section class="child-bar">
        <div>
            <span class="eyebrow">LEARNING PROFILE</span>
            <h2>Who's learning today?</h2>
        </div>
        <label class="child-select">
            <span>Choose child</span>
            <select id="childSelect">
                <?php foreach ($children as $child): ?>
                    <option value="<?= (int)$child["id"] ?>">
                        <?= h($child["child_name"]) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </section>

    <section class="section-heading">
        <div>
            <span class="eyebrow">PICK YOUR NEXT CHALLENGE</span>
            <h2>Explore activities</h2>
        </div>
        <span class="section-caption">Short sessions. Big progress.</span>
    </section>

    <section class="activity-grid">
        <article class="activity-card">
            <div class="activity-visual visual-blue"><span class="visual-symbol">Aa</span><span class="visual-tag">LETTERS</span></div>
            <div class="activity-body">
                <div class="activity-meta"><span>FOUNDATION</span><span>4 QUESTIONS</span></div>
                <h3>Letter Match</h3>
                <p>Recognise letters and spot the letter that matches the prompt.</p>
                <button class="start-btn" data-activity="letter-match">Start activity <span>→</span></button>
            </div>
        </article>

        <article class="activity-card">
            <div class="activity-visual visual-peach"><span class="visual-symbol">☀</span><span class="visual-tag">PICTURES</span></div>
            <div class="activity-body">
                <div class="activity-meta"><span>FOUNDATION</span><span>4 QUESTIONS</span></div>
                <h3>Picture Match</h3>
                <p>Connect familiar words with the pictures they describe.</p>
                <button class="start-btn" data-activity="picture-match">Start activity <span>→</span></button>
            </div>
        </article>

        <article class="activity-card">
            <div class="activity-visual visual-lilac"><span class="visual-symbol">C A T</span><span class="visual-tag">WORD PLAY</span></div>
            <div class="activity-body">
                <div class="activity-meta"><span>BEGINNER</span><span>4 QUESTIONS</span></div>
                <h3>Word Builder</h3>
                <p>Choose the correct spelling and build confidence with simple words.</p>
                <button class="start-btn" data-activity="word-builder">Start activity <span>→</span></button>
            </div>
        </article>

        <article class="activity-card">
            <div class="activity-visual visual-mint"><span class="visual-symbol">“ ”</span><span class="visual-tag">READING</span></div>
            <div class="activity-body">
                <div class="activity-meta"><span>BEGINNER</span><span>4 QUESTIONS</span></div>
                <h3>Sentence Time</h3>
                <p>Read a short sentence and answer a simple question about it.</p>
                <button class="start-btn" data-activity="sentence-time">Start activity <span>→</span></button>
            </div>
        </article>
    </section>

    <section class="section-heading history-heading">
        <div>
            <span class="eyebrow">SMALL STEPS MATTER</span>
            <h2>Recent practice</h2>
        </div>
        <a class="text-link" href="progress.php">View progress →</a>
    </section>

    <section class="panel history-panel">
        <?php if (count($history) === 0): ?>
            <div class="history-empty">
                <div class="empty-icon">✦</div>
                <div><h3>Your learning journey starts here</h3>
                <p>Complete an activity and its result will appear here.</p></div>
            </div>
        <?php else: ?>
            <div class="history-list">
                <?php foreach ($history as $item): ?>
                    <div class="history-row">
                        <div class="history-icon">✓</div>
                        <div class="history-info">
                            <strong><?= h($activityNames[$item["activity_key"]] ?? "Learning Activity") ?></strong>
                            <span><?= h(date("d M Y, g:i A", strtotime($item["completed_at"]))) ?></span>
                        </div>
                        <strong class="history-score">
                            <?= (int)$item["score"] ?>/<?= (int)$item["total_questions"] ?>
                        </strong>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <?php endif; ?>
</main>

<!-- Activity dialog -->
<div class="modal" id="activityModal" aria-hidden="true">
    <div class="modal-backdrop" data-close="true"></div>
    <section class="modal-card" role="dialog" aria-modal="true" aria-labelledby="gameTitle">
        <button class="modal-close" id="closeModal" aria-label="Close activity">×</button>
        <div class="game-top">
            <span class="eyebrow" id="gameCategory">LEARNING ACTIVITY</span>
            <span class="question-count" id="questionCount">Question 1 of 4</span>
        </div>
        <div class="game-progress"><span id="gameProgress"></span></div>
        <h2 id="gameTitle">Let's practise!</h2>
        <p class="game-instruction" id="gameInstruction">Choose your answer.</p>
        <div id="questionArea"></div>
        <div id="feedback" class="feedback" aria-live="polite"></div>
        <div class="game-actions">
            <button class="primary-btn" id="checkAnswer" disabled>Check answer</button>
            <button class="primary-btn hidden" id="nextQuestion">Next question →</button>
        </div>
        <div id="resultArea" class="result-area hidden"></div>
        <form id="saveForm" method="post" class="hidden">
            <input type="hidden" name="action" value="save_activity">
            <input type="hidden" name="child_id" id="saveChildId">
            <input type="hidden" name="activity_key" id="saveActivityKey">
            <input type="hidden" name="score" id="saveScore">
            <input type="hidden" name="total_questions" value="4">
            <button type="submit" class="primary-btn">Save result</button>
        </form>
    </section>
</div>

<script>
const activityData = {
    "letter-match": {
        title: "Letter Match",
        category: "LETTERS",
        instruction: "Look at the big letter. Choose the same letter.",
        questions: [
            {prompt:"Find the letter A", options:["A","M","O"], answer:"A"},
            {prompt:"Find the letter B", options:["D","B","P"], answer:"B"},
            {prompt:"Find the letter M", options:["N","W","M"], answer:"M"},
            {prompt:"Find the letter S", options:["Z","S","C"], answer:"S"}
        ]
    },
    "picture-match": {
        title: "Picture Match",
        category: "WORDS & PICTURES",
        instruction: "Read the word and choose the matching picture.",
        questions: [
            {prompt:"Which one is the SUN?", options:["☀ Sun","☂ Umbrella","★ Star"], answer:"☀ Sun"},
            {prompt:"Which one is a CAT?", options:["🐟 Fish","🐱 Cat","🐦 Bird"], answer:"🐱 Cat"},
            {prompt:"Which one is an APPLE?", options:["🍎 Apple","🍌 Banana","🍇 Grapes"], answer:"🍎 Apple"},
            {prompt:"Which one is a BOOK?", options:["⚽ Ball","✏ Pencil","📘 Book"], answer:"📘 Book"}
        ]
    },
    "word-builder": {
        title: "Word Builder",
        category: "WORD PLAY",
        instruction: "Choose the correctly spelled word.",
        questions: [
            {prompt:"Which spelling is correct?", options:["CAT","CTA","ACT"], answer:"CAT"},
            {prompt:"Which spelling is correct?", options:["DGO","DOG","GOD"], answer:"DOG"},
            {prompt:"Which spelling is correct?", options:["SUN","SNU","UNS"], answer:"SUN"},
            {prompt:"Which spelling is correct?", options:["FISH","FSIH","HIFS"], answer:"FISH"}
        ]
    },
    "sentence-time": {
        title: "Sentence Time",
        category: "READING",
        instruction: "Read the sentence and answer the question.",
        questions: [
            {prompt:"The cat is sleeping. What is sleeping?", options:["The cat","The dog","The bird"], answer:"The cat"},
            {prompt:"The sun is hot. What is hot?", options:["The moon","The sun","The rain"], answer:"The sun"},
            {prompt:"Ria has a red ball. What colour is the ball?", options:["Blue","Green","Red"], answer:"Red"},
            {prompt:"The bird can fly. What can fly?", options:["The fish","The bird","The cat"], answer:"The bird"}
        ]
    }
};

const modal = document.getElementById("activityModal");
const questionArea = document.getElementById("questionArea");
const feedback = document.getElementById("feedback");
const checkButton = document.getElementById("checkAnswer");
const nextButton = document.getElementById("nextQuestion");
const resultArea = document.getElementById("resultArea");
const saveForm = document.getElementById("saveForm");

let currentActivity = "";
let currentIndex = 0;
let currentScore = 0;
let selectedAnswer = null;
let answerChecked = false;

function openActivity(key) {
    currentActivity = key;
    currentIndex = 0;
    currentScore = 0;
    saveForm.classList.add("hidden");
    resultArea.classList.add("hidden");
    document.getElementById("gameTitle").textContent = activityData[key].title;
    document.getElementById("gameCategory").textContent = activityData[key].category;
    document.getElementById("gameInstruction").textContent = activityData[key].instruction;
    modal.classList.add("open");
    modal.setAttribute("aria-hidden", "false");
    document.body.classList.add("modal-open");
    renderQuestion();
}

function renderQuestion() {
    const data = activityData[currentActivity];
    const q = data.questions[currentIndex];
    selectedAnswer = null;
    answerChecked = false;

    document.getElementById("questionCount").textContent =
        `Question ${currentIndex + 1} of ${data.questions.length}`;
    document.getElementById("gameProgress").style.width =
        `${(currentIndex / data.questions.length) * 100}%`;

    questionArea.innerHTML = "";
    const prompt = document.createElement("div");
    prompt.className = "question-prompt";
    prompt.textContent = q.prompt;
    questionArea.appendChild(prompt);

    const options = document.createElement("div");
    options.className = "answer-options";

    q.options.forEach(option => {
        const button = document.createElement("button");
        button.type = "button";
        button.className = "answer-option";
        button.textContent = option;
        button.addEventListener("click", () => {
            if (answerChecked) return;
            selectedAnswer = option;
            options.querySelectorAll("button").forEach(b => b.classList.remove("selected"));
            button.classList.add("selected");
            checkButton.disabled = false;
        });
        options.appendChild(button);
    });

    questionArea.appendChild(options);
    feedback.textContent = "";
    feedback.className = "feedback";
    checkButton.classList.remove("hidden");
    checkButton.disabled = true;
    nextButton.classList.add("hidden");
    resultArea.classList.add("hidden");
}

checkButton.addEventListener("click", () => {
    if (selectedAnswer === null || answerChecked) return;

    answerChecked = true;
    const q = activityData[currentActivity].questions[currentIndex];
    const correct = selectedAnswer === q.answer;

    if (correct) currentScore++;

    questionArea.querySelectorAll(".answer-option").forEach(button => {
        button.disabled = true;
        if (button.textContent === q.answer) button.classList.add("correct");
        else if (button.textContent === selectedAnswer) button.classList.add("wrong");
    });

    feedback.textContent = correct
        ? "Great work! You got it. ✦"
        : "Good try! The correct answer is: " + q.answer;
    feedback.classList.add(correct ? "good" : "try-again");

    checkButton.classList.add("hidden");
    nextButton.textContent = currentIndex === 3 ? "See my result →" : "Next question →";
    nextButton.classList.remove("hidden");
});

nextButton.addEventListener("click", () => {
    if (currentIndex < 3) {
        currentIndex++;
        renderQuestion();
        return;
    }

    questionArea.innerHTML = "";
    document.getElementById("gameProgress").style.width = "100%";
    document.getElementById("questionCount").textContent = "Activity complete";
    document.getElementById("gameTitle").textContent = "You did it!";
    document.getElementById("gameInstruction").textContent = "Every bit of practice helps you learn.";
    feedback.textContent = "";
    feedback.className = "feedback";
    checkButton.classList.add("hidden");
    nextButton.classList.add("hidden");

    const percent = Math.round((currentScore / 4) * 100);
    resultArea.innerHTML = `
        <div class="result-star">✦</div>
        <div class="result-score">${currentScore} / 4</div>
        <p>You answered ${percent}% correctly.</p>
        <p class="result-message">${currentScore === 4 ? "Fantastic work!" : "Nice effort! You can practise again anytime."}</p>
    `;
    resultArea.classList.remove("hidden");

    document.getElementById("saveChildId").value =
        document.getElementById("childSelect").value;
    document.getElementById("saveActivityKey").value = currentActivity;
    document.getElementById("saveScore").value = currentScore;
    saveForm.classList.remove("hidden");
});

function closeActivity() {
    modal.classList.remove("open");
    modal.setAttribute("aria-hidden", "true");
    document.body.classList.remove("modal-open");
}

document.getElementById("closeModal").addEventListener("click", closeActivity);
modal.querySelector(".modal-backdrop").addEventListener("click", closeActivity);

document.addEventListener("keydown", event => {
    if (event.key === "Escape" && modal.classList.contains("open")) {
        closeActivity();
    }
});

document.querySelectorAll("[data-activity]").forEach(button => {
    button.addEventListener("click", () => openActivity(button.dataset.activity));
});
</script>
</body>
</html>
