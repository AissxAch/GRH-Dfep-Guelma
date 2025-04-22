<?php
session_start();
require 'config/dbconnect.php';
require 'config/functions.php'; // Include the functions file we created
$pdo = getDBConnection();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Fetch statistics
try {
    // Total employees
    $totalEmployees = $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
    
    // Employees with high-level positions
    $highLevelEmployees = $pdo->query("SELECT COUNT(DISTINCT employee_id) FROM employee_high_level_history WHERE end_date IS NULL")->fetchColumn();
    
    // Recent hires (last 30 days)
    $recentHires = $pdo->query("
        SELECT e.*, d.name as department_name 
        FROM employees e 
        LEFT JOIN departments d ON e.department_id = d.department_id 
        WHERE e.hire_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
        ORDER BY e.hire_date DESC 
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Recent promotions (changes in high-level positions)
    $recentPromotions = $pdo->query("
        SELECT e.employee_id, e.firstname_ar, e.lastname_ar, h.position_id, p.name as position_name, h.start_date 
        FROM employee_high_level_history h
        JOIN employees e ON h.employee_id = e.employee_id
        JOIN high_level_positions p ON h.position_id = p.position_id
        ORDER BY h.start_date DESC 
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Recent activity (including all modifications)
    $recentActivity = $pdo->query("
        SELECT 
            a.*, 
            e.firstname_ar, 
            e.lastname_ar,
            u.FullName as changed_by_name,
            CASE 
                WHEN a.activity_type = 'hire' THEN CONCAT(a.details)
                WHEN a.activity_type = 'promotion' THEN CONCAT('تم ترقية ', e.firstname_ar, ' ', e.lastname_ar, ' من ', a.old_value, ' إلى ', a.new_value)
                WHEN a.activity_type = 'modification' THEN 
                    CASE
                        WHEN a.changed_field = 'department_id' THEN 
                            CONCAT('تم نقل ', e.firstname_ar, ' ', e.lastname_ar, ' إلى قسم ', 
                                (SELECT name FROM departments WHERE department_id = a.new_value))
                        WHEN a.changed_field = 'position' THEN 
                            CONCAT('تم تغيير منصب ', e.firstname_ar, ' ', e.lastname_ar, ' إلى ', a.new_value)
                        WHEN a.changed_field = 'is_active' THEN
                            CONCAT('تم تغيير حالة ', e.firstname_ar, ' ', e.lastname_ar, ' إلى ', 
                                CASE WHEN a.new_value = 1 THEN 'نشط' ELSE 'غير نشط' END)
                        WHEN a.changed_field IS NOT NULL THEN 
                            CONCAT('تم تعديل ', 
                                CASE 
                                    WHEN a.changed_field = 'firstname_ar' THEN 'الاسم الأول'
                                    WHEN a.changed_field = 'lastname_ar' THEN 'الاسم الأخير'
                                    WHEN a.changed_field = 'birth_date' THEN 'تاريخ الميلاد'
                                    WHEN a.changed_field = 'national_id' THEN 'الرقم الوطني'
                                    WHEN a.changed_field = 'phone' THEN 'رقم الهاتف'
                                    WHEN a.changed_field = 'email' THEN 'البريد الإلكتروني'
                                    WHEN a.changed_field = 'address' THEN 'العنوان'
                                    WHEN a.changed_field = 'vacances_remain_days' THEN 'أيام الإجازة المتبقية'
                                    ELSE a.changed_field
                                END, 
                                ' لـ ', e.firstname_ar, ' ', e.lastname_ar)
                        ELSE CONCAT('تم تعديل بيانات ', e.firstname_ar, ' ', e.lastname_ar)
                    END
                WHEN a.activity_type = 'delete' THEN CONCAT(a.details)
                ELSE a.details
            END as message,
            CASE 
                WHEN a.activity_type = 'hire' THEN 'user-plus'
                WHEN a.activity_type = 'promotion' THEN 'arrow-up'
                WHEN a.activity_type = 'modification' THEN 'edit'
                WHEN a.activity_type = 'delete' THEN 'user-times'
                ELSE 'circle'
            END as icon_class,
            CASE 
                WHEN a.activity_type = 'hire' THEN 'activity-hire'
                WHEN a.activity_type = 'promotion' THEN 'activity-promotion'
                WHEN a.activity_type = 'modification' THEN 'activity-modification'
                WHEN a.activity_type = 'delete' THEN 'activity-delete'
                ELSE ''
            END as activity_class
        FROM activity_log a
        LEFT JOIN employees e ON a.employee_id = e.employee_id
        LEFT JOIN users u ON a.changed_by = u.id
        ORDER BY a.activity_date DESC 
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error fetching dashboard data: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - GRH Depf</title>
    <link rel="stylesheet" href="CSS/index.css">
    <link rel="stylesheet" href="CSS/icons.css">
    <link rel="stylesheet" href="CSS/style.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include 'header.php'; ?>

        <main class="dashboard-main">
            <h1 class="dashboard-title">نظام إدارة الموارد البشرية</h1>
            
            <!-- Stats Widgets -->
            <div class="stats-container">
                <div class="stat-card">
                    <h3>إجمالي الموظفين</h3>
                    <div class="stat-value"><?= $totalEmployees ?></div>
                    <div class="stat-label">موظف</div>
                </div>
                
                <div class="stat-card">
                    <h3>موظفين بمناصب عليا</h3>
                    <div class="stat-value"><?= $highLevelEmployees ?></div>
                    <div class="stat-label">موظف</div>
                </div>
                
                <div class="stat-card">
                    <h3>متعاقدين جدد</h3>
                    <div class="stat-value"><?= count($recentHires) ?></div>
                    <div class="stat-label">في آخر 30 يوم</div>
                </div>
                
                <div class="stat-card">
                    <h3>ترقيات حديثة</h3>
                    <div class="stat-value"><?= count($recentPromotions) ?></div>
                    <div class="stat-label">في آخر 30 يوم</div>
                </div>
            </div>
            
            <!-- Recent Activity Section -->
            <div class="recent-activity">
                <h2><i class="fas fa-clock"></i> النشاط الحديث</h2>
                <?php if (!empty($recentActivity)): ?>
                    <?php foreach ($recentActivity as $activity): ?>
                        <div class="activity-item">
                            <i class="fas fa-<?= $activity['icon_class'] ?> <?= $activity['activity_class'] ?> activity-icon"></i>
                            <div class="activity-content">
                                <div class="activity-message"><?= $activity['message'] ?></div>
                                <div class="activity-date">
                                    <?= date('d/m/Y H:i', strtotime($activity['activity_date'])) ?>
                                    <?php if (!empty($activity['changed_by_name'])): ?>
                                        <span class="activity-changed-by">بواسطة <?= htmlspecialchars($activity['changed_by_name']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>لا يوجد نشاط حديث</p>
                <?php endif; ?>
            </div>

            <div class="grid-container">
                <!-- الموظفين Section -->
                <div class="section-title">إدارة الموظفين</div>
                
                <a href="add_employee.php" class="card btn-primary">
                    <i class="fas fa-user-plus"></i>
                    <h3>إضافة موظف</h3>
                    <p>إضافة سجل موظف جديد إلى النظام</p>
                </a>

                <a href="list_employees.php" class="card btn-info">
                    <i class="fas fa-users"></i>
                    <h3>قائمة الموظفين</h3>
                    <p>عرض قائمة بجميع الموظفين</p>
                </a>

                <a href="checkAttendance.php" class="card btn-primary">
                    <i class="fas fa-user-check"></i>
                    <h3>فحص سجل الحضور</h3>
                    <p>فحص المتاخرين و الغأبين</p>
                </a>

                <div class="card search-card">
                    <i class="fas fa-search"></i>
                    <br>
                    <form action="employee.php" method="GET">
                        <div class="search-container">
                            <input type="text" name="id" placeholder="ابحث عن موظف..." required>
                        </div>
                    </form>
                    <p>ابحث بالرقم الوظيفي أو الرقم الوطني</p>
                </div>
                
                <!-- المصالح Section -->
                <div class="section-title">إدارة المصالح</div>
                
                <a href="add_department.php" class="card btn-primary">
                    <i class="fas fa-plus"></i>
                    <h3>إضافة قسم</h3>
                    <p>إضافة قسم جديد إلى النظام</p>
                </a>

                <a href="list_departments.php" class="card btn-info">
                    <i class="fas fa-list"></i>
                    <h3>قائمة الأقسام</h3>
                    <p>عرض قائمة بجميع الأقسام</p>
                </a>
                
                <!-- الرتب Section -->
                <div class="section-title">إدارة الرتب</div>
                
                <a href="add_position.php" class="card btn-primary">
                    <i class="fas fa-plus"></i>
                    <h3>إضافة رتبة</h3>
                    <p>إضافة رتبة جديدة إلى النظام</p>
                </a>

                <a href="list_positions.php" class="card btn-info">
                    <i class="fas fa-list"></i>
                    <h3>قائمة الرتب</h3>
                    <p>عرض قائمة بجميع الرتب</p>
                </a>
                
                <a href="list_high_positions.php" class="card btn-info">
                    <i class="fas fa-list"></i>
                    <h3>قائمة المناصب العليا</h3>
                    <p>عرض قائمة بجميع المناصب العليا</p>
                </a>
            </div>
        </main>

        <footer class="dashboard-footer">
            <p>جميع الحقوق محفوظة &copy; م.ت.ت.م قالمة <?php echo date('Y'); ?></p>
        </footer>
    </div>
</body>
</html>