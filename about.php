<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar">
        <a href="home.php" class="logo">MyApp</a>
        <div class="nav-links">
            <a href="home.php">Home</a>
            <a href="about.php">About</a>
            <?php if(isset($_SESSION['user'])): ?>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="login.php">Login</a>
                <a href="register.php">Register</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="container">
        <h1>About Page</h1>
        <p style="text-align: center; color: #4b5563; margin-top: 1rem;">
            This is a simple authentication system built with PHP and PostgreSQL.
        </p>
        <div style="text-align: center; margin-top: 2rem;">
            <a href="home.php" style="color: #4f46e5; text-decoration: none; font-weight: 500;">&larr; Back Home</a>
        </div>
    </div>
</body>
</html>