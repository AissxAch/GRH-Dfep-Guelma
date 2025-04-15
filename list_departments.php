<?php
session_start();
require_once 'config/dbconnect.php';
$pdo = getDBConnection();

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get departments from database
$departments = [];
try {
    $stmt = $pdo->query("SELECT * FROM departments ORDER BY created_at DESC");
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "خطأ في جلب البيانات: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>قائمة الأقسام - GRH Depf</title>
    <link rel="stylesheet" href="CSS/icons.css">
    <style>
        .departments-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            box-shadow: 0 1px 10px rgba(0,0,0,0.1);
        }
        
        .departments-table th,
        .departments-table td {
            padding: 12px 15px;
            text-align: right;
            border-bottom: 1px solid #ddd;
        }
        
        .departments-table th {
            background-color: #3498db;
            color: white;
        }
        
        .departments-table tr:hover {
            background-color: #f5f5f5;
        }
        
        .no-departments {
            text-align: center;
            padding: 20px;
            color: #666;
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        .actions {
            display: flex;
            gap: 10px;
            justify-content: flex-start;
        }
        
        .btn {
            padding: 5px 10px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-edit {
            background: #f1c40f;
            color: #2c3e50;
        }
        
        .btn-delete {
            background: #e74c3c;
            color: white;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include 'header.php'; ?>
        
        <main class="dashboard-main">
            <h1 class="dashboard-title">قائمة الأقسام</h1>
            
            <?php if (isset($error)): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <div class="table-container">
                <?php if (count($departments) > 0): ?>
                    <table class="departments-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>الاسم العربي</th>
                                <th>تاريخ الإضافة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($departments as $index => $dept): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= htmlspecialchars($dept['name']) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($dept['created_at'])) ?></td>
                                <td class="actions">
                                    <a href="edit_department.php?id=<?= $dept['department_id'] ?>" class="btn btn-edit">
                                        <i class="fas fa-edit"></i>
                                        تعديل
                                    </a>
                                    <a href="delete_department.php?id=<?= $dept['department_id'] ?>" class="btn btn-delete" 
                                       onclick="return confirm('هل أنت متأكد من رغبتك في حذف هذا القسم؟')">
                                        <i class="fas fa-trash"></i>
                                        حذف
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-departments">
                        <i class="fas fa-info-circle"></i>
                        لا توجد أقسام مسجلة في النظام
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>