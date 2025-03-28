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
    <title>إستخراج الوثائق- GRH Depf</title>
    <link rel="stylesheet" href="CSS/extract.css">
    <link rel="stylesheet" href="CSS/icons.css">
</head>

<body>
    <div class="dashboard-container">
    <?php include 'header.php'; ?>

        <div class="documnts-container">
            <h1 class="dashboard-title">إستخراج الوثائق</h1>
            <div class="documnts-grid">
                <div class="documnt-card">
                    <i class="fas fa-file-alt"></i>
                    <h3>إستخراج شهادة عمل</h3>
                    <p>إستخراج شهادة عمل لموظف معين</p>
                    <a href="work_certificate.php" class="btn-primary">إستخراج</a>
                </div>
                <div class="documnt-card">
                    <i class="fas fa-file-alt"></i>
                    <h3>إستخراج شهادة خبرة</h3>
                    <p>إستخراج شهادة خبرة لموظف معين</p>
                    <a href="experience_certificate.php" class="btn-primary">إستخراج</a>
                </div>
                <div class="documnt-card">
                    <i class="fas fa-file-alt"></i>
                    <h3>إستخراج شهادة توظيف</h3>
                    <p>إستخراج شهادة توظيف لموظف معين</p>
                    <a href="employment_certificate.php" class="btn-primary">إستخراج</a>
                </div>
                <div class="documnt-card">
                    <i class="fas fa-file-alt"></i>
                    <h3>إستخراج شهادة تأمين</h3>
                    <p>إستخراج شهادة تأمين لموظف معين</p>
                    <a href="insurance_certificate.php" class="btn-primary">إستخراج</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>