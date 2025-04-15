<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
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
</head>
<body>
    <div class="dashboard-container">
    <?php include 'header.php'; ?>

        <main class="dashboard-main">
            <h1 class="dashboard-title">نظام إدارة الموارد البشرية</h1>
            
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
                </div>

                <!-- التقارير Section -->
                <!-- <div class="section-title">التقارير</div>
                
                <a href="reports.php" class="card btn-secondary">
                    <i class="fas fa-chart-bar"></i>
                    <h3>تقارير الموظفين</h3>
                    <p>عرض الإحصائيات والتحليلات</p>
                </a> -->
            </div>
        </main>

        <footer class="dashboard-footer">
            <p>جميع الحقوق محفوظة &copy; م.ت.ت.م قالمة <?php echo date('Y'); ?></p>
        </footer>
    </div>
</body>
</html>