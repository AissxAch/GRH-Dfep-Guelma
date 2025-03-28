<?php
session_start();
require_once 'config/dbconnect.php';
$pdo = getDBConnection();

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$errors = [];
$success = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and validate inputs
    $name_ar = trim($_POST['name_ar'] ?? '');
    $name_en = trim($_POST['name_en'] ?? '');

    // Validate required fields
    if (empty($name_ar)) {
        $errors[] = 'اسم القسم بالعربية مطلوب';
    }
    if (empty($name_en)) {
        $errors[] = 'اسم القسم بالإنجليزية مطلوب';
    }

    // Insert if no errors
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO departments (name_ar, name_en) VALUES (?, ?)");
            $stmt->execute([$name_ar, $name_en]);
            
            $success = 'تم إضافة القسم بنجاح';
            // Clear form values
            $name_ar = $name_en = '';
        } catch (PDOException $e) {
            $errors[] = 'خطأ في إضافة القسم: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة قسم جديد - GRH Depf</title>
    <link rel="stylesheet" href="CSS/icons.css">
    <style>
        /* Reuse existing styles from add_employee.css */
        .dashboard-container {
            background: #f5f6fa;
            min-height: 100vh;
            padding: 20px;
        }
        
        .form-container {
            max-width: 800px;
            margin: 2rem auto;
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #2c3e50;
        }
        
        input[type="text"] {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        button[type="submit"] {
            background: #3498db;
            color: white;
            padding: 0.8rem 2rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }
        
        button[type="submit"]:hover {
            background: #2980b9;
        }
        
        .error-message {
            color: #dc3545;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
        }
        
        .success-message {
            color: #28a745;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #c3e6cb;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include 'header.php'; ?>
        
        <main class="dashboard-main">
            <h1 class="dashboard-title">إضافة قسم جديد</h1>
            
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

            <div class="form-container">
                <form method="POST">
                    <div class="form-group">
                        <label for="name_ar">اسم القسم (عربي) <span style="color: red">*</span></label>
                        <input type="text" id="name_ar" name="name_ar" 
                               value="<?= htmlspecialchars($name_ar ?? '') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="name_en">اسم القسم (إنجليزي) <span style="color: red">*</span></label>
                        <input type="text" id="name_en" name="name_en" 
                               value="<?= htmlspecialchars($name_en ?? '') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit">
                            <i class="fas fa-save"></i> حفظ القسم
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>