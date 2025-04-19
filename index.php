<?php
session_start();
require 'config/dbconnect.php';
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
    
    // Recent activity (including modifications and deletions)
    $recentActivity = $pdo->query("
        SELECT 
            a.*, 
            e.firstname_ar, 
            e.lastname_ar,
            CASE 
                WHEN a.activity_type = 'hire' THEN CONCAT('تم تعيين ', e.firstname_ar, ' ', e.lastname_ar)
                WHEN a.activity_type = 'promotion' THEN CONCAT('تم ترقية ', e.firstname_ar, ' ', e.lastname_ar, ' إلى ', a.details)
                WHEN a.activity_type = 'modification' THEN CONCAT('تم تعديل بيانات ', e.firstname_ar, ' ', e.lastname_ar)
                WHEN a.activity_type = 'delete' THEN CONCAT('تم حذف الموظف: ', a.details)
                ELSE a.details
            END as message
        FROM activity_log a
        LEFT JOIN employees e ON a.employee_id = e.employee_id
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
    <style>
        /* Stats Widget Styles */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .stat-card h3 {
            margin-top: 0;
            color: #333;
            font-size: 1.1rem;
        }
        .stat-value {
            font-size: 2.5em;
            font-weight: bold;
            color: #2c3e50;
            margin: 10px 0;
        }
        .stat-label {
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        
        /* Recent Activity Styles */
        .recent-activity {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .recent-activity h2 {
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .activity-item {
            padding: 12px 0;
            border-bottom: 1px solid #f5f5f5;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }
        .activity-item:last-child {
            border-bottom: none;
        }
        .activity-icon {
            font-size: 1.2rem;
            min-width: 25px;
            margin-top: 2px;
        }
        .activity-content {
            flex: 1;
        }
        .activity-message {
            margin-bottom: 4px;
            font-weight: 500;
        }
        .activity-date {
            color: #7f8c8d;
            font-size: 0.85rem;
            direction: ltr;
            text-align: left;
        }
        
        /* Activity Type Colors */
        .activity-hire { color: #2ecc71; }
        .activity-promotion { color: #3498db; }
        .activity-modification { color: #f39c12; }
        .activity-delete { color: #e74c3c; }
        
        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .stats-container {
                grid-template-columns: 1fr 1fr;
            }
            .stat-value {
                font-size: 2rem;
            }
        }
        @media (max-width: 480px) {
            .stats-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
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
                            <i class="fas fa-<?= match($activity['activity_type']) {
                                'hire' => 'user-plus activity-hire',
                                'promotion' => 'arrow-up activity-promotion',
                                'modification' => 'edit activity-modification',
                                'delete' => 'user-times activity-delete',
                                default => 'circle'
                            } ?> activity-icon"></i>
                            <div class="activity-content">
                                <div class="activity-message"><?= $activity['message'] ?></div>
                                <div class="activity-date">
                                    <?= date('d/m/Y H:i', strtotime($activity['activity_date'])) ?>
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

                <a href="promote_employee.php" class="card btn-primary">
                    <i class="fas fa-arrow-up"></i>
                    <h3>ترقية الموظفين</h3>
                    <p>تغيير رتبة الموظف إلى رتبة جديدة</p>
                </a>

                <div class="card search-card">
                    <i class="fas fa-search"></i>
                    <br>
                    <form action="employee.php" method="GET">
                        <div class="search-container">
                            <input type="text" name="id" placeholder="ابحث عن موظف..." required>
                            <button type="submit" class="search-btn" aria-label="بحث"><i class="fas fa-magnifying-glass"></i></button>
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