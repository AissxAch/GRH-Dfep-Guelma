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
$docDate = date('Y-m-d'); // Changed to Y-m-d format for date input
$docNum = '';
$startDate = '';
$endDate = '';
$leaveDays = '';
$leaveYear = date('Y');
$suggest = '';
$decisionmaker= '';

// Get employee ID from URL
$employee_id = isset($_GET['id']) ? $_GET['id'] : '';

try {
    // Check if input is numeric (employee ID) or string (national ID)
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

// Function to convert date from Y-m-d to d/m/Y format for display
function formatDateForDisplay($date) {
    if (empty($date)) return '';
    $dateObj = DateTime::createFromFormat('Y-m-d', $date);
    return $dateObj ? $dateObj->format('d/m/Y') : $date;
}

// Function to convert date from d/m/Y to Y-m-d format for database
function formatDateForDatabase($date) {
    if (empty($date)) return '';
    $dateObj = DateTime::createFromFormat('d/m/Y', $date);
    return $dateObj ? $dateObj->format('Y-m-d') : $date;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $docNum = $_POST['docNum'] ?? '';
    $docDate = $_POST['docDate'] ?? date('Y-m-d');
    $startDate = $_POST['startDate'] ?? '';
    $endDate = $_POST['endDate'] ?? '';
    $leaveDays = $_POST['leaveDays'] ?? '';
    $remainingDays = $_POST['remainingDays'] ?? $employee['vacances_remain_days'];
    $leaveYear = $_POST['leaveYear'] ?? date('Y');
    $suggest = $_POST['suggest'] ?? '';
    $decisionmaker = $_POST['decisionmaker'] ?? '';
    $lawTexts = $_POST['law_texts'] ?? [];
    $lawText = implode("\n", $lawTexts);
    
    // Validate inputs
    if (empty($docNum) || empty($startDate) || empty($endDate) || empty($leaveDays) || empty($lawTexts)) {
        $error = "يرجى ملء جميع الحقول المطلوبة.";
    }
}
$fullname = $employee['firstname_ar'].' '.$employee['lastname_ar'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مقرر عطلة سنوية - GRH Depf</title>
    <link rel="stylesheet" href="CSS/deduction_decision.css">
    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="CSS/icons.css">
    <link rel="stylesheet" href="CSS/laws_selector.css">
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
                <h1 class="dashboard-title">إنشاء مقرر عطلة سنوية</h1>
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
                    
                    <!-- Leave Details Section -->
                    <div class="form-section">
                        <h3><i class="fas fa-calendar-alt"></i> تفاصيل العطلة</h3>
                        
                        <div class="form-group">
                            <label for="leaveYear">سنة العطلة:</label>
                            <input type="number" id="leaveYear" name="leaveYear" min="2020" max="2050"
                                value="<?= htmlspecialchars($leaveYear) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="startDate">تاريخ بداية العطلة:</label>
                            <input type="date" id="startDate" name="startDate" 
                                value="<?= htmlspecialchars($startDate) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="endDate">تاريخ نهاية العطلة:</label>
                            <input type="date" id="endDate" name="endDate" 
                                value="<?= htmlspecialchars($endDate) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="leaveDays">عدد أيام العطلة:</label>
                            <input type="number" id="leaveDays" name="leaveDays" min="1" max="365"
                                value="<?= htmlspecialchars($leaveDays) ?>" required placeholder="ادخل عدد أيام العطلة">
                        </div>
                        
                        <div class="form-group">
                            <label for="remainingDays">الأيام المتبقية:</label>
                            <input type="number" id="remainingDays" name="remainingDays" min="0"
                                value="<?= htmlspecialchars($employee['vacances_remain_days']) ?>" required placeholder="00">
                        </div>
                        
                        <div class="form-group">
                            <label for="law">القانـون المنطبق:</label>
                            <div class="law-select-container" data-category="annual_leave">
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
                            <input type="date" id="docDate" name="docDate" 
                                value="<?= htmlspecialchars($docDate) ?>" required>
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
    
    <!-- Annual Leave Decision (only shows when printing) -->
    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error): ?>
    <div class="annual-leave-decision">
        <div class="header">الجمهوريـــــة الجزائريـــــة الديمقراطيـــــة الشعبيـــــة</div>
        <div class="header">وزارة التكوين والتعليم المهنيين</div>
        <br>
        <div class="header2">مديرية التكوين والتعليم المهنيين لولاية قالمة</div>
        <div class="docnum">رقم: 2025/<?= htmlspecialchars($docNum) ?></div>
        <h1>مقـرر عطلة سنوية</h1>
        <div class="content">
            <p>- إن مدير التكوين والتعليـم المهنيين،</p>
            <p>- بمقتضى الأمر رقم 06-03 المؤرخ في 15/07/2006، المتضمن القانون الأساسي العام للوظيفة العمومية، المتمم،</p>
            <p>- بمقتضى المرسوم التنفيذي رقم 90-99 المؤرخ في 27/03/1990، المتعلق بسلطة التعيين والتسيير الإداري، بالنسبة للموظفين وأعوان الإدارة المركزية والولايات والبلديات والمؤسسات العمومية ذات الطابع الإداري،</p>
            <p>- بمقتضى المرسوم التنفيذي رقم 14-98 المؤرخ في 04/03/2014، المحدد لقواعد تنظيم مديريات التكوين والتعليم المهنيين في الولاية وسيرها،</p>
            <p><?= nl2br(htmlspecialchars($lawText)) ?></p>
            <p>- وبناء على رزنامة العطل السنوية لسنة <?= htmlspecialchars($leaveYear) ?>،</p>
            <p>- وباقتراح مـن <?= htmlspecialchars($suggest) ?>،</p>
            <h1 class="decision-title">يقـــــــرر</h1>
            <p>- <strong>المـادة الأولى</strong>: تمنح <strong>للسيد(ة) <?= htmlspecialchars($fullname) ?></strong> <?= htmlspecialchars($employee['position']) ?> عطلة سنوية لمدة <strong><?= htmlspecialchars($leaveDays) ?> يوم</strong> بعنوان سنة <strong><?= htmlspecialchars($leaveYear) ?></strong>، ابتداء من <strong><?= formatDateForDisplay($startDate) ?></strong> إلى غاية <strong><?= formatDateForDisplay($endDate) ?></strong>.</p>
            <p>- يحتفظ المعني(ة) برصيد عطلة سنوية مدته <strong><?= htmlspecialchars($remainingDays) ?> يوم</strong> بعنوان سنة <strong><?= htmlspecialchars($leaveYear) ?></strong>.</p>
            <p>- <strong>المـــــــــادة 2</strong>: يكلف <?= htmlspecialchars($decisionmaker) ?> بتنفيـذ هـذا المقرر.</p>
            <div class="signature">
                <p>قالمة في: <strong><?= formatDateForDisplay($docDate) ?></strong></p>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <style>
     .annual-leave-decision {
            font-family: 'Amiri', serif;
            padding: 30px;
            width: 21cm;
            min-height: 29.7cm;
            margin: 20px auto;
            line-height: 30px;
            box-sizing: border-box;
            background: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        
        .annual-leave-decision .header {
            font-weight: bold;
            font-size: 22px;
        }
        
        .annual-leave-decision .content {
            font-size: 20px;
            text-align: right;
        }
        
        .annual-leave-decision .content p {
            margin: 5px;
        }
        
        .annual-leave-decision .signature {
            margin-top: 50px;
            text-align: left;
        }
        
        .annual-leave-decision h1 {
            font-size: 26px;
            font-weight: bold;
            margin: 50px 0px 50px 61px;
        }
        
        .annual-leave-decision .docnum {
            text-align: right;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 30px;
        }
        
        .annual-leave-decision .decision-title {
            margin: 30px 300px;
        }
        
        @media print {
            @page {
                size: A4;
                margin: 0;
            }
            body {
                background: none;
                margin: 0 !important;
                padding: 0 !important;
            }
            .annual-leave-decision {
                box-shadow: none;
                margin: 0 !important;
                width: auto !important;
                height: auto !important;
                page-break-after: always;
            }
            .no-print,
            .dashboard-container,
            .dashboard-header,
            header {
                display: none !important;
            }
            .annual-leave-decision {
                display: block !important;
            }
        }
    </style>
    <script src="JS/laws_selector.js"></script>
    <script>
        function toggleLawInput() {
            const select = document.getElementById('lawSelect');
            const customContainer = document.getElementById('lawCustomContainer');
            
            if (select.value === 'other') {
                customContainer.style.display = 'block';
            } else {
                customContainer.style.display = 'none';
            }
        }

        // Auto-calculate leave days when dates are selected
        function calculateLeaveDays() {
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            
            if (startDate && endDate) {
                const start = new Date(startDate);
                const end = new Date(endDate);
                
                if (end >= start) {
                    const timeDiff = end.getTime() - start.getTime();
                    const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1; // +1 to include both start and end dates
                    document.getElementById('leaveDays').value = daysDiff;
                }
            }
        }

        // Add event listeners for automatic calculation
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('startDate').addEventListener('change', calculateLeaveDays);
            document.getElementById('endDate').addEventListener('change', calculateLeaveDays);
        });
    </script>
</body>
</html>