<?php
session_start();
include "config.php";

// Admin Protection Check - Only admin can access
if (!isset($_SESSION['admin'])) {
    header("Location: admin-login.php");
    exit();
}

$msg = "";
$msg_type = "success";

// Ensure admin_notes table exists (Silent Creation)
@pg_query($conn, "CREATE TABLE IF NOT EXISTS admin_notes (
    id SERIAL PRIMARY KEY,
    note TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
@pg_query($conn, "CREATE TABLE IF NOT EXISTS course_notes (
    id SERIAL PRIMARY KEY,
    topic VARCHAR(50) UNIQUE NOT NULL,
    note TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Ensure uploads structure
$upload_dir = 'uploads/videos/';
if (!is_dir($upload_dir)) {
    @mkdir($upload_dir, 0777, true);
}

// Check for Delete Actions
if (isset($_POST['delete_lesson'])) {
    $id = intval($_POST['delete_lesson']);
    $stmt = @pg_prepare($conn, "dl", "DELETE FROM lessons WHERE id = $1");
    if ($stmt) {
        pg_execute($conn, "dl", array($id));
        $msg = "Lesson deleted successfully.";
    }
}
if (isset($_POST['delete_quiz'])) {
    $id = intval($_POST['delete_quiz']);
    $stmt = @pg_prepare($conn, "dq", "DELETE FROM quizzes WHERE id = $1");
    if ($stmt) {
        pg_execute($conn, "dq", array($id));
        $msg = "Quiz question deleted successfully.";
    }
}
if (isset($_POST['delete_note'])) {
    $id = intval($_POST['delete_note']);
    $stmt = @pg_prepare($conn, "dn", "DELETE FROM admin_notes WHERE id = $1");
    if ($stmt) {
        pg_execute($conn, "dn", array($id));
        $msg = "Admin note deleted successfully.";
    }
}

// Check for Edit Actions
if (isset($_POST['edit_lesson'])) {
    $id = intval($_POST['lesson_id']);
    $topic = trim($_POST['topic']);
    $title = trim($_POST['title']);
    $desc = trim($_POST['description']);
    $video_url = trim($_POST['video_link']);

    $stmt = @pg_prepare($conn, "ul", "UPDATE lessons SET topic=$1, title=$2, description=$3, video_link=$4 WHERE id=$5");
    if ($stmt) {
        pg_execute($conn, "ul", array($topic, $title, $desc, $video_url, $id));
        $msg = "Lesson updated successfully.";
    }
}

if (isset($_POST['edit_quiz'])) {
    $id = intval($_POST['quiz_id']);
    $topic = trim($_POST['topic']);
    $question = trim($_POST['question']);
    $opt1 = trim($_POST['option1']);
    $opt2 = trim($_POST['option2']);
    $opt3 = trim($_POST['option3']);
    $opt4 = trim($_POST['option4']);
    $answer = trim($_POST['answer']);

    $stmt = @pg_prepare($conn, "uq", "UPDATE quizzes SET topic=$1, question=$2, option1=$3, option2=$4, option3=$5, option4=$6, answer=$7 WHERE id=$8");
    if ($stmt) {
        pg_execute($conn, "uq", array($topic, $question, $opt1, $opt2, $opt3, $opt4, $answer, $id));
        $msg = "Quiz question updated successfully.";
    }
}

if (isset($_POST['add_note'])) {
    $note = trim($_POST['note']);
    if (!empty($note)) {
        $stmt = @pg_prepare($conn, "inote", "INSERT INTO admin_notes (note) VALUES ($1)");
        if ($stmt) {
            pg_execute($conn, "inote", array($note));
            $msg = "Note successfully added!";
        }
    }
}

if (isset($_POST['save_course_note'])) {
    $topic = trim($_POST['topic']);
    $note = trim($_POST['note']);
    if (!empty($topic) && !empty($note)) {
        $q = "INSERT INTO course_notes (topic, note) VALUES ($1, $2) ON CONFLICT (topic) DO UPDATE SET note = EXCLUDED.note, created_at = CURRENT_TIMESTAMP";
        $stmt = @pg_prepare($conn, "upsert_course_note_" . time(), $q);
        if ($stmt) {
            pg_execute($conn, "upsert_course_note_" . time(), array($topic, $note));
            $msg = "Course note for $topic saved successfully!";
        }
    }
}

if (isset($_POST['delete_course_note'])) {
    $id = intval($_POST['delete_course_note']);
    $stmt = @pg_prepare($conn, "dcn", "DELETE FROM course_notes WHERE id = $1");
    if ($stmt) {
        pg_execute($conn, "dcn", array($id));
        $msg = "Course note deleted successfully.";
    }
}

// Handle Lesson Upload (Create)
if (isset($_POST['upload_lesson'])) {
    $topic = trim($_POST['topic']);
    $title = trim($_POST['title']);
    $desc = trim($_POST['description']);
    $video_url = trim($_POST['video_link']);
    $video = "";

    if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] == 0) {
        $ext = pathinfo($_FILES['video_file']['name'], PATHINFO_EXTENSION);
        if (strtolower($ext) == 'mp4') {
            $filename = time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "", basename($_FILES['video_file']['name']));
            $target_file = $upload_dir . $filename;
            if (move_uploaded_file($_FILES['video_file']['tmp_name'], $target_file)) {
                $video = $target_file; 
            } else {
                $msg = "File upload failed.";
                $msg_type = "error";
            }
        } else {
            $msg = "Only MP4 files are permitted.";
            $msg_type = "error";
        }
    } else {
        $video = $video_url;
        // Convert YouTube format automatically
        if (strpos($video, "youtu.be/") !== false) {
            $video_id = explode("youtu.be/", $video)[1];
            $video_id = explode("?", $video_id)[0];
            $video = "https://www.youtube.com/embed/" . $video_id;
        }
        if (strpos($video, "watch?v=") !== false) {
            $video_id = explode("watch?v=", $video)[1];
            $video_id = explode("&", $video_id)[0];
            $video = "https://www.youtube.com/embed/" . $video_id;
        }
    }

    if (!empty($title) && !empty($topic) && $msg_type != "error") {
        $q = "INSERT INTO lessons (topic, title, description, video_link) VALUES ($1, $2, $3, $4)";
        $stmt = @pg_prepare($conn, "insert_lesson", $q);
        if ($stmt) {
            pg_execute($conn, "insert_lesson", array($topic, $title, $desc, $video));
            $msg = "Lesson successfully added to $topic module!";
            echo "<script>window.onload = function() { alert('Success: " . addslashes($msg) . "'); };</script>";
        } else {
            $msg = "Error preparing lesson insert. Check table structure.";
            $msg_type = "error";
        }
    }
}

// Handle Quiz Question Upload (Create)
if (isset($_POST['add_quiz'])) {
    $topic = trim($_POST['topic']);
    $question = trim($_POST['question']);
    $opt1 = trim($_POST['option1']);
    $opt2 = trim($_POST['option2']);
    $opt3 = trim($_POST['option3']);
    $opt4 = trim($_POST['option4']);
    $answer = trim($_POST['answer']);

    if (!empty($question) && !empty($topic) && !empty($opt1) && !empty($answer)) {
        $q = "INSERT INTO quizzes (topic, question, option1, option2, option3, option4, answer) VALUES ($1, $2, $3, $4, $5, $6, $7)";
        $stmt = @pg_prepare($conn, "insert_quiz", $q);
        if ($stmt) {
            pg_execute($conn, "insert_quiz", array($topic, $question, $opt1, $opt2, $opt3, $opt4, $answer));
            $msg = "Quiz question successfully added to $topic module!";
            echo "<script>window.onload = function() { alert('Success: " . addslashes($msg) . "'); };</script>";
        } else {
            $msg = "Error preparing quiz insert. Check table structure.";
            $msg_type = "error";
        }
    }
}

// Live Fetches
$users_res = @pg_query($conn, "SELECT * FROM users ORDER BY created_at DESC");
$progress_res = @pg_query($conn, "SELECT * FROM progress ORDER BY completed_date DESC");

$lessons_list = @pg_query($conn, "SELECT * FROM lessons ORDER BY created_at DESC");
$quizzes_list = @pg_query($conn, "SELECT * FROM quizzes ORDER BY created_at DESC");
$notes_list = @pg_query($conn, "SELECT * FROM admin_notes ORDER BY created_at DESC");
$course_notes_list = @pg_query($conn, "SELECT * FROM course_notes ORDER BY created_at DESC");

// Fetch into associative arrays for modal data injection
$lessons_array = [];
if($lessons_list){
    while($row = pg_fetch_assoc($lessons_list)) {
        $lessons_array[] = $row;
    }
    pg_result_seek($lessons_list, 0); // reset pointer for iteration
}

$quizzes_array = [];
if($quizzes_list){
    while($row = pg_fetch_assoc($quizzes_list)) {
        $quizzes_array[] = $row;
    }
    pg_result_seek($quizzes_list, 0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Professional Dashboard - EduCode</title>
    <!-- Use Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --secondary: #10b981;
            --secondary-hover: #059669;
            --danger: #ef4444;
            --bg: #f3f4f6;
            --surface: #ffffff;
            --text-main: #111827;
            --text-light: #6b7280;
            --border: #e5e7eb;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: var(--bg);
            color: var(--text-main);
        }

        .navbar {
            background: linear-gradient(90deg, #1e1b4b 0%, #312e81 100%);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .navbar .logo {
            color: #fff;
            font-size: 1.5rem;
            font-weight: 700;
            text-decoration: none;
            letter-spacing: -0.5px;
        }

        .nav-links a {
            color: #d1d5db;
            text-decoration: none;
            margin-left: 1.5rem;
            font-weight: 500;
            transition: color 0.2s;
        }

        .nav-links a:hover {
            color: #fff;
        }

        .nav-links .logout {
            color: #fca5a5;
        }

        .nav-links .logout:hover {
            color: #f87171;
        }

        .container {
            max-width: 1300px;
            margin: 2.5rem auto;
            padding: 0 1.5rem;
        }

        /* Improved Tabs */
        .tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            background: var(--surface);
            padding: 0.5rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow-x: auto;
        }

        .tab {
            background: transparent;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            color: var(--text-light);
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .tab.active {
            background: var(--primary);
            color: #fff;
            box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.4);
        }

        .tab:hover:not(.active) {
            background: #f3f4f6;
            color: var(--text-main);
        }

        .dashboard-section {
            display: none;
            animation: fadeIn 0.4s ease-out forwards;
        }

        .dashboard-section.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Cards & Forms */
        .card {
            background: var(--surface);
            padding: 2rem;
            border-radius: 16px;
            border: 1px solid var(--border);
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
            transition: transform 0.2s;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05);
        }

        .card-title {
            color: var(--text-main);
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-main);
            font-size: 0.95rem;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 0.95rem;
            outline: none;
            transition: all 0.2s ease;
            background-color: #f9fafb;
        }

        .form-control:focus {
            border-color: var(--primary);
            background-color: #fff;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .btn {
            display: inline-block;
            background: var(--primary);
            color: #fff;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-align: center;
            text-decoration: none;
        }

        .btn:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
        }

        .btn-success { background: var(--secondary); }
        .btn-success:hover { background: var(--secondary-hover); }
        .btn-danger { background: var(--danger); }
        .btn-danger:hover { background: #dc2626; }
        .btn-sm { padding: 0.4rem 0.8rem; font-size: 0.85rem; }

        .form-row {
            display: flex;
            gap: 1rem;
        }
        .form-row > div { flex: 1; }

        .split-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        @media (max-width: 900px) {
            .split-layout { grid-template-columns: 1fr; }
        }

        /* Improved Tables */
        .table-responsive {
            overflow-x: auto;
            border-radius: 8px;
            border: 1px solid var(--border);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
        }

        th {
            background: #f9fafb;
            color: var(--text-light);
            font-weight: 600;
            text-align: left;
            padding: 1rem;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid var(--border);
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid var(--border);
            color: var(--text-main);
            font-size: 0.95rem;
            vertical-align: middle;
        }

        tr:last-child td { border-bottom: none; }
        tr:hover td { background-color: #f9fafb; }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
            background: #e0e7ff;
            color: var(--primary);
        }

        .badge-success { background: #d1fae5; color: #059669; }

        .action-flex {
            display: flex;
            gap: 0.5rem;
        }

        /* Modal styling */
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 100;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
        }

        .modal.open {
            display: flex;
        }

        .modal-content {
            background: #fff;
            padding: 2.5rem;
            border-radius: 16px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            animation: modalIn 0.3s ease-out;
            max-height: 90vh;
            overflow-y: auto;
        }

        @keyframes modalIn {
            from { transform: scale(0.95); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        .alert.success { background: #d1fae5; color: #065f46; border-left: 4px solid #10b981; }
        .alert.error { background: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }

    </style>
</head>
<body>
    <nav class="navbar">
        <a href="admin-dashboard.php" class="logo">EduCode Admin Panel</a>
        <div class="nav-links">
            <a href="home.php" target="_blank">View Live Site</a>
            <a href="logout.php" class="logout">Secure Logout</a>
        </div>
    </nav>

    <div class="container">
        <?php if (!empty($msg)) {
            echo "<div class='alert " . $msg_type . "'>$msg</div>";
        } ?>

        <div class="tabs">
            <button class="tab active" onclick="switchTab('manage-content', this)">Manage Content</button>
            <button class="tab" onclick="switchTab('manage-quizzes', this)">Manage Quizzes</button>
            <button class="tab" onclick="switchTab('view-students', this)">Student Analytics</button>
            <button class="tab" onclick="switchTab('admin-notes', this)">Admin Notes</button>
            <button class="tab" onclick="switchTab('course-notes', this)">Course Notes</button>
        </div>

        <!-- LESSONS SECTION -->
        <div id="manage-content" class="dashboard-section active">
            <div class="split-layout">
                <div>
                    <div class="card">
                        <div class="card-title">Upload New Lesson</div>
                        <form method="POST" enctype="multipart/form-data">
                            <div class="form-group">
                                <label>Course Module:</label>
                                <select name="topic" class="form-control" required>
                                    <option value="">-- Choose a Topic --</option>
                                    <option value="HTML">HTML</option>
                                    <option value="CSS">CSS</option>
                                    <option value="JavaScript">JavaScript</option>
                                    <option value="React">React</option>
                                    <option value="Git">Git</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Lesson Title:</label>
                                <input type="text" name="title" class="form-control" placeholder="E.g. Introduction to Variables" required>
                            </div>
                            <div class="form-group">
                                <label>Lesson Description:</label>
                                <textarea name="description" class="form-control" rows="3" placeholder="Brief explanation of the lesson..."></textarea>
                            </div>
                            
                            <div style="background: #f9fafb; padding: 1.5rem; border-radius: 8px; border: 1px dashed var(--border); margin-bottom: 1.5rem;">
                                <div class="form-group">
                                    <label>Upload Video (MP4 only):</label>
                                    <input type="file" name="video_file" class="form-control" accept="video/mp4">
                                </div>
                                <div style="text-align: center; color: var(--text-light); margin: 0.5rem 0;">OR</div>
                                <div class="form-group mb-0">
                                    <label>YouTube Embed Link:</label>
                                    <input type="url" name="video_link" class="form-control" placeholder="https://www.youtube.com/watch?v=...">
                                </div>
                            </div>
                            <button type="submit" name="upload_lesson" class="btn btn-success" style="width: 100%;">Push Lesson to Live Database</button>
                        </form>
                    </div>
                </div>

                <div>
                    <div class="card">
                        <div class="card-title">Live Uploaded Lessons</div>
                        <div class="table-responsive" style="max-height: 600px;">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Details</th>
                                        <th style="text-align:right;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($lessons_list && pg_num_rows($lessons_list) > 0): 
                                        pg_result_seek($lessons_list, 0);
                                        while ($l = pg_fetch_assoc($lessons_list)): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge"><?php echo htmlspecialchars($l['topic']); ?></span><br>
                                                    <strong style="display:block; margin: 0.4rem 0;"><?php echo htmlspecialchars($l['title']); ?></strong>
                                                    <span style="font-size: 0.8rem; color: #9ca3af;"><?php echo date('M j, Y', strtotime($l['created_at'])); ?></span>
                                                </td>
                                                <td style="text-align:right;">
                                                    <div class="action-flex" style="justify-content: flex-end;">
                                                        <button onclick="openEditLesson(<?php echo $l['id']; ?>)" class="btn btn-sm">Edit</button>
                                                        <form method="POST" style="display:inline;" onsubmit="return confirm('WARNING: Are you sure you want to completely delete this lesson?');">
                                                            <input type="hidden" name="delete_lesson" value="<?php echo $l['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-danger">Del</button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile;
                                    else: ?>
                                        <tr>
                                            <td colspan="2" style="text-align:center;">No lessons found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- QUIZZES SECTION -->
        <div id="manage-quizzes" class="dashboard-section">
            <div class="split-layout">
                <div>
                    <div class="card" style="border-top: 4px solid var(--secondary);">
                        <div class="card-title">Add Quiz Question</div>
                        <form method="POST">
                            <div class="form-group">
                                <label>Course Module:</label>
                                <select name="topic" class="form-control" required>
                                    <option value="">-- Choose a Topic --</option>
                                    <option value="HTML">HTML</option>
                                    <option value="CSS">CSS</option>
                                    <option value="JavaScript">JavaScript</option>
                                    <option value="React">React</option>
                                    <option value="Git">Git</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Question:</label>
                                <textarea name="question" rows="2" class="form-control" placeholder="Enter your question here..." required></textarea>
                            </div>

                            <div class="form-row">
                                <div class="form-group"><label>Option 1:</label><input type="text" name="option1" class="form-control" required></div>
                                <div class="form-group"><label>Option 2:</label><input type="text" name="option2" class="form-control" required></div>
                            </div>
                            <div class="form-row">
                                <div class="form-group"><label>Option 3:</label><input type="text" name="option3" class="form-control" required></div>
                                <div class="form-group"><label>Option 4:</label><input type="text" name="option4" class="form-control" required></div>
                            </div>

                            <div class="form-group">
                                <label>Correct Answer:</label>
                                <select name="answer" class="form-control" required>
                                    <option value="">-- Select Answer Box --</option>
                                    <option value="1">Option 1</option>
                                    <option value="2">Option 2</option>
                                    <option value="3">Option 3</option>
                                    <option value="4">Option 4</option>
                                </select>
                            </div>
                            <button type="submit" name="add_quiz" class="btn btn-success" style="width: 100%;">Add Question</button>
                        </form>
                    </div>
                </div>

                <div>
                    <div class="card">
                        <div class="card-title">Live Uploaded Questions</div>
                        <div class="table-responsive" style="max-height: 600px;">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Question Preview</th>
                                        <th style="text-align:right;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($quizzes_list && pg_num_rows($quizzes_list) > 0): 
                                        pg_result_seek($quizzes_list, 0);
                                        while ($q = pg_fetch_assoc($quizzes_list)): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge badge-success"><?php echo htmlspecialchars($q['topic']); ?></span><br>
                                                    <span style="font-size:0.9rem; margin-top:0.4rem; display:block;"><?php echo htmlspecialchars(strlen($q['question']) > 50 ? substr($q['question'], 0, 50) . '...' : $q['question']); ?></span>
                                                </td>
                                                <td style="text-align:right; white-space:nowrap;">
                                                    <div class="action-flex" style="justify-content: flex-end;">
                                                        <button onclick="openEditQuiz(<?php echo $q['id']; ?>)" class="btn btn-sm">Edit</button>
                                                        <form method="POST" style="display:inline;" onsubmit="return confirm('WARNING: Delete this quiz question permanently?');">
                                                            <input type="hidden" name="delete_quiz" value="<?php echo $q['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-danger">Del</button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile;
                                    else: ?>
                                        <tr>
                                            <td colspan="2" style="text-align:center;">No questions found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- STUDENTS SECTION -->
        <div id="view-students" class="dashboard-section">
            <div class="split-layout">
                <div class="card">
                    <div class="card-title">Student Course Completions</div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student Email</th>
                                    <th>Course</th>
                                    <th>Completed On</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($progress_res && pg_num_rows($progress_res) > 0): while ($p = pg_fetch_assoc($progress_res)): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($p['email']); ?></strong></td>
                                            <td><span class="badge"><?php echo htmlspecialchars($p['course_name']); ?></span></td>
                                            <td style="color:var(--text-light); font-size:0.85rem;"><?php echo date('M j, Y', strtotime($p['completed_date'])); ?></td>
                                        </tr>
                                    <?php endwhile;
                                else: ?>
                                    <tr><td colspan="3" style="text-align:center;">No progress to show.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-title">Directory of Users</div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Contact Info</th>
                                    <th>Joined</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($users_res && pg_num_rows($users_res) > 0): while ($row = pg_fetch_assoc($users_res)): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                                            <td>
                                                <div><?php echo htmlspecialchars($row['email']); ?></div>
                                                <div style="font-size:0.8rem; color:var(--text-light);"><?php echo htmlspecialchars($row['phone']); ?></div>
                                            </td>
                                            <td style="color:var(--text-light); font-size:0.85rem;"><?php echo date('M j, Y', strtotime($row['created_at'])); ?></td>
                                        </tr>
                                <?php endwhile;
                                endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- COURSE NOTES SECTION -->
        <div id="course-notes" class="dashboard-section">
            <div class="split-layout">
                <div class="card">
                    <div class="card-title">Manage Topic Note</div>
                    <form method="POST">
                        <div class="form-group">
                            <label>Course Topic:</label>
                            <select name="topic" class="form-control" required>
                                <option value="">-- Select a Topic --</option>
                                <option value="HTML">HTML</option>
                                <option value="CSS">CSS</option>
                                <option value="JavaScript">JavaScript</option>
                                <option value="React">React</option>
                                <option value="Git">Git</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Note for Students:</label>
                            <textarea name="note" rows="5" class="form-control" placeholder="Write a note that will appear for all students taking this course topic..." required></textarea>
                        </div>
                        <button type="submit" name="save_course_note" class="btn btn-success" style="width:100%;">Save Topic Note</button>
                    </form>
                </div>
                
                <div class="card">
                    <div class="card-title">Active Topic Notes</div>
                    <div class="table-responsive">
                        <table>
                            <tbody>
                                <?php if ($course_notes_list && pg_num_rows($course_notes_list) > 0): while ($cn = pg_fetch_assoc($course_notes_list)): ?>
                                        <tr>
                                            <td style="vertical-align:top;">
                                                <span class="badge badge-success"><?php echo htmlspecialchars($cn['topic']); ?></span>
                                                <div style="white-space:pre-wrap; font-size:0.95rem; line-height:1.5; color:var(--text-main); margin-top:0.8rem;"><?php echo htmlspecialchars($cn['note']); ?></div>
                                            </td>
                                            <td style="text-align:right; vertical-align:top; width:60px;">
                                                <form method="POST" onsubmit="return confirm('WARNING: Are you sure you want to delete this topic note?');">
                                                    <input type="hidden" name="delete_course_note" value="<?php echo $cn['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">&times;</button>
                                                </form>
                                            </td>
                                        </tr>
                                <?php endwhile; else: ?>
                                    <tr><td colspan="2" style="text-align:center;">No topic notes added.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ADMIN NOTES SECTION -->
        <div id="admin-notes" class="dashboard-section">
            <div class="split-layout">
                <div class="card">
                    <div class="card-title">Create Private Note</div>
                    <form method="POST">
                        <div class="form-group">
                            <label>Note Content:</label>
                            <textarea name="note" rows="5" class="form-control" placeholder="Jot down tasks, ideas, or reminders strictly for admin view..." required></textarea>
                        </div>
                        <button type="submit" name="add_note" class="btn" style="width:100%;">Save Secure Note</button>
                    </form>
                </div>
                
                <div class="card">
                    <div class="card-title">Saved Admin Notes</div>
                    <div class="table-responsive">
                        <table>
                            <tbody>
                                <?php if ($notes_list && pg_num_rows($notes_list) > 0): while ($n = pg_fetch_assoc($notes_list)): ?>
                                        <tr>
                                            <td style="vertical-align:top;">
                                                <div style="white-space:pre-wrap; font-size:0.95rem; line-height:1.5; color:var(--text-main); margin-bottom:0.8rem;"><?php echo htmlspecialchars($n['note']); ?></div>
                                                <div style="font-size:0.8rem; color:var(--text-light);"><?php echo date('F j, Y, g:i a', strtotime($n['created_at'])); ?></div>
                                            </td>
                                            <td style="text-align:right; vertical-align:top; width:60px;">
                                                <form method="POST" onsubmit="return confirm('WARNING: Are you sure you want to delete this note?');">
                                                    <input type="hidden" name="delete_note" value="<?php echo $n['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">&times;</button>
                                                </form>
                                            </td>
                                        </tr>
                                <?php endwhile; else: ?>
                                    <tr><td colspan="2" style="text-align:center;">No private notes saved.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Modals for Editing -->
    <div id="editLessonModal" class="modal">
        <div class="modal-content">
            <h3 style="margin-bottom: 1.5rem;">Edit Lesson</h3>
            <form method="POST">
                <input type="hidden" name="lesson_id" id="e_l_id">
                <div class="form-group">
                    <label>Course Module:</label>
                    <select name="topic" id="e_l_topic" class="form-control" required>
                        <option value="HTML">HTML</option>
                        <option value="CSS">CSS</option>
                        <option value="JavaScript">JavaScript</option>
                        <option value="React">React</option>
                        <option value="Git">Git</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Lesson Title:</label>
                    <input type="text" name="title" id="e_l_title" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Lesson Description:</label>
                    <textarea name="description" id="e_l_desc" class="form-control" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label>Video URL / Link:</label>
                    <input type="text" name="video_link" id="e_l_video" class="form-control">
                </div>
                <div class="form-row">
                    <button type="button" class="btn" onclick="closeModals()" style="background:var(--text-light);">Cancel</button>
                    <button type="submit" name="edit_lesson" class="btn btn-success">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editQuizModal" class="modal">
        <div class="modal-content">
            <h3 style="margin-bottom: 1.5rem;">Edit Quiz Question</h3>
            <form method="POST">
                <input type="hidden" name="quiz_id" id="e_q_id">
                <div class="form-group">
                    <label>Course Module:</label>
                    <select name="topic" id="e_q_topic" class="form-control" required>
                        <option value="HTML">HTML</option>
                        <option value="CSS">CSS</option>
                        <option value="JavaScript">JavaScript</option>
                        <option value="React">React</option>
                        <option value="Git">Git</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Question:</label>
                    <textarea name="question" id="e_q_question" class="form-control" rows="2" required></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Option 1:</label><input class="form-control" type="text" name="option1" id="e_q_opt1"></div>
                    <div class="form-group"><label>Option 2:</label><input class="form-control" type="text" name="option2" id="e_q_opt2"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Option 3:</label><input class="form-control" type="text" name="option3" id="e_q_opt3"></div>
                    <div class="form-group"><label>Option 4:</label><input class="form-control" type="text" name="option4" id="e_q_opt4"></div>
                </div>
                <div class="form-group">
                    <label>Correct Answer:</label>
                    <select name="answer" id="e_q_ans" class="form-control" required>
                        <option value="1">Option 1</option>
                        <option value="2">Option 2</option>
                        <option value="3">Option 3</option>
                        <option value="4">Option 4</option>
                    </select>
                </div>
                <div class="form-row">
                    <button type="button" class="btn" onclick="closeModals()" style="background:var(--text-light);">Cancel</button>
                    <button type="submit" name="edit_quiz" class="btn btn-success">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const lessonsData = <?php echo json_encode($lessons_array); ?>;
        const quizzesData = <?php echo json_encode($quizzes_array); ?>;

        function switchTab(tabId, btn) {
            document.querySelectorAll('.dashboard-section').forEach(section => {
                section.classList.remove('active');
            });
            document.getElementById(tabId).classList.add('active');

            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            btn.classList.add('active');
        }

        function openEditLesson(id) {
            const lesson = lessonsData.find(l => parseInt(l.id) === id);
            if(lesson) {
                document.getElementById('e_l_id').value = lesson.id;
                document.getElementById('e_l_topic').value = lesson.topic;
                document.getElementById('e_l_title').value = lesson.title;
                document.getElementById('e_l_desc').value = lesson.description;
                document.getElementById('e_l_video').value = lesson.video_link;
                document.getElementById('editLessonModal').classList.add('open');
            }
        }

        function openEditQuiz(id) {
            const quiz = quizzesData.find(q => parseInt(q.id) === id);
            if(quiz) {
                document.getElementById('e_q_id').value = quiz.id;
                document.getElementById('e_q_topic').value = quiz.topic;
                document.getElementById('e_q_question').value = quiz.question;
                document.getElementById('e_q_opt1').value = quiz.option1;
                document.getElementById('e_q_opt2').value = quiz.option2;
                document.getElementById('e_q_opt3').value = quiz.option3;
                document.getElementById('e_q_opt4').value = quiz.option4;
                document.getElementById('e_q_ans').value = quiz.answer;
                document.getElementById('editQuizModal').classList.add('open');
            }
        }

        function closeModals() {
            document.getElementById('editLessonModal').classList.remove('open');
            document.getElementById('editQuizModal').classList.remove('open');
        }
    </script>
</body>
</html>