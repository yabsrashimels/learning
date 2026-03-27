<?php
session_start();
include "config.php";

$error = "";
$success = "";

if (isset($_POST['register'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $phone = trim($_POST['phone']);

    if (empty($name) || empty($email) || empty($password) || empty($phone)) {
        $error = "All fields are required.";
    } else {
        // Check for duplicate email
        $check_query = "SELECT email FROM users WHERE email = $1";
        pg_prepare($conn, "check_email", $check_query);
        $check_result = pg_execute($conn, "check_email", array($email));

        if (pg_num_rows($check_result) > 0) {
            $error = "Email is already registered.";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert_query = "INSERT INTO users(name, email, password, phone, created_at) VALUES($1, $2, $3, $4, NOW())";
            pg_prepare($conn, "insert_user", $insert_query);
            $insert_result = pg_execute($conn, "insert_user", array($name, $email, $hashed_password, $phone));

            if ($insert_result) {
                // Auto login
                $_SESSION['user'] = $email;
                $_SESSION['name'] = $name;
                header("Location: home.php");
                exit();
            } else {
                $error = "Failed to register. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
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
        <h1>Create an Account</h1>
        
        <?php if (!empty($error)): ?>
            <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <input name="name" placeholder="Full Name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
            <input name="email" type="email" placeholder="Email Address" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            <input name="password" type="password" placeholder="Password">
            <input name="phone" placeholder="Phone Number" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
            <button name="register" type="submit">Register</button>
        </form>
        
        <div class="form-footer">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </div>
</body>
</html>