<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - EduCode Learning Platform</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .hero {
            text-align: center;
            padding: 3rem 1rem;
            background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
            color: #fff;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .hero h1 {
            color: #fff;
            margin-bottom: 1rem;
            font-size: 2.2rem;
        }

        .hero p {
            font-size: 1.2rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }

        .course-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
            margin-bottom: 3rem;
        }

        .card {
            background: #fff;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
            border: 1px solid #e5e7eb;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .card h3 {
            color: #1f2937;
            margin-bottom: 0.5rem;
            font-size: 1.25rem;
        }

        .card p {
            color: #4b5563;
            margin-bottom: 1.5rem;
            line-height: 1.5;
            flex-grow: 1;
        }

        .btn-learn {
            background-color: #f3f4f6;
            color: #4f46e5;
            padding: 0.5rem 1rem;
            text-align: center;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: background-color 0.2s ease;
            width: fit-content;
            border: none;
            cursor: pointer;
            display: inline-block;
        }

        .btn-learn:hover {
            background-color: #e0e7ff;
        }

        /* Dark Mode Specific Styles */
        .dark-mode {
            background-color: #111827 !important;
            color: #f3f4f6 !important;
        }

        .dark-mode .card {
            background: #1f2937;
            border-color: #374151;
        }

        .dark-mode .card h3 {
            color: #f9fafb;
        }

        .dark-mode .card p {
            color: #9ca3af;
        }

        .dark-mode .btn-learn {
            background-color: #374151;
            color: #818cf8;
        }

        .dark-mode .btn-learn:hover {
            background-color: #4b5563;
        }

        .dark-mode .navbar {
            background-color: #1f2937;
            border-bottom: 1px solid #374151;
        }

        .dark-mode .nav-links a {
            color: #d1d5db;
        }

        .dark-mode .nav-links a:hover {
            color: #818cf8;
        }

        .dark-mode .navbar .logo {
            color: #818cf8;
        }

        .dark-mode-toggle {
            background: transparent;
            border: 1px solid #d1d5db;
            color: #4b5563;
            padding: 0.25rem 0.75rem;
            cursor: pointer;
            border-radius: 6px;
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }

        .dark-mode-toggle:hover {
            background: #f3f4f6;
        }

        .dark-mode .dark-mode-toggle {
            border-color: #4b5563;
            color: #d1d5db;
        }

        .dark-mode .dark-mode-toggle:hover {
            background: #374151;
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <a href="home.php" class="logo">EduCode</a>
        <div class="nav-links">
            <a href="home.php">Home</a>
            <a href="admin-dashboard.php">Admin Dashboard</a>
            <a href="logout.php">Logout</a>
            <button id="themeToggle" class="dark-mode-toggle">Dark Mode</button>
        </div>
    </nav>

    <div class="container wide" style="box-shadow: none; background: transparent; padding: 1rem;">

        <div class="hero">
            <h1>Welcome, <?php echo isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'User'; ?>!</h1>
            <p>This website helps students learn programming step by step.</p>
        </div>

        <h2 style="color: #1f2937; margin-top: 2rem; margin-bottom: 1rem; border-bottom: 2px solid #e5e7eb; padding-bottom: 0.5rem;" id="coursesHeader">Available Courses</h2>

        <div class="course-grid">
            <div class="card">
                <h3>HTML Basics</h3>
                <p>Learn the foundation of the web. Build structure for your web pages using semantic HTML tags.</p>
                <a href="html-course.php" class="btn-learn">Start Course</a>
            </div>

            <div class="card">
                <h3>CSS Styling</h3>
                <p>Make your websites look beautiful. Master layout, colors, typography, and responsive design.</p>
                <a href="css-course.php" class="btn-learn">Start Course</a>
            </div>

            <div class="card">
                <h3>JavaScript Fundamentals</h3>
                <p>Add interactivity to your sites. Understand variables, functions, events, and DOM manipulation.</p>
                <a href="javascript-course.php" class="btn-learn">Start Course</a>
            </div>

            <div class="card">
                <h3>React Introduction</h3>
                <p>Build modern user interfaces. Dive into components, state, props, and hooks to create dynamic web apps.</p>
                <a href="react-course.php" class="btn-learn">Start Course</a>
            </div>

            <div class="card">
                <h3>Git Version Control</h3>
                <p>Track your code changes and collaborate with others. Learn branching, committing, pushing, and pulling.</p>
                <a href="git-course.php" class="btn-learn">Start Course</a>
            </div>
        </div>

    </div>

    <script>
        // Dark mode toggle
        const themeToggle = document.getElementById('themeToggle');

        themeToggle.addEventListener('click', () => {
            document.body.classList.toggle('dark-mode');
            const isDark = document.body.classList.contains('dark-mode');

            themeToggle.textContent = isDark ? 'Light Mode' : 'Dark Mode';

            const coursesHeader = document.getElementById('coursesHeader');
            if (coursesHeader) {
                coursesHeader.style.color = isDark ? '#f9fafb' : '#1f2937';
                coursesHeader.style.borderColor = isDark ? '#374151' : '#e5e7eb';
            }
        });
    </script>
</body>

</html>