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
    <link rel="stylesheet" href="CSS/login.css">
    <link rel="stylesheet" href="CSS/index.css">
</head>
<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <div class="user-info">
                <span class="welcome">مرحبا، <?php echo htmlspecialchars($_SESSION['fullname']); ?></span>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a>
            </div>
        </header>

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

                <a href="modify_employee.php" class="card btn-warning">
                    <i class="fas fa-user-edit"></i>
                    <h3>تعديل بيانات</h3>
                    <p>تحديث معلومات الموظفين</p>
                </a>

                <a href="remove_employee.php" class="card btn-danger">
                    <i class="fas fa-user-times"></i>
                    <h3>حذف موظف</h3>
                    <p>إزالة موظف من النظام</p>
                </a>

                <a href="checkAttendance.php" class="card btn-primary">
                    <i class="fas fa-user-check"></i>
                    <h3>فحص سجل الحضور</h3>
                    <p>فحص المتاخرين و الغأبين</p>
                </a>

                <div class="card search-card">
                    <form action="search.php" method="GET">
                        <div class="search-container">
                            <input type="text" name="query" placeholder="ابحث عن موظف..." required>
                            <button type="submit" class="search-btn"><i class="fas fa-search"></i></button>
                        </div>
                    </form>
                    <p>ابحث بالاسم أو الرقم الوظيفي</p>
                </div>

                <!-- المستندات Section -->
                <div class="section-title">إدارة المستندات</div>
                
                <a href="upload.php" class="card btn-info">
                    <i class="fas fa-upload"></i>
                    <h3>رفع المستندات</h3>
                    <p>تحميل الوثائق والملفات</p>
                </a>

                <a href="extract.php" class="card btn-success">
                    <i class="fas fa-file-download"></i>
                    <h3>استخراج مستندات</h3>
                    <p>تنزيل وثائق الموظفين</p>
                </a>

                <!-- التقارير Section -->
                <div class="section-title">التقارير</div>
                
                <a href="reports.php" class="card btn-secondary">
                    <i class="fas fa-chart-bar"></i>
                    <h3>تقارير الموظفين</h3>
                    <p>عرض الإحصائيات والتحليلات</p>
                </a>
            </div>
        </main>

        <footer class="dashboard-footer">
            <p>جميع الحقوق محفوظة &copy; م.ت.ت.م قالمة <?php echo date('Y'); ?></p>
        </footer>
    </div>
</body>
</html>