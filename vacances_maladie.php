<?php
session_start();
require 'config/dbconnect.php';
$pdo = getDBConnection();

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Initialize variables
$error = null;
$employee = null;
$docDate = date('d/m/Y');
$docNum = '';
$medicalCertNum = '';
$medicalCertDate = '';
$medicalCertIssuer = '';
$startDate = '';
$leaveDays = '';
$suggest = '';
$decisionmaker = '';

// Get employee ID from URL
$employee_id = isset($_GET['id']) ? $_GET['id'] : '';

try {
    if (is_numeric($employee_id)) {
        $stmt = $pdo->prepare("SELECT * FROM employees WHERE employee_id = ?");
    } else {
        $stmt = $pdo->prepare("SELECT * FROM employees WHERE national_id = ?");
    }
    $stmt->execute([$employee_id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$employee) {
        $error = "الموظف غير موجود في النظام";
    }
} catch (PDOException $e) {
    $error = "خطأ في قاعدة البيانات: " . $e->getMessage();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $docNum = $_POST['docNum'] ?? '';
    $docDate = $_POST['docDate'] ?? date('d/m/Y');
    $medicalCertNum = $_POST['medicalCertNum'] ?? '';
    $medicalCertDate = $_POST['medicalCertDate'] ?? '';
    $medicalCertIssuer = $_POST['medicalCertIssuer'] ?? '';
    $startDate = $_POST['startDate'] ?? '';
    $leaveDays = $_POST['leaveDays'] ?? '';
    $suggest = $_POST['suggest'] ?? '';
    $decisionmaker = $_POST['decisionmaker'] ?? '';
    $lawTexts = $_POST['law_texts'] ?? [];
    $lawText = implode("\n<p>", $lawTexts);
    
    // Validate inputs
    if (empty($docNum) || empty($medicalCertNum) || empty($medicalCertDate) || 
        empty($medicalCertIssuer) || empty($startDate) || empty($leaveDays) || empty($lawTexts)) {
        $error = "يرجى ملء جميع الحقول المطلوبة.";
    }
}
$fullname = $employee['firstname_ar'] . ' ' . $employee['lastname_ar'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مقرر عطلة مرضية - GRH Depf</title>
    <link rel="stylesheet" href="CSS/deduction_decision.css">
    <link rel="stylesheet" href="CSS/laws_selector.css">
    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="CSS/icons.css">
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="dashboard-container no-print">
        <main class="dashboard-main">
            <?php if ($error): ?>
                <div class="error-container">
                    <div class="error-card">
                        <div class="error-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h2 class="error-title">حدث خطأ</h2>
                        <p class="error-message"><?= $error ?></p>
                        <div class="error-actions">
                            <a href="list_employees.php" class="error-button">
                                <i class="fas fa-arrow-right"></i>
                                العودة إلى قائمة الموظفين
                            </a>
                            <a href="javascript:history.back()" class="error-button secondary">
                                <i class="fas fa-undo"></i>
                                العودة للخلف
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <h1 class="dashboard-title">إنشاء مقرر عطلة مرضية</h1>
                <form method="post">
                    <!-- Employee Information Section -->
                    <div class="form-section">
                        <h3><i class="fas fa-user-tie"></i> معلومات الموظف</h3>
                        
                        <div class="form-group">
                            <label for="docNum">رقم الوثيقة:</label>
                            <input type="text" id="docNum" name="docNum" value="<?= htmlspecialchars($docNum) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>اسم الموظف:</label>
                            <input type="text" value="<?= htmlspecialchars($fullname) ?>" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label>المنصب:</label>
                            <input type="text" value="<?= htmlspecialchars($employee['position']) ?>" readonly>
                        </div>
                    </div>
                    
                    <!-- Medical Leave Details Section -->
                    <div class="form-section">
                        <h3><i class="fas fa-procedures"></i> تفاصيل العطلة المرضية</h3>
                        
                        <div class="form-group">
                            <label for="medicalCertNum">رقم الشهادة الطبية:</label>
                            <input type="text" id="medicalCertNum" name="medicalCertNum" 
                                value="<?= htmlspecialchars($medicalCertNum) ?>" required placeholder="ادخل رقم الشهادة الطبية">
                        </div>
                        
                        <div class="form-group">
                            <label for="medicalCertDate">تاريخ الشهادة الطبية:</label>
                            <input type="text" id="medicalCertDate" name="medicalCertDate" 
                                value="<?= htmlspecialchars($medicalCertDate) ?>" required placeholder="DD/MM/YYYY">
                        </div>
                        
                        <div class="form-group">
                            <label for="medicalCertIssuer">صادر عن:</label>
                            <input type="text" id="medicalCertIssuer" name="medicalCertIssuer" 
                                value="<?= htmlspecialchars($medicalCertIssuer) ?>" required placeholder="ادخل مصدر الشهادة الطبية">
                        </div>
                        
                        <div class="form-group">
                            <label for="startDate">تاريخ بداية العطلة:</label>
                            <input type="text" id="startDate" name="startDate" 
                                value="<?= htmlspecialchars($startDate) ?>" required placeholder="DD/MM/YYYY">
                        </div>
                        
                        <div class="form-group">
                            <label for="leaveDays">عدد أيام العطلة:</label>
                            <input type="text" id="leaveDays" name="leaveDays" 
                                value="<?= htmlspecialchars($leaveDays) ?>" required placeholder="ادخل عدد أيام العطلة">
                        </div>
                        
                        <div class="form-group">
                            <label for="laws">القانـون المنطبق:</label>
                            <div class="law-select-container" data-category="sick_leave">
                                <input type="text" class="law-input-field" placeholder="ابحث أو اختر من القائمة">
                                <div class="law-dropdown"></div>
                                <button type="button" class="add-law-btn">إضافة قانون مخصص</button>
                                <div class="selected-laws"></div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="suggest">المقترح من:</label>
                            <input type="text" id="suggest" name="suggest" 
                                value="<?= htmlspecialchars($suggest) ?>" required placeholder="ادخل منصب المقترح">
                        </div>

                        <div class="form-group">
                            <label for="decisionmaker">المكلف بالتنفيذ:</label>
                            <input type="text" id="decisionmaker" name="decisionmaker" 
                                value="<?= htmlspecialchars($decisionmaker) ?>" required placeholder="ادخل منصب المكلف بالتنفيذ">
                        </div>
                        
                        <div class="form-group">
                            <label for="docDate">تاريخ القرار:</label>
                            <input type="text" id="docDate" name="docDate" 
                                value="<?= htmlspecialchars($docDate) ?>" required placeholder="DD/MM/YYYY">
                        </div>
                    </div>
                    
                    <!-- Form Buttons -->
                    <div class="form-buttons">
                        <button type="submit">
                            <i class="fas fa-save"></i>
                            إنشاء القرار
                        </button>
                        <button type="button" onclick="window.print()" class="print-button">
                            <i class="fas fa-print"></i>
                            طباعة القرار
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </main>
    </div>
    
    <!-- Medical Leave Decision (only shows when printing) -->
    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error): ?>
    <div class="medical-leave-decision">
        <div class="header">الجمهوريـــــة الجزائريـــــة الديمقراطيـــــة الشعبيـــــة</div>
        <div class="header">وزارة التكوين والتعليم المهنيين</div>
        <br>
        <div class="header2">مديرية التكوين والتعليم المهنيين لولاية قالمة</div>
        <div class="docnum">رقم: 2025/<?= htmlspecialchars($docNum) ?></div>
        <h1>مقــــــرر</h1>
        <div class="content">
            <p>-إن مدير التكوين والتعليم المهنيين،</p>
            <p>-بمقتضى الأمر رقم 06-03 المؤرخ في 15/07/2006، المتضمن القانون الأساسي العام للوظيفة العمومية، المتمم،</p>
            <p>-وبمقتضى المرسوم التنفيذي رقم 90-99 المؤرخ في 27/03/1990 والمتعلق بسلطة التعيين، والتسيير الإداري، بالنسبة للموظفين وأعوان الإدارة المركزية والولايات والبلديات والمؤسسات العمومية ذات الطابع الإداري،</p>
            <p><?= nl2br(htmlspecialchars($lawText)) ?></p>
            <p>-بناء على الشهادة الطبية رقم <strong><?= htmlspecialchars($medicalCertNum) ?></strong> بتاريخ <strong><?= htmlspecialchars($medicalCertDate) ?></strong> الصادرة عن <strong><?= htmlspecialchars($medicalCertIssuer) ?></strong>، المتضمنة منح السيد(ة) <strong><?= htmlspecialchars($fullname) ?><strong> توقف عن العمل لمدة (<strong><?= htmlspecialchars($leaveDays) ?></strong>) يوم، ابتداء من <strong><?= htmlspecialchars($startDate) ?></strong>،</p>
            <p>-باقتراح من </strong><?= htmlspecialchars($suggest) ?></strong></p>
            <h1 class="decision-title">يقـــــــرر</h1>
            <p>-<strong>المـادة الأولى</strong>: تمنح عطلة مرضية لمدة <strong>(<?= htmlspecialchars($leaveDays) ?>) يوم،</strong> ابتداء من <?= htmlspecialchars($startDate) ?> <strong>للسيد(ة) <?= htmlspecialchars($fullname) ?></strong> <?= htmlspecialchars($employee['position']) ?>.</p>
            <p>-<strong>المــــــادة 02</strong>: يكلف كل من <?= htmlspecialchars($decisionmaker) ?>، بتنفيـذ هـذا المقـرر.</p>
            <div class="signature">
                <p>قالمة في: <strong><?= htmlspecialchars($docDate) ?></strong></p>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <script src="JS/laws_selector.js"></script>
</body>
</html>