<?php
session_start();
require_once 'config/dbconnect.php';
$pdo = getDBConnection();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name'] ?? '');
    
    if (empty($name)) {
        $errors[] = 'اسم الرتبة مطلوب';
    }
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO positions (name) VALUES (?)");
            $stmt->execute([$name]);
            $success = 'تمت إضافة الرتبة بنجاح';
        } catch (PDOException $e) {
            $errors[] = 'خطأ في قاعدة البيانات: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة رتبة جديدة - GRH Depf</title>
    <link rel="stylesheet" href="CSS/add_employee.css">
    <link rel="stylesheet" href="CSS/icons.css">
    <style>
        .error-message { color: #dc3545; padding: 10px; margin: 10px 0; border: 1px solid #f5c6cb; border-radius: 5px; }
        .success-message { color: #28a745; padding: 10px; margin: 10px 0; border: 1px solid #c3e6cb; border-radius: 5px; }
        .required { color: red; }
        .form-section { margin-bottom: 2rem; padding: 1.5rem; background: #f8f9fa; border-radius: 8px; }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include 'header.php'; ?>

        <main class="dashboard-main">
            <h1 class="dashboard-title">إضافة رتبة جديدة</h1>
            
            <?php if (!empty($errors)): ?>
                <div class="error-message">
                    <?php foreach ($errors as $error): ?>
                        <p><?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form class="employee-form" method="POST">
                <div class="form-section">
                    <h2><i class="fas fa-user-tag"></i> معلومات الرتبة</h2>
                    <div class="input-group">
                        <label>اسم الرتبة<span class="required">*</span></label>
                        <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="login-button">
                        <i class="fas fa-save"></i> حفظ البيانات
                    </button>
                    <a href="index.php" class="cancel-button">
                        <i class="fas fa-times"></i> إلغاء
                    </a>
                </div>
            </form>
        </main>
    </div>
</body>
</html>