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

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $docNum = $_POST['docNum'] ?? '';
    $docDate = $_POST['docDate'] ?? date('d/m/Y');
    $startDate = $_POST['startDate'] ?? '';
    $endDate = $_POST['endDate'] ?? '';
    $leaveDays = $_POST['leaveDays'] ?? '';
    $remainingDays = $_POST['remainingDays'] ?? $employee['vacances_remain_days'];
    $leaveYear = $_POST['leaveYear'] ?? date('Y');
    $suggest = $_POST['suggest'] ?? '';
    $decisionmaker = $_POST['decisionmaker'] ?? '';
    $law = $_POST['law'] ?? '';
    if (isset($_POST['lawCustom']) && !empty($_POST['lawCustom'])) {
        $law = $_POST['lawCustom'];
    }
    // Validate inputs
    if (empty($docNum) || empty($startDate) || empty($endDate) || empty($leaveDays)) {
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
                            <input type="text" id="leaveYear" name="leaveYear" 
                                value="<?= htmlspecialchars($leaveYear) ?>" required placeholder="YYYY">
                        </div>
                        
                        <div class="form-group">
                            <label for="startDate">تاريخ بداية العطلة:</label>
                            <input type="text" id="startDate" name="startDate" 
                                value="<?= htmlspecialchars($startDate) ?>" required placeholder="DD/MM/YYYY">
                        </div>
                        
                        <div class="form-group">
                            <label for="endDate">تاريخ نهاية العطلة:</label>
                            <input type="text" id="endDate" name="endDate" 
                                value="<?= htmlspecialchars($endDate) ?>" required placeholder="DD/MM/YYYY">
                        </div>
                        
                        <div class="form-group">
                            <label for="leaveDays">عدد أيام العطلة:</label>
                            <input type="text" id="leaveDays" name="leaveDays" 
                                value="<?= htmlspecialchars($leaveDays) ?>" required placeholder="ادخل عدد أيام العطلة">
                        </div>
                        
                        <div class="form-group">
                            <label for="remainingDays">الأيام المتبقية:</label>
                            <input type="text" id="remainingDays" name="remainingDays" 
                                value="<?= htmlspecialchars($employee['vacances_remain_days']) ?>" required placeholder="00">
                        </div>
                        
                        <div class="form-group">
                            <label for="law">القانـون المنطبق:</label>
                            <select name="law" id="lawSelect" class="form-select" onchange="toggleLawInput()">
                                <option value="">اختر القانون المنطبق</option>
                                <option value="- وبمقتضى المرسوم التنفيذي رقم 09-241 المؤرخ في 22/07/2009، المتضمن القانون الأساسي الخاص بالموظفين المنتمين للأسلاك التقنية الخاصة بالإدارة المكلفة بالسكن والعمران،">
                                    - وبمقتضى المرسوم التنفيذي رقم 09-241 المؤرخ في 22/07/2009، المتضمن القانون الأساسي الخاص بالموظفين المنتمين للأسلاك التقنية الخاصة بالإدارة المكلفة بالسكن والعمران،
                                </option>
                                <option value="other">أخرى</option>
                            </select>
                            <div id="lawCustomContainer" class="custom-law-container" style="display: none;">
                                <input type="text" id="lawCustomInput" name="lawCustom" class="form-input" placeholder="أدخل النص القانوني المطلوب">
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
            <p><?=htmlspecialchars($law) ?></p>
            <p>- وبناء على رزنامة العطل السنوية لسنة <?= htmlspecialchars($leaveYear) ?>،</p>
            <p>- وباقتراح مـن <?= htmlspecialchars($suggest) ?>،</p>
            <h1 class="decision-title">يقـــــــرر</h1>
            <p>- <strong>المـادة الأولى</strong>: تمنح <strong>للسيد(ة) <?= htmlspecialchars($fullname) ?></strong> <?= htmlspecialchars($employee['position']) ?> عطلة سنوية لمدة <strong><?= htmlspecialchars($leaveDays) ?> يوم</strong> بعنوان سنة <strong><?= htmlspecialchars($leaveYear) ?></strong>، ابتداء من <strong><?= htmlspecialchars($startDate) ?></strong> إلى غاية <strong><?= htmlspecialchars($endDate) ?></strong>.</p>
            <p>- يحتفظ المعني(ة) برصيد عطلة سنوية مدته <strong><?= htmlspecialchars($remainingDays) ?> يوم</strong> بعنوان سنة <strong><?= htmlspecialchars($leaveYear) ?></strong>.</p>
            <p>- <strong>المـــــــــادة 2</strong>: يكلف <?= htmlspecialchars($decisionmaker) ?> بتنفيـذ هـذا المقرر.</p>
            <div class="signature">
                <p>قالمة في: <strong><?= htmlspecialchars($docDate) ?></strong></p>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <style>
        .custom-law-container {
            animation: fadeIn 0.3s ease-out;
        }

        .custom-law-container .form-input {
            width: 1150px;
            height: 20px;
            margin-right: 220px;
            padding: 0.8rem 1.2rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            /* font-size: 1rem;
            transition: all 0.3s ease; */
            background: #fff;
            font-family: 'Tajawal', sans-serif;
        }

        .custom-law-container .form-input:focus {
            border-color: #3498db;
            box-shadow: 0 0 8px rgba(52, 152, 219, 0.2);
            outline: none;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
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
    <script>
        function toggleLawInput() {
            const lawSelect = document.getElementById('lawSelect');
            const lawCustomContainer = document.getElementById('lawCustomContainer');
            const lawCustomInput = document.getElementById('lawCustomInput');
            
            if (lawSelect.value === 'other') {
                lawCustomContainer.style.display = 'block';
                lawCustomInput.focus(); // Auto-focus the input when shown
            } else {
                lawCustomContainer.style.display = 'none';
                lawCustomInput.value = ''; // Clear the input when hiding
            }
        }

        document.querySelector('form').addEventListener('submit', function(e) {
            const lawSelect = document.getElementById('lawSelect');
            const lawCustomInput = document.getElementById('lawCustomInput');
            
            if (lawSelect.value === 'other') {
                if (!lawCustomInput.value.trim()) {
                    e.preventDefault();
                    alert('الرجاء إدخال النص القانوني المطلوب');
                    lawCustomInput.focus();
                    return;
                }
                
                // Create hidden input with the custom value
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'law';
                hiddenInput.value = lawCustomInput.value;
                this.appendChild(hiddenInput);
            }
        });
    </script>
</body>
</html>