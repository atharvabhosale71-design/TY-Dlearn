
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

$dashboardTitles = [
    "parent"  => "Parent Dashboard",
    "teacher" => "Teacher Dashboard",
    "admin"   => "Admin Dashboard"
];

$cards = [
    "parent" => [
        [
            "icon" => "bi-people-fill",
            "color" => "blue",
            "title" => "Child Profiles",
            "description" => "Add your child and manage their personal details and profiles.",
            "button" => "Manage Children",
            "link" => "child_profile.php"
        ],
        [
            "icon" => "bi-clipboard2-check-fill",
            "color" => "purple",
            "title" => "Screening Questionnaire",
            "description" => "Record observations about your child's reading and learning.",
            "button" => "Start Screening",
            "link" => "screening.php"
        ],
        [
            "icon" => "bi-book-half",
            "color" => "green",
            "title" => "Learning Area",
            "description" => "Explore learning activities designed to support your child.",
            "button" => "Open Learning Area",
            "link" => "learning.php"
        ],
        [
            "icon" => "bi-file-earmark-bar-graph-fill",
            "color" => "orange",
            "title" => "Assessment Reports",
            "description" => "View saved screening summaries and assessment records.",
            "button" => "View Reports",
            "link" => "report.php"
        ],
        [
            "icon" => "bi-graph-up-arrow",
            "color" => "pink",
            "title" => "Progress Tracking",
            "description" => "Review your child's recorded screening scores over time.",
            "button" => "View Progress",
            "link" => "progress.php"
        ]
    ],

    "teacher" => [
        [
            "icon" => "bi-people-fill",
            "color" => "blue",
            "title" => "Assigned Students",
            "description" => "View students assigned to you for assessment.",
            "button" => "View Students",
            "link" => "teacher_assessment.php"
        ],
        [
            "icon" => "bi-clipboard2-check-fill",
            "color" => "purple",
            "title" => "Student Assessment",
            "description" => "Review student answers, enter marks and add remarks.",
            "button" => "Assess Students",
            "link" => "teacher_assessment.php"
        ],
        [
            "icon" => "bi-file-earmark-text-fill",
            "color" => "orange",
            "title" => "Assessment Reports",
            "description" => "Manage assessment records for student progress reviews.",
            "button" => "Manage Assessments",
            "link" => "teacher_assessment.php"
        ]
    ],

    "admin" => [
        [
            "icon" => "bi-people-fill",
            "color" => "blue",
            "title" => "User & Child Management",
            "description" => "Access the administration overview for users and children.",
            "button" => "Admin Overview",
            "link" => "admin.php"
        ],
        [
            "icon" => "bi-file-earmark-bar-graph-fill",
            "color" => "orange",
            "title" => "Screening & Reports",
            "description" => "Review saved screening and assessment records.",
            "button" => "View Reports",
            "link" => "report.php"
        ],
        [
            "icon" => "bi-book-half",
            "color" => "green",
            "title" => "Learning Activities",
            "description" => "Preview the available learning activities.",
            "button" => "Open Learning Page",
            "link" => "learning.php"
        ],
        [
            "icon" => "bi-person-check-fill",
            "color" => "pink",
            "title" => "Student Assignments",
            "description" => "Open the admin area for configured assignment management.",
            "button" => "Manage Assignments",
            "link" => "admin.php"
        ]
    ]
];

$roleLabel = ucfirst($role);
$dashboardTitle = $dashboardTitles[$role];
$roleCards = $cards[$role];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?= htmlspecialchars($dashboardTitle, ENT_QUOTES, "UTF-8") ?> | DLearn</title>

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="/TY-Dlearn/assets/css/dashboard.css?v=2">
</head>

<body class="dashboard-page">

<header class="dashboard-header">
    <a class="brand" href="index.php">
        <span class="brand-icon">
            <i class="bi bi-book-half"></i>
        </span>
        <span>DLearn</span>
    </a>

    <nav class="header-links">
        <span class="welcome-user">
            <i class="bi bi-person-circle"></i>
            Welcome, <?= $name ?>
        </span>

        <a href="index.php">
            <i class="bi bi-house-door"></i> Home
        </a>

        <a href="logout.php" class="logout-link">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </nav>
</header>

<main class="dashboard-container">

    <section class="dashboard-welcome">
        <div class="welcome-content">
            <span class="welcome-label">
                <i class="bi bi-stars"></i> YOUR LEARNING SPACE
            </span>

            <h1><?= htmlspecialchars($dashboardTitle, ENT_QUOTES, "UTF-8") ?></h1>

            <p>
                Welcome to DLearn — Dyslexia Screening and Learning Support Platform.
                Manage learning activities and access your dashboard tools in one place.
            </p>

            <span class="account-badge">
                <i class="bi bi-shield-check"></i>
                <?= htmlspecialchars($roleLabel, ENT_QUOTES, "UTF-8") ?> Account
            </span>
        </div>

        <div class="welcome-illustration" aria-hidden="true">
            <div class="illustration-circle">
                <i class="bi bi-book-half"></i>
            </div>
            <span class="floating-icon floating-one">
                <i class="bi bi-pencil-fill"></i>
            </span>
            <span class="floating-icon floating-two">
                <i class="bi bi-lightbulb-fill"></i>
            </span>
            <span class="floating-icon floating-three">
                <i class="bi bi-stars"></i>
            </span>
        </div>
    </section>

    <div class="section-heading">
        <div>
            <span class="section-eyebrow">YOUR WORKSPACE</span>
            <h2>Explore Your Dashboard</h2>
            <p>Choose a section to get started.</p>
        </div>

        <span class="feature-count">
            <i class="bi bi-grid-fill"></i>
            <?= count($roleCards) ?> Features
        </span>
    </div>

    <section class="dashboard-cards">

        <?php foreach ($roleCards as $card): ?>
            <article class="dashboard-card card-<?= htmlspecialchars($card["color"], ENT_QUOTES, "UTF-8") ?>">

                <div class="card-top">
                    <div class="card-icon">
                        <i class="bi <?= htmlspecialchars($card["icon"], ENT_QUOTES, "UTF-8") ?>"></i>
                    </div>

                    <span class="card-arrow">
                        <i class="bi bi-arrow-up-right"></i>
                    </span>
                </div>

                <h3><?= htmlspecialchars($card["title"], ENT_QUOTES, "UTF-8") ?></h3>

                <p><?= htmlspecialchars($card["description"], ENT_QUOTES, "UTF-8") ?></p>

                <a class="card-button"
                   href="<?= htmlspecialchars($card["link"], ENT_QUOTES, "UTF-8") ?>">
                    <?= htmlspecialchars($card["button"], ENT_QUOTES, "UTF-8") ?>
                    <i class="bi bi-arrow-right"></i>
                </a>

            </article>
        <?php endforeach; ?>

    </section>

    <aside class="dashboard-note">
        <span class="note-icon">
            <i class="bi bi-heart-fill"></i>
        </span>

        <div>
            <h3>Every learner has their own strengths</h3>
            <p>
                DLearn supports learning and educational screening.
                Screening results are not a medical diagnosis.
            </p>
        </div>
    </aside>

</main>

<footer class="dashboard-footer">
    <p>© <?= date("Y") ?> DLearn. All rights reserved.</p>
    <p>Made to support every learner's journey.</p>
</footer>

</body>
</html>
