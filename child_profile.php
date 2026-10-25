
<?php
session_start();
require_once "db_connect.php";

if (!isset($_SESSION["user_id"]) || ($_SESSION["user_role"] ?? "") !== "parent") {
    header("Location: login.php");
    exit;
}

$message = "";
$messageType = "";
$parent_id = (int) $_SESSION["user_id"];

function e($value) {
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $child_name = trim($_POST["child_name"] ?? "");
    $dob = trim($_POST["date_of_birth"] ?? "");
    $gender = trim($_POST["gender"] ?? "");
    $school = trim($_POST["school_name"] ?? "");

    $dob = $dob !== "" ? $dob : null;
    $gender = $gender !== "" ? $gender : null;

    if ($child_name === "") {
        $message = "Please enter the child's name.";
        $messageType = "error";
    } elseif (mb_strlen($child_name) > 100 || mb_strlen($school) > 150) {
        $message = "Please check the length of the name or school name.";
        $messageType = "error";
    } elseif ($dob !== null && (
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob) ||
        $dob > date("Y-m-d") ||
        !checkdate((int)substr($dob, 5, 2), (int)substr($dob, 8, 2), (int)substr($dob, 0, 4))
    )) {
        $message = "Please select a valid date of birth.";
        $messageType = "error";
    } elseif (!in_array($gender, ["", "Male", "Female", "Other"], true)) {
        $message = "Please select a valid gender.";
        $messageType = "error";
    } else {
        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO children
            (parent_id, child_name, date_of_birth, gender, school_name)
            VALUES (?, ?, ?, ?, ?)"
        );

        if ($stmt) {
            mysqli_stmt_bind_param(
                $stmt, "issss", $parent_id, $child_name, $dob, $gender, $school
            );

            if (mysqli_stmt_execute($stmt)) {
                $message = "Child profile saved successfully!";
                $messageType = "success";
            } else {
                $message = "Could not save profile. Please try again.";
                $messageType = "error";
            }

            mysqli_stmt_close($stmt);
        } else {
            $message = "Something went wrong. Please try again.";
            $messageType = "error";
        }
    }
}

$stmt = mysqli_prepare(
    $conn,
    "SELECT id, child_name, date_of_birth, gender, school_name, created_at
     FROM children
     WHERE parent_id = ?
     ORDER BY id DESC"
);

mysqli_stmt_bind_param($stmt, "i", $parent_id);
mysqli_stmt_execute($stmt);
$children = mysqli_stmt_get_result($stmt);

$childCount = mysqli_num_rows($children);
$childRows = [];

while ($row = mysqli_fetch_assoc($children)) {
    $childRows[] = $row;
}

mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Child Profiles | DLearn</title>

<link rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: "Segoe UI", Arial, sans-serif;
    background: #f7f5ff;
    color: #30294c;
}

a {
    text-decoration: none;
}

/* Header */
.topbar {
    padding: 16px 5%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    flex-wrap: wrap;
    background: #fff;
    box-shadow: 0 3px 16px rgba(65, 44, 110, .07);
}

.brand {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #6846cf;
    font-size: 23px;
    font-weight: 800;
}

.brand-icon {
    width: 42px;
    height: 42px;
    display: grid;
    place-items: center;
    border-radius: 14px;
    color: #fff;
    background: linear-gradient(135deg, #754de8, #ed68b6);
    font-size: 21px;
}

.back-link {
    padding: 10px 16px;
    border-radius: 11px;
    background: #f0eaff;
    color: #6744c5;
    font-size: 13px;
    font-weight: 700;
}

/* Colorful hero */
.hero {
    position: relative;
    overflow: hidden;
    margin: 28px auto 32px;
    width: 94%;
    padding: 36px 42px;
    min-height: 215px;
    border-radius: 25px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 25px;
    color: #fff;
    background: linear-gradient(115deg, #7149db, #a14ed0 55%, #ef70ac);
    box-shadow: 0 14px 32px rgba(123, 75, 198, .2);
}

.hero::before,
.hero::after {
    content: "";
    position: absolute;
    border-radius: 50%;
    background: rgba(255,255,255,.09);
    pointer-events: none;
}

.hero::before {
    width: 240px;
    height: 240px;
    top: -150px;
    right: 150px;
}

.hero::after {
    width: 200px;
    height: 200px;
    bottom: -130px;
    right: -20px;
}

.hero-text {
    position: relative;
    z-index: 1;
    max-width: 760px;
}

.hero-tag {
    display: inline-flex;
    gap: 8px;
    align-items: center;
    padding: 8px 12px;
    border-radius: 30px;
    background: rgba(255,255,255,.17);
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 1px;
}

.hero h1 {
    margin: 15px 0 10px;
    font-size: clamp(27px, 3vw, 38px);
    line-height: 1.2;
}

.hero p {
    max-width: 650px;
    margin: 0;
    color: #f9efff;
    font-size: 14px;
    line-height: 1.8;
}

.hero-art {
    position: relative;
    z-index: 1;
    flex-shrink: 0;
    width: 155px;
    height: 145px;
    display: grid;
    place-items: center;
}

.hero-book {
    width: 110px;
    height: 110px;
    display: grid;
    place-items: center;
    border: 1px solid rgba(255,255,255,.4);
    border-radius: 32px;
    background: rgba(255,255,255,.17);
    font-size: 58px;
    transform: rotate(-8deg);
}

.art-star {
    position: absolute;
    width: 37px;
    height: 37px;
    display: grid;
    place-items: center;
    border-radius: 12px;
    background: #fff;
    color: #ef9b36;
    font-size: 18px;
    box-shadow: 0 5px 16px #43207730;
}

.star-one {
    top: 0;
    right: 0;
}

.star-two {
    bottom: 0;
    left: 0;
    color: #22a67b;
}

/* Main layout */
.page-layout {
    width: 94%;
    max-width: 1600px;
    margin: 0 auto 40px;
    display: grid;
    grid-template-columns: minmax(340px, .85fr) minmax(0, 1.5fr);
    align-items: start;
    gap: 26px;
}

.panel {
    min-width: 0;
    padding: 27px;
    border: 1px solid #eee8fb;
    border-radius: 21px;
    background: #fff;
    box-shadow: 0 7px 24px rgba(64, 45, 110, .055);
}

.panel-heading {
    display: flex;
    align-items: center;
    gap: 13px;
    margin-bottom: 23px;
}

.heading-icon {
    width: 49px;
    height: 49px;
    flex-shrink: 0;
    display: grid;
    place-items: center;
    border-radius: 15px;
    font-size: 23px;
}

.heading-purple {
    background: #f0eaff;
    color: #8053dd;
}

.heading-pink {
    background: #ffebf3;
    color: #dc5794;
}

.panel-heading h2 {
    margin: 0 0 5px;
    color: #30294c;
    font-size: 20px;
}

.panel-heading p {
    margin: 0;
    color: #89839e;
    font-size: 12px;
    line-height: 1.5;
}

/* Form */
.form-label {
    display: block;
    margin: 15px 0 7px;
    color: #4b4367;
    font-size: 13px;
    font-weight: 700;
}

.form-control {
    width: 100%;
    min-height: 45px;
    padding: 12px 13px;
    border: 1px solid #e5def3;
    border-radius: 11px;
    outline: none;
    background: #fcfbff;
    color: #30294c;
    font: inherit;
    font-size: 13px;
    transition: border-color .2s, box-shadow .2s;
}

.form-control:focus {
    border-color: #9469e8;
    box-shadow: 0 0 0 3px #9469e820;
    background: #fff;
}

.form-control::placeholder {
    color: #aaa3bb;
}

.submit-button {
    width: 100%;
    min-height: 48px;
    margin-top: 22px;
    padding: 12px 17px;
    border: 0;
    border-radius: 12px;
    background: linear-gradient(100deg, #7449dc, #e05ca7);
    color: #fff;
    font-size: 14px;
    font-weight: 800;
    cursor: pointer;
    box-shadow: 0 7px 16px #9a58d52c;
    transition: transform .2s, box-shadow .2s;
}

.submit-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px #9a58d540;
}

.notice {
    margin-bottom: 18px;
    padding: 12px 14px;
    border-radius: 11px;
    font-size: 13px;
    line-height: 1.6;
}

.notice.success {
    background: #e5f9ee;
    border: 1px solid #c3efd5;
    color: #16754c;
}

.notice.error {
    background: #fff0f0;
    border: 1px solid #ffd4d4;
    color: #b33b47;
}

/* Saved child cards */
.saved-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 22px;
}

.saved-heading .panel-heading {
    margin-bottom: 0;
}

.count-badge {
    flex-shrink: 0;
    padding: 8px 12px;
    border-radius: 30px;
    background: #f0eaff;
    color: #7549ce;
    font-size: 12px;
    font-weight: 800;
}

.children-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 17px;
}

.child-card {
    --accent: #7551d8;
    --soft: #f1ebff;
    min-width: 0;
    padding: 20px;
    overflow: hidden;
    border: 1px solid #eee8fb;
    border-top: 4px solid var(--accent);
    border-radius: 17px;
    background: linear-gradient(150deg, #fff, var(--soft));
    transition: transform .2s, box-shadow .2s;
}

.child-card:nth-child(5n + 2) {
    --accent: #e05b9b;
    --soft: #fff0f6;
}

.child-card:nth-child(5n + 3) {
    --accent: #259b80;
    --soft: #eafaf4;
}

.child-card:nth-child(5n + 4) {
    --accent: #e99a35;
    --soft: #fff6e8;
}

.child-card:nth-child(5n + 5) {
    --accent: #4286e8;
    --soft: #edf5ff;
}

.child-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 23px rgba(64, 45, 110, .09);
}

.child-avatar {
    width: 57px;
    height: 57px;
    display: grid;
    place-items: center;
    margin-bottom: 15px;
    border-radius: 19px;
    background: var(--soft);
    color: var(--accent);
    font-size: 28px;
}

.child-name {
    margin: 0 0 13px;
    overflow-wrap: anywhere;
    color: #30294c;
    font-size: 17px;
    font-weight: 800;
}

.child-detail {
    display: flex;
    align-items: flex-start;
    gap: 9px;
    margin-top: 10px;
    color: #77718d;
    font-size: 12px;
    line-height: 1.6;
    overflow-wrap: anywhere;
}

.child-detail i {
    flex-shrink: 0;
    margin-top: 1px;
    color: var(--accent);
    font-size: 15px;
}

.empty-state {
    padding: 35px 20px;
    text-align: center;
    border: 2px dashed #e8dff7;
    border-radius: 17px;
    background: #fcfaff;
}

.empty-icon {
    width: 72px;
    height: 72px;
    display: grid;
    place-items: center;
    margin: 0 auto 16px;
    border-radius: 24px;
    background: #f0eaff;
    color: #8557d9;
    font-size: 34px;
}

.empty-state h3 {
    margin: 0 0 9px;
    font-size: 17px;
    color: #42365e;
}

.empty-state p {
    max-width: 300px;
    margin: auto;
    color: #89839e;
    font-size: 13px;
    line-height: 1.7;
}

/* Footer */
.footer {
    padding: 24px 15px;
    text-align: center;
    background: #fff;
    border-top: 1px solid #eee8fb;
    color: #89839e;
    font-size: 12px;
    line-height: 1.8;
}

.footer p {
    margin: 3px 0;
}

/* Responsive */
@media (max-width: 1050px) {
    .page-layout {
        grid-template-columns: 1fr;
    }

    .children-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

@media (max-width: 700px) {
    .hero {
        width: 92%;
        padding: 26px 23px;
    }

    .hero-art {
        display: none;
    }

    .page-layout {
        width: 92%;
        gap: 20px;
    }

    .panel {
        padding: 21px;
    }

    .children-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 460px) {
    .topbar {
        padding: 13px 4%;
    }

    .hero {
        margin-top: 20px;
        border-radius: 19px;
    }

    .hero h1 {
        font-size: 27px;
    }

    .children-grid {
        grid-template-columns: 1fr;
    }

    .saved-heading {
        align-items: flex-start;
    }
}
</style>
</head>

<body>

<header class="topbar">
    <a class="brand" href="index.php">
        <span class="brand-icon"><i class="bi bi-book-half"></i></span>
        <span>DLearn</span>
    </a>

    <a class="back-link" href="dashboard.php">
        <i class="bi bi-arrow-left"></i> Back to Dashboard
    </a>
</header>

<section class="hero">
    <div class="hero-text">
        <span class="hero-tag">
            <i class="bi bi-stars"></i> LITTLE LEARNERS, BIG DREAMS
        </span>

        <h1>Every Child Is Special! <i class="bi bi-heart-fill"></i></h1>

        <p>
            Create and manage your child's profile in one place.
            Every learning journey begins with understanding the learner.
        </p>
    </div>

    <div class="hero-art" aria-hidden="true">
        <div class="hero-book"><i class="bi bi-book-half"></i></div>
        <span class="art-star star-one"><i class="bi bi-stars"></i></span>
        <span class="art-star star-two"><i class="bi bi-lightbulb-fill"></i></span>
    </div>
</section>

<main class="page-layout">

    <!-- Add child form -->
    <section class="panel">
        <div class="panel-heading">
            <span class="heading-icon heading-purple">
                <i class="bi bi-person-plus-fill"></i>
            </span>
            <div>
                <h2>Add Child Details</h2>
                <p>Fill in your child's basic information.</p>
            </div>
        </div>

        <?php if ($message !== ""): ?>
            <div class="notice <?= e($messageType) ?>" role="status">
                <i class="bi <?= $messageType === "success" ? "bi-check-circle-fill" : "bi-exclamation-circle-fill" ?>"></i>
                <?= e($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="child_profile.php">
            <label class="form-label" for="child_name">Child's Name *</label>
            <input
                class="form-control"
                type="text"
                id="child_name"
                name="child_name"
                maxlength="100"
                placeholder="Enter child's full name"
                autocomplete="off"
                required
            >

            <label class="form-label" for="date_of_birth">Date of Birth</label>
            <input
                class="form-control"
                type="date"
                id="date_of_birth"
                name="date_of_birth"
                max="<?= date("Y-m-d") ?>"
            >

            <label class="form-label" for="gender">Gender</label>
            <select class="form-control" id="gender" name="gender">
                <option value="">Select gender</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Other">Other</option>
            </select>

            <label class="form-label" for="school_name">School Name</label>
            <input
                class="form-control"
                type="text"
                id="school_name"
                name="school_name"
                maxlength="150"
                placeholder="Enter school name (optional)"
            >

            <button class="submit-button" type="submit">
                <i class="bi bi-plus-circle-fill"></i>
                Save Child Profile
                <i class="bi bi-arrow-right"></i>
            </button>
        </form>
    </section>

    <!-- Saved child profiles -->
    <section class="panel">
        <div class="saved-heading">
            <div class="panel-heading">
                <span class="heading-icon heading-pink">
                    <i class="bi bi-people-fill"></i>
                </span>
                <div>
                    <h2>Saved Children</h2>
                    <p>Your family's learning profiles.</p>
                </div>
            </div>

            <span class="count-badge">
                <?= $childCount ?> <?= $childCount === 1 ? "Child" : "Children" ?>
            </span>
        </div>

        <?php if ($childCount === 0): ?>

            <div class="empty-state">
                <div class="empty-icon">
                    <i class="bi bi-emoji-smile"></i>
                </div>
                <h3>Your journey starts here!</h3>
                <p>
                    You haven't added a child profile yet.
                    Use the form to create your first profile.
                </p>
            </div>

        <?php else: ?>

            <div class="children-grid">
                <?php foreach ($childRows as $child): ?>
                    <article class="child-card">

                        <div class="child-avatar">
                            <i class="bi bi-person-bounding-box"></i>
                        </div>

                        <h3 class="child-name"><?= e($child["child_name"]) ?></h3>

                        <div class="child-detail">
                            <i class="bi bi-calendar-heart"></i>
                            <span>
                                <strong>Date of Birth</strong><br>
                                <?= e($child["date_of_birth"] ?: "Not provided") ?>
                            </span>
                        </div>

                        <div class="child-detail">
                            <i class="bi bi-gender-ambiguous"></i>
                            <span>
                                <strong>Gender</strong><br>
                                <?= e($child["gender"] ?: "Not provided") ?>
                            </span>
                        </div>

                        <div class="child-detail">
                            <i class="bi bi-mortarboard-fill"></i>
                            <span>
                                <strong>School</strong><br>
                                <?= e($child["school_name"] ?: "Not provided") ?>
                            </span>
                        </div>

                    </article>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>
    </section>

</main>

<footer class="footer">
    <p>© <?= date("Y") ?> DLearn · Supporting every learner's journey</p>
    <p>Educational screening only; not a medical diagnosis.</p>
</footer>

</body>
</html>
