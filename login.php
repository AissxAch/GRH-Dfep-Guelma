<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}
include 'config/dbconnect.php';
$pdo = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    try {
        $sql = "SELECT * FROM users WHERE username = :username";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['fullname'] = $user['FullName'];
            header('Location: index.php');
            exit();
        } else {
            $error = "اسم المستخدم أو كلمة المرور غير صالحة.";
        }
    } catch (PDOException $e) {
        error_log("Database query failed: " . $e->getMessage());
        die("Database query failed. Please try again later.");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="CSS/login.css">
    <link rel="stylesheet" href="CSS/style.css">
    <title>تسجيل الدخول - GRH Depf</title>
</head>
<body>
    <div class="login-container">
        <div class="login-form">
            <h2>تسجيل الدخول</h2>
            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            <form action="login.php" method="POST">
                <div class="input-group">
                    <i class="uil uil-user"></i>
                    <input type="text" name="username" placeholder="اسم المستخدم" required>
                </div>
                <div class="input-group">
                    <i class="uil uil-lock"></i>
                    <input type="password" name="password" placeholder="كلمة المرور" required>
                </div>
                <button type="submit" class="login-button">تسجيل الدخول</button>
            </form>
            <p>جميع الحقوق محفوظة ل م.ت.ت.م قالمة 2025</p>
        </div>
    </div>
</body>
</html>