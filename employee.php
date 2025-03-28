<?php
session_start();
require 'config/dbconnect.php';
$pdo = getDBConnection();
// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get employee ID from URL
$employee_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
try {
    if(isset($employee_id)){
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE employee_id = :employee_id");
    $stmt->execute(['employee_id'=>$employee_id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    }if(!$employee){
        $stmt = $pdo->prepare("SELECT * FROM employees WHERE national_id = :national_id");
        $stmt->execute(['national_id'=>$employee_id]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    if (!$employee) {
        die("الموظف غير موجود في النظام");
    }
} catch (PDOException $e) {
    die("خطأ في قاعدة البيانات: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>معلومات الموظف - GRH Depf</title>
    <link rel="stylesheet" href="CSS/employee.css">
    <link rel="stylesheet" href="CSS/icons.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include 'header.php'; ?>

        <main class="dashboard-main">
            <h1 class="dashboard-title">الملف الشخصي للموظف</h1>
            
            <div class="employee-profile">
                <div class="profile-section">
                    <h2><i class="fas fa-user"></i> المعلومات الشخصية</h2>
                    <div class="info-row">
                        <span class="label">الاسم الكامل (عربي):</span>
                        <span class="value"><?= htmlspecialchars($employee['full_name_ar']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">الاسم الكامل (إنجليزي):</span>
                        <span class="value"><?= htmlspecialchars($employee['full_name_en']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">تاريخ الميلاد:</span>
                        <span class="value"><?= date('d/m/Y', strtotime($employee['birth_date'])) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">مكان الميلاد:</span>
                        <span class="value"><?= htmlspecialchars($employee['birth_place']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">النوع:</span>
                        <span class="value"><?= $employee['gender'] == 'male' ? 'ذكر' : 'أنثى' ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">فصيلة الدم:</span>
                        <span class="value"><?= htmlspecialchars($employee['bloodtype']) ?></span>
                    </div>
                </div>

                <!-- Add this after the last profile-section div -->
                <div class="profile-actions">
                    <a href="document.php?id=<?= $employee['employee_id'] ?>" class="action-button documents-button">
                        <i class="fas fa-file-alt"></i> عرض المستندات
                    </a>
                </div>

                <div class="profile-section">
                    <h2><i class="fas fa-address-card"></i> المعلومات الوظيفية</h2>
                    <div class="info-row">
                        <span class="label">الرقم الوطني:</span>
                        <span class="value"><?= htmlspecialchars($employee['national_id']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">تاريخ التعيين:</span>
                        <span class="value"><?= date('d/m/Y', strtotime($employee['hire_date'])) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">القسم:</span>
                        <span class="value"><?= htmlspecialchars($employee['department']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">المنصب:</span>
                        <span class="value"><?= htmlspecialchars($employee['position']) ?></span>
                    </div>
                </div>

                <div class="profile-section">
                    <h2><i class="fas fa-phone"></i> معلومات الاتصال</h2>
                    <div class="info-row">
                        <span class="label">رقم الهاتف:</span>
                        <span class="value"><?= htmlspecialchars($employee['phone']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">البريد الإلكتروني:</span>
                        <span class="value"><?= htmlspecialchars($employee['email']) ?? 'غير متوفر' ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">العنوان:</span>
                        <span class="value"><?= htmlspecialchars($employee['address']) ?? 'غير متوفر' ?></span>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>