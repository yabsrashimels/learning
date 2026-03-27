<?php
session_start();
include "config.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$score = null;

if (isset($_POST['submit_quiz'])) {
    $q1 = isset($_POST['q1']) ? $_POST['q1'] : '';
    $q2 = isset($_POST['q2']) ? $_POST['q2'] : '';
    $q3 = isset($_POST['q3']) ? $_POST['q3'] : '';

    $pts = 0;
    if ($q1 === 'a') $pts++;
    if ($q2 === 'c') $pts++;
    if ($q3 === 'b') $pts++;

    $score = $pts;
    $email = $_SESSION['user'];
    
    $ins = @pg_prepare($conn, "ins_quiz", "INSERT INTO quizzes (email, quiz_name, score, total_questions) VALUES ($1, $2, $3, $4)");
    if ($ins) {
        pg_execute($conn, "ins_quiz", array($email, 'HTML Basics Quiz', $score, 3));
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>HTML Quiz</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .quiz-question { background: #f9fafb; padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; border-left: 4px solid #4f46e5; }
        .quiz-question p { margin-bottom: 1rem; font-size: 1.1rem; color: #1f2937; }
        .quiz-question label { display: block; margin-bottom: 0.5rem; cursor: pointer; color: #4b5563; }
        .quiz-question input { width: auto; margin-right: 0.5rem; }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="home.php" class="logo">EduCode</a>
        <div class="nav-links">
            <a href="home.php">Home</a>
            <a href="html-course.php">Course</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <div class="container wide" style="margin-top: 2rem;">
        <h1 style="border-bottom: 2px solid #e5e7eb; padding-bottom: 1rem; margin-bottom: 2rem;">HTML Basics Quiz</h1>

        <?php if ($score !== null): ?>
            <div class="alert success" style="font-size: 1.2rem;">
                🎉 You scored <strong><?php echo $score; ?> out of 3</strong>! Your result has been securely saved.
            </div>
            <div style="text-align: center; margin-top: 2rem; display: flex; gap: 1rem; justify-content: center;">
                <a href="html-course.php" class="btn-learn" style="text-decoration: none;">&larr; Back to HTML Course</a>
                <a href="home.php" class="btn-learn" style="text-decoration: none;">Continue Learning &rarr;</a>
            </div>
        <?php else: ?>
            <form method="POST">
                <div class="quiz-question">
                    <p><strong>1. What does HTML stand for?</strong></p>
                    <label><input type="radio" name="q1" value="a" required> Hyper Text Markup Language</label>
                    <label><input type="radio" name="q1" value="b"> Hot Mail</label>
                    <label><input type="radio" name="q1" value="c"> How to Make Lasagna</label>
                </div>

                <div class="quiz-question">
                    <p><strong>2. Which tag is used for the largest heading?</strong></p>
                    <label><input type="radio" name="q2" value="a" required> &lt;heading&gt;</label>
                    <label><input type="radio" name="q2" value="b"> &lt;h6&gt;</label>
                    <label><input type="radio" name="q2" value="c"> &lt;h1&gt;</label>
                </div>

                <div class="quiz-question">
                    <p><strong>3. Which tag creates a line break?</strong></p>
                    <label><input type="radio" name="q3" value="a" required> &lt;lb&gt;</label>
                    <label><input type="radio" name="q3" value="b"> &lt;br&gt;</label>
                    <label><input type="radio" name="q3" value="c"> &lt;break&gt;</label>
                </div>

                <div style="border-top: 2px solid #e5e7eb; padding-top: 1.5rem;">
                    <button type="submit" name="submit_quiz" style="width: auto; padding: 0.75rem 2rem; font-size: 1.1rem;">Submit Answers</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
