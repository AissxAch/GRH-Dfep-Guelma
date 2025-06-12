<?php
session_start();
require_once 'config/dbconnect.php';
$pdo = getDBConnection();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Initialize variables
$errors = [];
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Assign new high-level position
    if (isset($_POST['assign_position'])) {
        $employee_id = $_POST['employee_id'] ?? '';
        $position_id = $_POST['position_id'] ?? '';
        $start_date = $_POST['start_date'] ?? '';
        
        if (empty($employee_id)) $errors[] = 'يجب اختيار موظف';
        if (empty($position_id)) $errors[] = 'يجب اختيار منصب عالي';
        if (empty($start_date)) $errors[] = 'تاريخ البدء مطلوب';
        
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO employee_high_level_history 
                                      (employee_id, position_id, start_date) 
                                      VALUES (?, ?, ?)");
                $stmt->execute([$employee_id, $position_id, $start_date]);
                $success = 'تم تعيين المنصب العالي بنجاح';
            } catch (PDOException $e) {
                $errors[] = 'خطأ في قاعدة البيانات: ' . $e->getMessage();
            }
        }
    }
    
    // End a high-level position assignment
    if (isset($_POST['end_position'])) {
        $history_id = $_POST['history_id'] ?? '';
        $end_date = $_POST['end_date'] ?? '';
        
        if (empty($history_id)) $errors[] = 'معرف التسجيل غير صالح';
        if (empty($end_date)) $errors[] = 'تاريخ الانتهاء مطلوب';
        
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("UPDATE employee_high_level_history 
                                      SET end_date = ? 
                                      WHERE history_id = ?");
                $stmt->execute([$end_date, $history_id]);
                $success = 'تم إنهاء المنصب العالي بنجاح';
            } catch (PDOException $e) {
                $errors[] = 'خطأ في قاعدة البيانات: ' . $e->getMessage();
            }
        }
    }
}

// Fetch all high-level positions
$high_positions = [];
try {
    $stmt = $pdo->query("SELECT * FROM high_level_positions ORDER BY name ASC");
    $high_positions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "خطأ في جلب المناصب العليا: " . $e->getMessage();
}

// Fetch all employees
$employees = [];
try {
    $stmt = $pdo->query("SELECT employee_id, firstname_ar, lastname_ar FROM employees ORDER BY firstname_ar ASC");
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "خطأ في جلب الموظفين: " . $e->getMessage();
}

// Fetch current high-level assignments
$current_assignments = [];
try {
    $stmt = $pdo->query("SELECT h.history_id, e.employee_id, 
                         CONCAT(e.firstname_ar, ' ', e.lastname_ar) AS employee_name,
                         p.name AS position_name, h.start_date, h.end_date
                         FROM employee_high_level_history h
                         JOIN employees e ON h.employee_id = e.employee_id
                         JOIN high_level_positions p ON h.position_id = p.position_id
                         ORDER BY h.start_date DESC");
    $current_assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "خطأ في جلب التعيينات الحالية: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المناصب العليا - GRH Depf</title>
    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="CSS/icons.css">
    <style>
        .dashboard-container {
            background: #f5f6fa;
            min-height: 100vh;
        }
        
        .dashboard-main {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .section-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .section-title {
            color: #2c3e50;
            margin-bottom: 1.5rem;
            padding-bottom: 0.8rem;
            border-bottom: 2px solid #3498db;
            display: flex;
            align-items: center;
            gap: 0.7rem;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .assignments-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1.5rem;
        }
        
        .assignments-table th,
        .assignments-table td {
            padding: 1rem;
            text-align: right;
            border-bottom: 1px solid #eee;
        }
        
        .assignments-table th {
            background-color: #f8f9fa;
            color: #2c3e50;
            font-weight: 600;
        }
        
        .assignments-table tr:hover {
            background-color: #f8f9fa;
        }
        
        .active-assignment {
            background-color: #e8f5e9;
        }
        
        .ended-assignment {
            background-color: #ffebee;
        }
        
        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .active-badge {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        
        .ended-badge {
            background-color: #ffebee;
            color: #c62828;
        }
        
        .action-form {
            display: flex;
            gap: 0.5rem;
        }
        
        .date-input {
            max-width: 150px;
        }
        
        .empty-state {
            text-align: center;
            padding: 2rem;
            color: #7f8c8d;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #bdc3c7;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include 'header.php'; ?>

        <main class="dashboard-main">
            <h1 class="dashboard-title">إدارة المناصب العليا</h1>
            
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

            <div class="section-card">
                <h2 class="section-title"><i class="fas fa-plus-circle"></i> تعيين منصب عالي جديد</h2>
                
                <form method="POST">
                    <div class="form-grid">
                        <div class="input-group">
                            <label>الموظف <span class="required">*</span></label>
                            <select name="employee_id" required>
                                <option value="">اختر موظف...</option>
                                <?php foreach ($employees as $employee): ?>
                                    <option value="<?= $employee['employee_id'] ?>">
                                        <?= htmlspecialchars($employee['firstname_ar'] . ' ' . $employee['lastname_ar']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="input-group">
                            <label>المنصب العالي <span class="required">*</span></label>
                            <select name="position_id" required>
                                <option value="">اختر منصب عالي...</option>
                                <?php foreach ($high_positions as $position): ?>
                                    <option value="<?= $position['position_id'] ?>">
                                        <?= htmlspecialchars($position['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="input-group">
                            <label>تاريخ البدء <span class="required">*</span></label>
                            <input type="date" name="start_date" required>
                        </div>
                    </div>
                    
                    <div class="form-actions" style="margin-top: 1.5rem;">
                        <button type="submit" name="assign_position" class="login-button">
                            <i class="fas fa-save"></i> تعيين المنصب
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="section-card">
                <h2 class="section-title"><i class="fas fa-list"></i> التعيينات الحالية والسابقة</h2>
                
                <?php if (empty($current_assignments)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>لا توجد تعيينات مسجلة</h3>
                        <p>لم يتم تعيين أي موظف في منصب عالي بعد</p>
                    </div>
                <?php else: ?>
                    <table class="assignments-table">
                        <thead>
                            <tr>
                                <th>الموظف</th>
                                <th>المنصب العالي</th>
                                <th>تاريخ البدء</th>
                                <th>تاريخ الانتهاء</th>
                                <th>الحالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($current_assignments as $assignment): ?>
                                <tr class="<?= $assignment['end_date'] ? 'ended-assignment' : 'active-assignment' ?>">
                                    <td><?= htmlspecialchars($assignment['employee_name']) ?></td>
                                    <td><?= htmlspecialchars($assignment['position_name']) ?></td>
                                    <td><?= htmlspecialchars($assignment['start_date']) ?></td>
                                    <td>
                                        <?= $assignment['end_date'] ? htmlspecialchars($assignment['end_date']) : 'لا يزال مستمراً' ?>
                                    </td>
                                    <td>
                                        <span class="status-badge <?= $assignment['end_date'] ? 'ended-badge' : 'active-badge' ?>">
                                            <?= $assignment['end_date'] ? 'منتهي' : 'نشط' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!$assignment['end_date']): ?>
                                            <form method="POST" class="action-form">
                                                <input type="hidden" name="history_id" value="<?= $assignment['history_id'] ?>">
                                                <input type="date" name="end_date" class="date-input" required>
                                                <button type="submit" name="end_position" class="delete-button">
                                                    <i class="fas fa-times"></i> إنهاء
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span>--</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        // Set today's date as default for end date inputs
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            const endDateInputs = document.querySelectorAll('input[name="end_date"]');
            
            endDateInputs.forEach(input => {
                input.value = today;
                input.min = input.value; // Can't set end date before today
            });
            
            // Set minimum date for start date to today
            const startDateInput = document.querySelector('input[name="start_date"]');
            if (startDateInput) {
                startDateInput.min = today;
                startDateInput.value = today;
            }
        });
    </script>
</body>
</html>