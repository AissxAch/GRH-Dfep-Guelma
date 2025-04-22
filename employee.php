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
    // Get employee data
    $stmt = $pdo->prepare("SELECT e.*, d.name as department_name 
                          FROM employees e 
                          LEFT JOIN departments d ON e.department_id = d.department_id 
                          WHERE e.employee_id = ?");
    $stmt->execute([$employee_id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employee) {
        die("الموظف غير موجود في النظام");
    }

    // Get previous positions
    $stmt_prev = $pdo->prepare("SELECT p.*, d.name as department_name 
                               FROM employee_previous_positions p
                               LEFT JOIN departments d ON p.department_id = d.department_id
                               WHERE p.employee_id = ?
                               ORDER BY p.end_date DESC");
    $stmt_prev->execute([$employee_id]);
    $previous_positions = $stmt_prev->fetchAll(PDO::FETCH_ASSOC);

    // Get high level positions history
    $stmt_high_level = $pdo->prepare("SELECT h.*, p.name as position_name
                                     FROM employee_high_level_history h
                                     JOIN high_level_positions p ON h.position_id = p.position_id
                                     WHERE h.employee_id = ?
                                     ORDER BY h.start_date DESC");
    $stmt_high_level->execute([$employee_id]);
    $high_level_positions = $stmt_high_level->fetchAll(PDO::FETCH_ASSOC);

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
    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="CSS/icons.css">
    <style>
        .position-entry {
            margin-bottom: 8px;
            padding: 8px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }
        .position-dates {
            color: #6c757d;
            font-size: 0.9em;
            margin-right: 10px;
        }
        .current-position {
            background-color: #e8f5e9;
            border-left: 4px solid #4CAF50;
        }
        .high-level-position {
            background-color: #e3f2fd;
            border-left: 4px solid #2196F3;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include 'header.php'; ?>

        <main class="dashboard-main">
            <h1 class="dashboard-title">الملف الشخصي للموظف</h1>
            
            <div class="employee-profile">
                <div class="profile-section">
                    <div class="profile-actions">
                        <a href="edit_employee.php?id=<?= $employee['employee_id'] ?>" class="action-button edit-button">
                            <i class="fas fa-edit"></i> تعديل المعلومات
                        </a>
                        <a href="promote_employee.php?id=<?= $employee['employee_id'] ?>" class="action-button edit-button">
                            <i class="fas fa-arrow-up"></i> ترقية الموظف
                        </a>
                        <a href="delete_employee.php?id=<?= $employee['employee_id'] ?>" class="action-button delete-button">
                            <i class="fas fa-trash"></i> حذف الموظف
                        </a>
                        <a href="document.php?id=<?= $employee['employee_id'] ?>" class="action-button documents-button">
                            <i class="fas fa-file-alt"></i> عرض المستندات
                        </a>
                        <a href="extract.php?id=<?= $employee['employee_id'] ?>" class="action-button documents-button">
                            <i class="fas fa-file-export"></i> استخراج ملفات
                        </a>
                    </div>
                    <h2><i class="fas fa-user"></i> المعلومات الشخصية</h2>
                    <div class="en">
                        <div class="info-row">
                            <span class="label">Nom :</span>
                            <span class="value"><?= htmlspecialchars($employee['firstname_en'] ?? '') ?></span>
                        </div>
                        <div class="info-row">
                            <span class="label">Prenom :</span>
                            <span class="value"><?= htmlspecialchars($employee['lastname_en'] ?? '') ?></span>
                        </div>
                    </div>
                    <div class="info-row">
                        <span class="label">اللقب :</span>
                        <span class="value"><?= htmlspecialchars($employee['firstname_ar'] ?? '') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">الاسم :</span>
                        <span class="value"><?= htmlspecialchars($employee['lastname_ar'] ?? '') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">تاريخ الميلاد:</span>
                        <span class="value"><?= $employee['birth_date'] ? date('Y/m/d', strtotime($employee['birth_date'])) : 'غير محدد' ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">مكان الميلاد:</span>
                        <span class="value"><?= htmlspecialchars($employee['birth_place'] ?? 'غير محدد') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">النوع:</span>
                        <span class="value"><?= $employee['gender'] == 'male' ? 'ذكر' : 'أنثى' ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">الرقم الوطني:</span>
                        <span class="value"><?= htmlspecialchars($employee['national_id'] ?? '') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">فصيلة الدم:</span>
                        <span class="value"><?= htmlspecialchars($employee['bloodtype'] ?? 'غير محدد') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">الأيام المتبقية للإجازة:</span>
                        <span class="value"><?= htmlspecialchars($employee['vacances_remain_days'] ?? 0) ?> يوم</span>
                    </div>
                </div>

                <div class="profile-section">
                    <h2><i class="fas fa-address-card"></i> المعلومات الوظيفية</h2>
                    <div class="info-row">
                        <span class="label">رقم الضمان الاجتماعي:</span>
                        <span class="value"><?= htmlspecialchars($employee['ssn'] ?? 'غير محدد') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">تاريخ التنصيب:</span>
                        <span class="value"><?= $employee['first_hire_date'] ? date('Y/m/d', strtotime($employee['first_hire_date'])) : 'غير محدد' ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">تاريخ التعيين:</span>
                        <span class="value"><?= $employee['hire_date'] ? date('Y/m/d', strtotime($employee['hire_date'])) : 'غير محدد' ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">منصب الشغل:</span>
                        <span class="value"><?= htmlspecialchars($employee['position'] ?? 'غير محدد') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">القسم:</span>
                        <span class="value"><?= htmlspecialchars($employee['department_name'] ?? 'غير محدد') ?></span>
                    </div>
                    
                    <?php if (!empty($high_level_positions)): ?>
                        <div class="info-row">
                            <span class="label">المناصب العليا:</span>
                            <div class="previous-positions">
                                <?php foreach ($high_level_positions as $position): ?>
                                    <div class="position-entry high-level-position <?= empty($position['end_date']) ? 'current-position' : '' ?>">
                                        <span><?= htmlspecialchars($position['position_name']) ?></span>
                                        <span class="position-dates">
                                            (<?= date('Y/m/d', strtotime($position['start_date'])) ?>
                                            <?= empty($position['end_date']) ? ' - حتى الآن' : ' - ' . date('Y/m/d', strtotime($position['end_date'])) ?>)
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($previous_positions)): ?>
                        <div class="info-row">
                            <span class="label">المناصب السابقة:</span>
                            <div class="previous-positions">
                                <?php foreach ($previous_positions as $position): ?>
                                    <div class="position-entry">
                                        <span><?= htmlspecialchars($position['position']) ?></span>
                                        <span class="position-dates">
                                            (<?= date('Y/m/d', strtotime($position['start_date'])) ?> - 
                                            <?= date('Y/m/d', strtotime($position['end_date'])) ?>)
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="profile-section">
                    <h2><i class="fas fa-phone"></i> معلومات الاتصال</h2>
                    <div class="info-row">
                        <span class="label">رقم الهاتف:</span>
                        <span class="value"><?= htmlspecialchars($employee['phone'] ?? 'غير متوفر') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">البريد الإلكتروني:</span>
                        <span class="value"><?= htmlspecialchars($employee['email'] ?? 'غير متوفر') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">العنوان:</span>
                        <span class="value"><?= htmlspecialchars($employee['address'] ?? 'غير متوفر') ?></span>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>