<?php
session_start();
include "config.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$topic = "HTML";
$page_title = "HTML Basics";
$theme_color = "#4f46e5";
$theme_bg = "linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%)";
$msg = "";

// Handle Course Completion
if (isset($_POST['mark_completed'])) {
    $email = $_SESSION['user'];
    $chk = @pg_prepare($conn, "chk_" . $topic, "SELECT id FROM progress WHERE email=$1 AND course_name=$2");
    if ($chk) {
        $res = pg_execute($conn, "chk_" . $topic, array($email, $topic));
        if (pg_num_rows($res) == 0) {
            pg_prepare($conn, "ins_" . $topic, "INSERT INTO progress (email, course_name) VALUES ($1, $2)");
            pg_execute($conn, "ins_" . $topic, array($email, $topic));
            $msg = "Congratulations! You marked $topic as completed.";
        } else {
            $msg = "You have already completed this course.";
        }
    }
}

// Handle Quiz Submission
$quiz_score_msg = "";
if (isset($_POST['submit_quiz'])) {
    $score = 0;
    $total = 0;

    // Fetch all quiz questions for this topic to verify answers
    $verify_q = @pg_query($conn, "SELECT id, answer FROM quizzes WHERE topic='$topic'");
    if ($verify_q) {
        while ($row = pg_fetch_assoc($verify_q)) {
            $total++;
            $qid = $row['id'];
            if (isset($_POST['question_' . $qid]) && $_POST['question_' . $qid] == $row['answer']) {
                $score++;
            }
        }

        // Save score
        $email = $_SESSION['user'];
        $quiz_name = $topic . " Quiz";
        $ins = @pg_prepare($conn, "ins_quiz_score_" . $topic, "INSERT INTO quiz_scores (email, quiz_name, score, total_questions) VALUES ($1, $2, $3, $4)");
        if ($ins) {
            pg_execute($conn, "ins_quiz_score_" . $topic, array($email, $quiz_name, $score, $total));
        }
        $quiz_score_msg = "You scored $score out of $total on the $topic quiz!";
    }
}

// Fetch DB content
$lessons_res = @pg_query($conn, "SELECT * FROM lessons WHERE topic='$topic' ORDER BY created_at ASC");
$quizzes_res = @pg_query($conn, "SELECT * FROM quizzes WHERE topic='$topic' ORDER BY created_at ASC");
$course_note_res = @pg_query($conn, "SELECT note FROM course_notes WHERE topic='$topic'");
$course_note = "";
if ($course_note_res && pg_num_rows($course_note_res) > 0) {
    $course_note = pg_fetch_assoc($course_note_res)['note'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .course-header {
            text-align: center;
            padding: 2.5rem 1rem;
            background: <?php echo $theme_bg; ?>;
            border-radius: 12px;
            margin-bottom: 2rem;
            color: #1f2937;
        }

        .course-content {
            background: #fff;
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
            margin-bottom: 2rem;
        }

        .video-container {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
            overflow: hidden;
            max-width: 100%;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border: 1px solid #e5e7eb;
        }

        .video-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }

        h2 {
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
            color: #111827;
        }

        .quiz-question {
            background: #f9fafb;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border-left: 4px solid <?php echo $theme_color; ?>;
        }

        .quiz-question p {
            margin-bottom: 1rem;
            font-size: 1.1rem;
            color: #1f2937;
            font-weight: 600;
        }

        .quiz-question label {
            display: block;
            margin-bottom: 0.5rem;
            cursor: pointer;
            color: #4b5563;
        }

        .quiz-question input {
            width: auto;
            margin-right: 0.5rem;
        }
    </style>
</head>

<body style="background-color: #f3f4f6;">
    <nav class="navbar">
        <a href="home.php" class="logo">EduCode</a>
        <div class="nav-links">
            <a href="home.php">Home</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <div class="container wide" style="margin-top:2rem;">
        <?php if (!empty($msg)) echo "<div class='alert success'>$msg</div>"; ?>
        <?php if (!empty($quiz_score_msg)) echo "<div class='alert success' style='background-color:#dcfce3; color:#166534;'>$quiz_score_msg</div>"; ?>

        <div class="course-header">
            <h1 style="color: <?php echo $theme_color; ?>; margin-bottom: 0.5rem;"><?php echo $page_title; ?></h1>
            <p style="font-size: 1.1rem; color: #4b5563;">Dynamic Learning Module</p>
        </div>

        <!-- Topic Note -->
        <?php if(!empty($course_note)): ?>
        <div style="background: #e0f2fe; border-left: 4px solid #0ea5e9; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
            <h3 style="color: #0369a1; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                📌 Instructor Note
            </h3>
            <div style="color: #1e293b; line-height: 1.6; white-space: pre-wrap; font-size: 1.05rem;"><?php echo htmlspecialchars($course_note); ?></div>
        </div>
        <?php endif; ?>

        <!-- Render Dynamic Lessons -->
        <?php if ($lessons_res && pg_num_rows($lessons_res) > 0): ?>
            <h2 style="font-size: 1.75rem; color: <?php echo $theme_color; ?>;">Course Lessons</h2>
            <?php $lesson_num = 1;
            while ($lesson = pg_fetch_assoc($lessons_res)): ?>
                <div class="course-content">
                    <h2>Lesson <?php echo $lesson_num++; ?>: <?php echo htmlspecialchars($lesson['title']); ?></h2>

                    <?php
                    $video_link = $lesson['video_link'];

                    // Convert watch URL to embed URL automatically
                    if (strpos($video_link, "watch?v=") !== false) {
                        $video_link = str_replace("watch?v=", "embed/", $video_link);
                    }
                    ?>

                    <?php if (strpos($video_link, 'youtube.com') !== false): ?>
                    <div class="video-container">
                        <iframe src="<?php echo $video_link; ?>" frameborder="0" allowfullscreen></iframe>
                    </div>
                    <?php else: ?>
                    <div class="video-container" style="padding-bottom: 0; height: auto;">
                        <video width="100%" controls style="border-radius: 8px;">
                            <source src="<?php echo htmlspecialchars($video_link); ?>" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($lesson['description'])): ?>
                        <p style="font-size: 1.1rem; color: #374151; line-height: 1.6;"><?php echo nl2br(htmlspecialchars($lesson['description'])); ?></p>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="course-content">
                <p style="text-align:center; color:#6b7280; font-style:italic;">No lessons have been uploaded by the admin for this topic yet.</p>
            </div>
        <?php endif; ?>

        <!-- Render Dynamic Quiz -->
        <?php if ($quizzes_res && pg_num_rows($quizzes_res) > 0): ?>
            <h2 style="font-size: 1.75rem; color: #059669; margin-top: 3rem;">Topic Quiz</h2>
            <div class="course-content" style="border-top: 4px solid #059669;">
                <form method="POST">
                    <?php $q_num = 1;
                    while ($q = pg_fetch_assoc($quizzes_res)): ?>
                        <div class="quiz-question">
                            <p><?php echo $q_num++; ?>. <?php echo htmlspecialchars($q['question']); ?></p>
                            <label><input type="radio" name="question_<?php echo $q['id']; ?>" value="1" required> <?php echo htmlspecialchars($q['option1']); ?></label>
                            <label><input type="radio" name="question_<?php echo $q['id']; ?>" value="2"> <?php echo htmlspecialchars($q['option2']); ?></label>
                            <label><input type="radio" name="question_<?php echo $q['id']; ?>" value="3"> <?php echo htmlspecialchars($q['option3']); ?></label>
                            <label><input type="radio" name="question_<?php echo $q['id']; ?>" value="4"> <?php echo htmlspecialchars($q['option4']); ?></label>
                        </div>
                    <?php endwhile; ?>
                    <button type="submit" name="submit_quiz" style="background-color: #059669; font-size: 1.1rem; padding: 0.75rem 2rem;">Submit Answers</button>
                </form>
            </div>
        <?php endif; ?>

        <!-- Mark Completion -->
        <div style="display: flex; gap: 1rem; align-items: center; justify-content: space-between; margin-top: 2rem; padding-top: 2rem; border-top: 2px solid #e5e7eb;">
            <form method="POST" style="margin: 0;">
                <button type="submit" name="mark_completed" style="background-color: #10b981; font-size: 1.1rem; padding: 0.75rem 2rem;">Mark Topic as Completed</button>
            </form>
            <a href="home.php" style="color:#4f46e5; font-weight: 600; text-decoration: none; padding: 0.75rem 1rem; border: 1px solid #4f46e5; border-radius: 8px;">Back to Dashboard &rarr;</a>
        </div>

    </div>
</body>

</html>