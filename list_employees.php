<?php
session_start();
require 'config/dbconnect.php';
$pdo = getDBConnection();

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Handle employee deletion
if (isset($_GET['delete'])) {
    try {
        $employee_id = intval($_GET['delete']);
        
        // Delete employee
        $stmt = $pdo->prepare("DELETE FROM employees WHERE employee_id = ?");
        $stmt->execute([$employee_id]);
        
        // Delete related documents
        $stmt_docs = $pdo->prepare("DELETE FROM employee_documents WHERE employee_id = ?");
        $stmt_docs->execute([$employee_id]);
        
        $success = "تم حذف الموظف بنجاح";
    } catch (PDOException $e) {
        $error = "خطأ في قاعدة البيانات: " . $e->getMessage();
    }
}

// Search functionality
$search = '';
if (isset($_GET['search'])) {
    $search = '%' . trim($_GET['search']) . '%';
}

// Fetch employees with search filter
try {
    $sql = "SELECT * FROM employees ORDER BY employee_id ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("خطأ في جلب البيانات: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>قائمة الموظفين - GRH Depf</title>
    <link rel="stylesheet" href="CSS/employee.css">
    <link rel="stylesheet" href="CSS/icons.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include 'header.php'; ?>

        <main class="dashboard-main">
            <h1 class="dashboard-title">قائمة الموظفين</h1>
            
            <?php if (isset($error)): ?>
                <div class="error-message"><?= $error ?></div>
            <?php endif; ?>
            
            <?php if (isset($success)): ?>
                <div class="success-message">
                    <i class="fas fa-check"></i>
                    <p><?= $success ?></p>
                </div>
            <?php endif; ?>

            <div class="employee-actions">
                <a href="add_employee.php" class="action-button edit-button">
                    <i class="fas fa-user-plus"></i> إضافة موظف جديد
                </a>
                <form action="employee.php" method="GET" class="search-form">
                    <div class="input-group">
                        <input type="text" name="id" placeholder="ابحث بالاسم أو الرقم الوطني" 
                               value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                        <button type="submit" class="search-button">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
                
            </div>

            <div class="employees-list">
                <?php if (empty($employees)): ?>
                    <div class="no-results">
                        <i class="fas fa-user-slash"></i>
                        <p>لا توجد نتائج</p>
                    </div>
                <?php else: ?>
                    <div class="responsive-table">
                        <table class="employees-table">
                            <thead>
                                <tr>
                                    <th>الرقم الوظيفي</th>
                                    <th>الاسم الكامل</th>
                                    <th>الوظيفة</th>
                                    <th>الرقم الوطني</th>
                                    <th>تاريخ التعيين</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($employees as $employee): ?>
                                    <tr>
                                        <td data-label="الرقم الوظيفي"><?= $employee['employee_id'] ?></td>
                                        <td data-label="الاسم"><?= htmlspecialchars($employee['full_name_ar']) ?></td>
                                        <td data-label="الوظيفة"><?= htmlspecialchars($employee['position']) ?></td>
                                        <td data-label="الرقم الوطني"><?= $employee['national_id'] ?></td>
                                        <td data-label="تاريخ التعيين">
                                            <?= date('d/m/Y', strtotime($employee['hire_date'])) ?>
                                        </td>
                                        <td data-label="الإجراءات" class="actions-cell">
                                            <a href="employee.php?id=<?= $employee['employee_id'] ?>" 
                                               class="view-btn" title="عرض الملف">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit_employee.php?id=<?= $employee['employee_id'] ?>" 
                                               class="edit-btn" title="تعديل">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="extract.php?id=<?= $employee['employee_id'] ?>" 
                                               class="documents-btn" title="إستخراج الملف">
                                                <i class="fas fa-file-export"></i>
                                            <a href="document.php?id=<?= $employee['employee_id'] ?>" 
                                               class="documents-btn" title="عرض المستندات">
                                                <i class="fas fa-file-alt"></i>
                                            </a>
                                            <a href="list_employees.php?delete=<?= $employee['employee_id'] ?>" 
                                               class="delete-btn" title="حذف"
                                               onclick="return confirm('هل أنت متأكد من حذف هذا الموظف؟');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>