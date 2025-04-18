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
$absenceDate = '';
$daysDeducted = '';

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
    $absenceDate = $_POST['absenceDate'] ?? '';
    $daysDeducted = $_POST['daysDeducted'] ?? '';
    $absenceType = $_POST['absenceType'] ?? '';
    $law = $_POST['law'] ?? '';
    if (isset($_POST['lawCustom']) && !empty($_POST['lawCustom'])) {
        $law = $_POST['lawCustom'];
    }
    $suggest = $_POST['suggest'] ?? '';
    // Validate inputs
    if (empty($docNum) || empty($absenceDate) || empty($daysDeducted)) {
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
    <title>مقرر خصم - GRH Depf</title>
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
                    <h1 class="dashboard-title">إنشاء مقرر خصم</h1>
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
                        
                        <!-- Deduction Details Section -->
                        <div class="form-section">
                            <h3><i class="fas fa-calendar-times"></i> تفاصيل الخصم</h3>
                            
                            <div class="form-group">
                                <label for="absenceDate">تاريخ الغياب:</label>
                                <input type="text" id="absenceDate" name="absenceDate" value="<?= htmlspecialchars($absenceDate) ?>" 
                                    placeholder="DD/MM/YYYY" required>
                            </div>

                            <div class="form-group">
                                <label for="daysDeducted">نوع الغياب:</label>
                                <select name="absenceType" id="absenceType" required>
                                    <option value="">اختر نوع الغياب</option>
                                    <option value="المبرر">غياب مبرر</option>
                                    <option value="الغير مبرر">غياب غير مبرر</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="daysDeducted">عدد الأيام المخصومة:</label>
                                <input type="text" id="daysDeducted" name="daysDeducted" 
                                    value="<?= htmlspecialchars($daysDeducted) ?>" required placeholder="ادخل عدد الأيام المخصومة">
                            </div>
                            
                            <div class="form-group">
                                <label for="law">القانـون المنطبق:</label>
                                <select name="law" id="lawSelect" class="form-select" onchange="toggleLawInput()">
                                    <option value="">اختر القانون المنطبق</option>
                                    <option value="-بمقتضى المرسوم التنفيذي رقم 08-04 المؤرخ في 09/01/2008، المتضمن القانون الأساسي الخاص بالموظفين المنتمين للأسلاك المشتركة في المؤسسات والإدارات العمومية، المعدل والمتمم">-بمقتضى المرسوم التنفيذي رقم 08-04 المؤرخ في 09/01/2008، المتضمن القانون الأساسي الخاص بالموظفين المنتمين للأسلاك المشتركة في المؤسسات والإدارات العمومية، المعدل والمتمم</option>
                                    <option value="other">أخرى</option>
                                </select>
                                <div id="lawCustomContainer" class="custom-law-container" style="display: none;">
                                    <input type="text" id="lawCustomInput" name="lawCustom" class="form-input" placeholder="أدخل النص القانوني المطلوب">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="suggest">المقترح</label>
                                <input type="text" id="suggest" name="suggest" placeholder="ادخل منصب القترح" required>
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
                </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
    <!-- Deduction Decision (only shows when printing) -->
    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error): ?>
    <div class="deduction-decision">
        <div class="header">الجمهوريـــــة الجزائريـــــة الديمقراطيـــــة الشعبيـــــة</div>
        <div class="header">وزارة التكوين والتعليم المهنيين</div>
        <br>
        <div class="header2">مديرية التكوين والتعليم المهنيين لولاية قالمة</div>
        <div class="docnum">رقم: 2025/<?= htmlspecialchars($docNum) ?></div>
        <h1>مقـــــــرر</h1>
        <div class="content">
            <p>-إن مديــر التكوين والتعليم المهنيين،</p>
            <p>-بمقتضى الأمر رقم 06-03 المؤرخ في 15/07/2006، المتضمن القانون الأساسي العام للوظيفة العمومية، المتمم،</p>
            <p>-بمقتضى المرسوم التنفيذي رقم 90-99 المؤرخ في 27/03/1990، المتعلق بسلطة التعيين والتسيير الإداري إزاء موظفي وأعوان الإدارات المركزية، الولايات، البلديات وكذا المؤسسات العمومية ذات الطابع الإداري التابعة لها،</p>
            <p><?= htmlspecialchars($law) ?></p>
            <p>-نظرا للغياب <?= htmlspecialchars($absenceType)?> للسيد(ة) <strong><?= htmlspecialchars($fullname) ?></strong> <?= htmlspecialchars($employee['position']) ?> بتاريخ <strong><?= htmlspecialchars($absenceDate) ?></strong> في الفترة المسائية أو الصباحية،</p>
            <p><br>-باقتراح مـن <?= htmlspecialchars($suggest)?></p>
            <h1 class="ttt">يقـــــرر:</h1>
            <p>-<strong>المادة الأولى:</strong> يخصم <strong><?= htmlspecialchars($daysDeducted) ?></strong> يوم من الراتب الشهري للسيد(ة) <strong><?= htmlspecialchars($fullname) ?></strong> <?= htmlspecialchars($employee['position']) ?>، بسبب الغياب بتاريخ <strong><?= htmlspecialchars($absenceDate) ?></strong>.</p>
            <p>-<strong>المـــــادة 02:</strong> يكلف رئيس مكتب الميزانية والمحاسبة والوسائل العامة والأرشيف بتنفيذ هذا المقرر.</p>
            <div class="signature">
                <p >قالمة في: <strong><?= htmlspecialchars($docDate) ?></strong></p>
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