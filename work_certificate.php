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
$prevPostes = [];
$actualPoste = ['position' => '', 'start' => ''];

// Get employee ID from URL
$employee_id = isset($_GET['id']) ? $_GET['id'] : '';

try {
    // Check if input is numeric (employee ID) or string (national ID)
    if(is_numeric($employee_id)) {
        $stmt = $pdo->prepare("SELECT * FROM employees WHERE employee_id = ?");
    } else {
        $stmt = $pdo->prepare("SELECT * FROM employees WHERE national_id = ?");
    }
    
    $stmt->execute([$employee_id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employee) {
        $error = "الموظف غير موجود في النظام";
    } else {
        // Auto-fill employee data
        $fullname = $employee['full_name_ar'];
        $bornPlace = $employee['birth_place'];
        $bornDate = date('d/m/Y', strtotime($employee['birth_date']));
        $actualPoste = [
            'position' => $employee['position'],
            'start' => date('d/m/Y', strtotime($employee['hire_date']))
        ];
    }
} catch (PDOException $e) {
    $error = "خطأ في قاعدة البيانات: " . $e->getMessage();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $docNum = $_POST['docNum'] ?? '';
    $docDate = $_POST['docDate'] ?? date('d/m/Y');
    
    // Process previous positions
    if (isset($_POST['prevPostes']) && is_array($_POST['prevPostes'])) {
        foreach ($_POST['prevPostes'] as $poste) {
            if (!empty($poste['position']) && !empty($poste['start']) && !empty($poste['end'])) {
                $prevPostes[] = [
                    'position' => $poste['position'],
                    'start' => $poste['start'],
                    'end' => $poste['end']
                ];
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>شهادة عمل - GRH Depf</title>
    <link rel="stylesheet" href="CSS/work_certificate.css">
    <link rel="stylesheet" href="CSS/icons.css">
    <style>
        /* Print-specific styles */
        @media print {
            @page {
                size: A4;
                margin: 0;
            }
            body {
                font-family: 'Amiri', 'Traditional Arabic', serif;
                direction: rtl;
                text-align: center;
                margin: 0;
                padding: 0;
                background: none;
            }
            .certificate {
                padding: 2cm;
                width: 21cm;
                min-height: 29.7cm;
                margin: 0 auto;
                line-height: 2;
                box-sizing: border-box;
                background: white;
            }
            .header {
                font-weight: bold;
                font-size: 22px;
            }
            .content {
                font-size: 20px;
                text-align: right;
                margin-right: 50px;
            }
            h1 {
                font-size: 26px;
                font-weight: bold;
                margin: 30px 0;
            }
            .signature {
                text-align: left;
                margin-top: 50px;
            }
            .docnum {
                text-align: right;
                font-size: 18px;
                font-weight: bold;
                margin-bottom: 30px;
            }
            .position-item {
                margin-right: 20px;
            }
            .no-print {
                display: none !important;
            }
        }
        
        /* Screen styles */
        @media screen {
            .certificate {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container no-print">
        <?php include 'header.php'; ?>

        <main class="dashboard-main">
            <?php if ($error): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= $error ?>
                    <div class="error-actions">
                        <a href="list_employees.php" class="back-button">
                            <i class="fas fa-arrow-right"></i> العودة إلى قائمة الموظفين
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <h1 class="dashboard-title">إنشاء شهادة عمل</h1>

                <!-- Data Entry Form -->
                <div class="form-container">
                    <form method="post">
                        <div class="form-group">
                            <label for="docNum">رقم الوثيقة:</label>
                            <input type="text" id="docNum" name="docNum" value="<?= htmlspecialchars($docNum) ?>" required>
                        </div>

                        <!-- Auto-filled Employee Info -->
                        <div class="form-group">
                            <label>الاسم الكامل:</label>
                            <input type="text" value="<?= htmlspecialchars($fullname) ?>" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label>تاريخ الميلاد:</label>
                            <input type="text" value="<?= htmlspecialchars($bornDate) ?>" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label>مكان الميلاد:</label>
                            <input type="text" value="<?= htmlspecialchars($bornPlace) ?>" readonly>
                        </div>

                        <!-- Current Position -->
                        <h3>المنصب الحالي</h3>
                        <div class="form-group">
                            <label>المنصب:</label>
                            <input type="text" value="<?= htmlspecialchars($actualPoste['position']) ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label>تاريخ البدء:</label>
                            <input type="text" value="<?= htmlspecialchars($actualPoste['start']) ?>" readonly>
                        </div>

                        <!-- Previous Positions (Optional) -->
                        <h3>المناصب السابقة (اختياري)</h3>
                        <div id="prevPostesContainer">
                            <?php foreach($prevPostes as $index => $poste): ?>
                            <div class="prev-poste-container">
                                <div class="form-group">
                                    <label>المنصب:</label>
                                    <input type="text" name="prevPostes[<?= $index ?>][position]" value="<?= htmlspecialchars($poste['position']) ?>">
                                </div>
                                <div class="form-group">
                                    <label>تاريخ البدء:</label>
                                    <input type="text" name="prevPostes[<?= $index ?>][start]" value="<?= htmlspecialchars($poste['start']) ?>" placeholder="DD/MM/YYYY">
                                </div>
                                <div class="form-group">
                                    <label>تاريخ الانتهاء:</label>
                                    <input type="text" name="prevPostes[<?= $index ?>][end]" value="<?= htmlspecialchars($poste['end']) ?>" placeholder="DD/MM/YYYY">
                                </div>
                                <button type="button" class="remove-prev-poste">إزالة</button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" id="addPrevPoste" class="add-prev-poste">إضافة منصب سابق</button>

                        <!-- Document Date -->
                        <div class="form-group">
                            <label for="docDate">تاريخ الوثيقة:</label>
                            <input type="text" id="docDate" name="docDate" value="<?= htmlspecialchars($docDate) ?>" required placeholder="DD/MM/YYYY">
                        </div>

                        <!-- Form Buttons -->
                        <button type="submit">إنشاء الشهادة</button>
                        <button type="button" onclick="window.print()" class="print-button">طباعة الشهادة</button>
                    </form>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Certificate (only shows when printing) -->
    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error): ?>
    <div class="certificate">
        <div class="header">الجمهوريـــــة الجزائريـــــة الديمقراطيـــــة الشعبيـــــة</div>
        <div class="header">وزارة التكوين والتعليم المهنيين</div>
        <div class="header">مديرية التكوين والتعليم المهنيين لولاية قالمة</div>
        <br>
        <div class="docnum">رقم: 2025/<?= htmlspecialchars($docNum) ?></div>
        <h1>شهادة عمل</h1>
        <div class="content">
            أنـا الممضي أسفله السيـد/ مدير التكويـن المهنـي لولاية قالمـة، أشهــد أن:
            <br><br>
            <strong>السيد:</strong> <?= htmlspecialchars($fullname) ?>
            <br>
            <strong>تاريـخ ومكـان الازديــاد:</strong> <?= htmlspecialchars($bornDate) ?>، <?= htmlspecialchars($bornPlace) ?>
            <br><br>
            <strong>شغل لدى مصالحي المناصب التالية:</strong>
            <br>
            
            <?php if(!empty($prevPostes)): ?>
                <?php foreach($prevPostes as $poste): ?>
                <div class="position-item">
                    - <?= htmlspecialchars($poste['position']) ?>، من <?= htmlspecialchars($poste['start']) ?> إلى غاية <?= htmlspecialchars($poste['end']) ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <div class="position-item">
                - <?= htmlspecialchars($actualPoste['position']) ?>، من <?= htmlspecialchars($actualPoste['start']) ?> إلى غاية يومنا هذا.
            </div>
            
            <strong>سلمـت هـذه الشهـادة للمعني بالأمـر، بطلـب منـه، لاستعمالـها فـي حـدود مـا يسمـح بـه القانـون.</strong>
            <br><br>
            <p class="signature">حرر بقالمة في <?= htmlspecialchars($docDate) ?></p>
        </div>
    </div>
    <?php endif; ?>

    <script src="workcertificate.js"></script>
</body>
</html>