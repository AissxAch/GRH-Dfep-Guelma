<?php
session_start();
require_once 'config/dbconnect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Initialize variables
$error = '';
$employee_id = '';
$employee_data = null;

// Get employee ID from URL parameter
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $employee_id = (int)$_GET['id'];
    
    try {
        $pdo = getDBConnection();
        
        // Get employee data for confirmation display
        $stmt = $pdo->prepare("SELECT * FROM employees WHERE employee_id = ?");
        $stmt->execute([$employee_id]);
        $employee_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$employee_data) {
            $error = "الموظف غير موجود في النظام";
        }
    } catch (PDOException $e) {
        $error = "خطأ في قاعدة البيانات: " . $e->getMessage();
    }
} else {
    $error = "معرف الموظف غير صالح";
}

// Handle delete confirmation
// Handle delete confirmation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_delete'])) {
    try {
        $pdo->beginTransaction();
        
        // Store employee name for activity log
        $employee_name = $employee_data['firstname_ar'] . ' ' . $employee_data['lastname_ar'];
        
        // Log the deletion
        $log_stmt = $pdo->prepare("INSERT INTO activity_log (employee_id, activity_type, details, changed_by) 
                                   VALUES (?, 'delete', ?, ?)");
        $log_stmt->execute([
            $employee_id,
            "تم حذف الموظف: " . $employee_name,
            $_SESSION['user_id']
        ]);
        
        // Delete related records
        $pdo->prepare("DELETE FROM employee_previous_positions WHERE employee_id = ?")->execute([$employee_id]);
        $pdo->prepare("DELETE FROM employee_high_level_history WHERE employee_id = ?")->execute([$employee_id]);
        $pdo->prepare("DELETE FROM employee_documents WHERE employee_id = ?")->execute([$employee_id]);
        
        // Delete the employee
        $stmt = $pdo->prepare("DELETE FROM employees WHERE employee_id = ?");
        $stmt->execute([$employee_id]);
        
        $pdo->commit();
        
        $_SESSION['success_message'] = "تم حذف الموظف بنجاح";
        header('Location: index.php');
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "خطأ في حذف الموظف: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>حذف موظف - GRH Depf</title>
    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="CSS/icons.css">
    <style>
        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .error-message {
            color: #dc3545;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
        }
        .employee-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .actions {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include 'header.php'; ?>

        <main class="container">
            <h1>حذف موظف</h1>
            
            <?php if ($error): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
                <div class="actions">
                    <a href="index.php" class="btn btn-secondary">العودة إلى القائمة</a>
                </div>
            <?php elseif ($employee_data): ?>
                <div class="employee-info">
                    <h3>معلومات الموظف</h3>
                    <p><strong>الاسم:</strong> <?= htmlspecialchars($employee_data['firstname_ar'] . ' ' . $employee_data['lastname_ar']) ?></p>
                    <p><strong>الرقم الوطني:</strong> <?= htmlspecialchars($employee_data['national_id']) ?></p>
                    <p><strong>المنصب:</strong> <?= htmlspecialchars($employee_data['position']) ?></p>
                    <p><strong>القسم:</strong> 
                        <?php 
                        if ($employee_data['department_id']) {
                            $dept_stmt = $pdo->prepare("SELECT name FROM departments WHERE department_id = ?");
                            $dept_stmt->execute([$employee_data['department_id']]);
                            $dept = $dept_stmt->fetch(PDO::FETCH_ASSOC);
                            echo htmlspecialchars($dept['name'] ?? 'غير معروف');
                        } else {
                            echo 'غير محدد';
                        }
                        ?>
                    </p>
                </div>
                
                <div class="warning-message">
                    <h3>تحذير!</h3>
                    <p>هل أنت متأكد أنك تريد حذف هذا الموظف؟ هذه العملية لا يمكن التراجع عنها.</p>
                    <p>سيتم حذف جميع المعلومات المرتبطة بهذا الموظف بما في ذلك:</p>
                    <ul>
                        <li>الوظائف السابقة</li>
                        <li>المناصب العليا</li>
                        <li>الوثائق المرفقة</li>
                    </ul>
                </div>
                
                <form method="POST">
                    <div class="actions">
                        <a href="index.php" class="btn btn-secondary">إلغاء</a>
                        <button type="submit" name="confirm_delete" class="btn btn-danger">تأكيد الحذف</button>
                    </div>
                </form>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>