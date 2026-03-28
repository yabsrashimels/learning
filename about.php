<?php
session_start();

// Simulate logged-in user (replace with real auth)
if (!isset($_SESSION['username'])) {
    $_SESSION['username'] = "yeabsra";
}
$username = $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>EduCode Home</title>

<style>
    :root {
        --bg: #f5f6fa;
        --text: #222;
        --card: #fff;
    }

    body.dark {
        --bg: #1e1e2f;
        --text: #eee;
        --card: #2c2c3c;
    }

    body {
        margin: 0;
        font-family: Arial, sans-serif;
        background: var(--bg);
        color: var(--text);
        transition: 0.3s;
    }

    /* Navbar */
    .navbar {
        display: flex;
        justify-content: space-between;
        padding: 15px 30px;
        background: #fff;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    body.dark .navbar {
        background: #2c2c3c;
    }

    .nav-links a {
        margin: 0 10px;
        text-decoration: none;
        color: var(--text);
    }

    button {
        padding: 8px 12px;
        cursor: pointer;
        border-radius: 6px;
        border: none;
        background: #4a6cf7;
        color: white;
    }

    /* Hero */
    .hero {
        max-width: 800px;
        margin: 40px auto;
        padding: 40px;
        border-radius: 15px;
        text-align: center;
        background: linear-gradient(135deg, #4a6cf7, #6a5af9);
        color: white;
    }

    /* Courses */
    .courses {
        max-width: 900px;
        margin: auto;
    }

    .course-grid {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
    }

    .card {
        background: var(--card);
        padding: 20px;
        border-radius: 10px;
        width: 250px;
        transition: 0.3s;
        cursor: pointer;
    }

    .card:hover {
        transform: translateY(-8px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.2);
    }

    .enrolled {
        border: 2px solid green;
    }

</style>
</head>
<body>

<!-- Navbar -->
<div class="navbar">
    <h2>EduCode</h2>
    <div class="nav-links">
        <a href="home.php">Home</a>
        <a href="admin-dashboard.php">Dashboard</a>
        <a href="logout.php">Logout</a>
        <button onclick="toggleDark()">Dark Mode</button>
    </div>
</div>

<!-- Hero -->
<div class="hero">
    <h1>Welcome, <?php echo htmlspecialchars($username); ?>!</h1>
    <p>This website helps students learn programming step by step.</p>
</div>

<!-- Courses -->
<div class="courses">
    <h2>Available Courses</h2>
    <div class="course-grid">

        <div class="card" onclick="enroll(this)">
            <h3>HTML Basics</h3>
            <p>Learn the foundation of the web.</p>
        </div>

        <div class="card" onclick="enroll(this)">
            <h3>CSS Styling</h3>
            <p>Make your website beautiful.</p>
        </div>

        <div class="card" onclick="enroll(this)">
            <h3>JavaScript</h3>
            <p>Add interactivity to your website.</p>
        </div>

    </div>
</div>

<!-- 👨‍💻 Developer Section -->
<div class="courses" style="margin-top:50px;">
    <h2>About the Programmer</h2>

    <div class="card" style="width:100%;">
        <h3>👨‍💻 Programmer: Yeabsra</h3>

        <p>
            This platform is developed by <strong>Yeabsra</strong> to help students 
            learn programming in a simple, clear, and practical way.
        </p>

        <h4>🚀 Strategy</h4>
        <ul>
            <li>Start from basics (HTML, CSS, JavaScript)</li>
            <li>Learn by doing real examples</li>
            <li>Step-by-step structured lessons</li>
            <li>Interactive learning system</li>
        </ul>

        <h4>📞 Contact</h4>
        <p><strong>Phone:</strong> 0900465152</p>
    </div>
</div>

<!-- Footer -->
<footer style="text-align:center; padding:20px; margin-top:40px;">
    <p>© <?php echo date("Y"); ?> EduCode | Developed by Yeabsra</p>
</footer>

<script>
    // Dark Mode Toggle
    function toggleDark() {
        document.body.classList.toggle("dark");
    }

    // Enroll Interaction
    function enroll(card) {
        if (card.classList.contains("enrolled")) {
            card.classList.remove("enrolled");
            alert("Unenrolled!");
        } else {
            card.classList.add("enrolled");
            alert("Enrolled successfully!");
        }
    }
</script>

</body>
</html>