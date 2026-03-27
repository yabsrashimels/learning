<?php
session_start();
include "config.php";

// If already logged in as admin, redirect
if (isset($_SESSION['admin'])) {
    header("Location: admin-dashboard.php");
    exit();
}

$error = "";

if (isset($_POST['admin_login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Both fields are required.";
    } else {
        $query = "SELECT * FROM admins WHERE email = $1";
        $stmt = @pg_prepare($conn, "admin_login", $query);
        if ($stmt) {
            $result = pg_execute($conn, "admin_login", array($email));

            if (pg_num_rows($result) > 0) {
                $admin = pg_fetch_assoc($result);
                if (password_verify($password, $admin['password'])) {
                    $_SESSION['admin'] = $email;
                    header("Location: admin-dashboard.php");
                    exit();
                } else {
                    $error = "Wrong password.";
                }
            } else {
                $error = "Admin email not found.";
            }
        } else {
            $error = "Database configuration error. Did you run the setup SQL?";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal - EduCode</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body style="background-color: #111827;">
    <nav class="navbar" style="background-color: #1f2937; border-bottom: 1px solid #374151; box-shadow: none;">
        <a href="home.php" class="logo" style="color: #818cf8;">EduCode</a>
        <div class="nav-links">
            <a href="home.php" style="color: #d1d5db;">Back to Site</a>
        </div>
    </nav>

    <div class="container" style="background-color: #1f2937; border: 1px solid #374151; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.5);">
        <h1 style="color: #f9fafb;">Admin Access Portal</h1>
        <p style="text-align: center; color: #9ca3af; margin-bottom: 2rem;">Sign in to manage courses and students.</p>

        <?php if (!empty($error)): ?>
            <div class="alert error" style="background: rgba(153, 27, 27, 0.2); border-color: #991b1b; color: #fca5a5;"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <input name="email" type="email" placeholder="Admin Email" style="background-color: #f9fafb;" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            <input name="password" type="password" placeholder="Admin Password" style="background-color: #f9fafb;">
            <button name="admin_login" type="submit" style="background-color: #4f46e5; margin-top: 0.5rem;">Access Dashboard</button>
        </form>

        <div class="form-footer" style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #374151;">
            <p style="color: #6b7280; font-size: 0.85rem;">Login with <code style="color: #e5e7eb; background: #374151; padding: 2px 4px; border-radius: 4px;">yabsrashimels@gmail.com</code> / <code style="color: #e5e7eb; background: #374151; padding: 2px 4px; border-radius: 4px;">
                    <!-- Shimels@123 -->
                </code></p>
        </div>
    </div>
</body>

</html>