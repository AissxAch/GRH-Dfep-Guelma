<?php
require 'config/dbconnect.php';
$pdo = getDBConnection();

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
$employee_id = isset($_GET['id']) ? $_GET['id'] : null;

// Validate employee ID exists
if (!$employee_id) {
    die("لم يتم تحديد موظف");
}

try {
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE employee_id = :employee_id OR national_id = :national_id");
    $stmt->execute(['employee_id' => $employee_id, 'national_id' => $employee_id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
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
    <title>إستخراج الوثائق- GRH Depf</title>
    <link rel="stylesheet" href="CSS/extract.css">
    <link rel="stylesheet" href="CSS/icons.css">
</head>

<body>
    <div class="dashboard-container">
    <?php include 'header.php'; ?>

        <div class="documnts-container">
            <h1 class="dashboard-title">إستخراج الوثائق للموظف <?= htmlspecialchars($employee['full_name_ar']) ?></h1>
            <div class="documnts-grid">
                <div class="documnt-card">
                    <i class="fas fa-file-alt"></i>
                    <h3>إستخراج شهادة عمل</h3>
                    <p>إستخراج شهادة عمل لموظف معين</p>
                    <a href="work_certificate.php?id=<?= htmlspecialchars($employee_id) ?>" class="btn-primary">إستخراج</a>
                </div>
                <div class="documnt-card">
                    <i class="fas fa-file-alt"></i>
                    <h3>إستخراج مقرر خصم</h3>
                    <p>إستخراج مقرر خصم لموظف معين</p>
                    <a href="deduction_decision.php?id=<?= htmlspecialchars($employee_id) ?>" class="btn-primary">إستخراج</a>
                </div>
                <div class="documnt-card">
                    <i class="fas fa-file-alt"></i>
                    <h3>إستخراج مقرر عطلة سنوية</h3>
                    <p>إستخراج مقرر عطلة سنوية لموظف معين</p>
                    <a href="vacances_annuelles.php?id=<?= htmlspecialchars($employee_id) ?>" class="btn-primary">إستخراج</a>
                </div>
                <div class="documnt-card">
                    <i class="fas fa-file-alt"></i>
                    <h3>إستخراج مقرر عطلة مرضية</h3>
                    <p>إستخراج مقرر عطلة مرضية لموظف معين</p>
                    <a href="vacances_maladie.php?id=<?= htmlspecialchars($employee_id) ?>" class="btn-primary">إستخراج</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>