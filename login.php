<?php
session_start();
include "config.php";

// If already logged in, redirect
if (isset($_SESSION['user'])) {
    header("Location: home.php");
    exit();
}

$error = "";

if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Both fields are required.";
    } else {
        $query = "SELECT * FROM users WHERE email = $1";
        pg_prepare($conn, "login_user", $query);
        $result = pg_execute($conn, "login_user", array($email));

        if (pg_num_rows($result) > 0) {
            $user = pg_fetch_assoc($result);
            if (password_verify($password, $user['password'])) {
                $_SESSION['user'] = $email;
                $_SESSION['name'] = $user['name'];
                header("Location: home.php");
                exit();
            } else {
                $error = "Wrong password.";
            }
        } else {
            $error = "Email not found.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar">
        <a href="home.php" class="logo">MyApp</a>
        <div class="nav-links">
            <a href="home.php">Home</a>
            <a href="about.php">About</a>
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        </div>
    </nav>

    <div class="container">
        <h1>Welcome Back</h1>

        <?php if (!empty($error)): ?>
            <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <input name="email" type="email" placeholder="Email Address" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            <input name="password" type="password" placeholder="Password">
            <button name="login" type="submit">Login</button>
        </form>

        <div class="form-footer">
            Don't have an account? <a href="register.php">Register here</a>
        </div>
    </div>
</body>
</html>