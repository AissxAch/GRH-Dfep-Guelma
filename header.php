<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
?>
<!-- header.php -->
<header class="dashboard-header">
    <div class="header-content">
        <div class="header-brand">
            <div class="brand-logo">
                <i class="fas fa-user-group"></i>
            </div>
            <div class="brand-text">
                <h1>نظام إدارة الموارد البشرية</h1>
                <p>مديرية التكوين والتعليم المهنيين لولاية قالمة</p>
            </div>
        </div>
        
        <div class="header-actions">
            <div class="user-profile">
                <i class="fas fa-user-circle"></i>
                <div class="profile-info">
                    <span class="username"><?php echo htmlspecialchars($_SESSION['fullname']); ?></span>
                    <span class="role"><?php echo htmlspecialchars($_SESSION['role'] ?? 'مستخدم'); ?></span>
                </div>
                <div class="profile-dropdown">
                    <a href="profile.php" class="dropdown-item">
                        <i class="fas fa-user"></i>الملف الشخصي</a>
                    <a href="settings.php" class="dropdown-item">
                        <i class="fas fa-cog"></i>
                        الإعدادات
                    </a>
                </div>
            </div>
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span>تسجيل الخروج</span>
            </a>
        </div>
    </div>
    
    <nav class="header-nav">
        <a href="index.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''); ?>">
            <i class="fas fa-home"></i>
            الرئيسية
        </a>
        <a href="checkAttendance.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'checkAttendance.php' ? 'active' : ''); ?>">
            <i class="fas fa-calendar-check"></i>
            الحضور
        </a>
        <a href="list_departments.php" class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'list_departments.php' ? 'active' : '') ?>">
            <i class="fas fa-building"></i>
            الأقسام
        </a>
        <a href="manage_laws.php" class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'manage_laws.php' ? 'active' : '') ?>">
            <i class="fas fa-gavel"></i>
            إدارة القوانين
        </a>
        <a href="backup.php" class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'backup.php' ? 'active' : '') ?>">
            <i class="fas fa-database"></i>
            النسخ الاحتياطي
        </a>
    </nav>
</header>