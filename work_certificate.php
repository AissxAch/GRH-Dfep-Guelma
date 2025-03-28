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
        // Fetch existing previous positions
        $prevPosStmt = $pdo->prepare("SELECT * FROM employee_previous_positions WHERE employee_id = ? ORDER BY start_date");
        $prevPosStmt->execute([$employee['employee_id']]);
        $existingPrevPositions = $prevPosStmt->fetchAll(PDO::FETCH_ASSOC);

        // Auto-fill employee data
        $fullname = $employee['full_name_ar'];
        $bornPlace = $employee['birth_place'];
        $bornDate = date('d/m/Y', strtotime($employee['birth_date']));
        $actualPoste = [
            'position' => $employee['position'],
            'start' => date('d/m/Y', strtotime($employee['hire_date']))
        ];

        // Prepare existing previous positions for the form
        $prevPostes = array_map(function($pos) {
            return [
                'position' => $pos['position'],
                'start' => date('d/m/Y', strtotime($pos['start_date'])),
                'end' => date('d/m/Y', strtotime($pos['end_date']))
            ];
        }, $existingPrevPositions);
    }
} catch (PDOException $e) {
    $error = "خطأ في قاعدة البيانات: " . $e->getMessage();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $docNum = $_POST['docNum'] ?? '';
    $docDate = $_POST['docDate'] ?? date('d/m/Y');
    
    // Start a transaction
    $pdo->beginTransaction();

    try {
        // Remove existing previous positions for this employee
        $delStmt = $pdo->prepare("DELETE FROM employee_previous_positions WHERE employee_id = ?");
        $delStmt->execute([$employee['employee_id']]);

        // Process and insert new previous positions
        if (isset($_POST['prevPostes']) && is_array($_POST['prevPostes'])) {
            $insertStmt = $pdo->prepare("INSERT INTO employee_previous_positions 
                (employee_id, position, start_date, end_date) 
                VALUES (?, ?, STR_TO_DATE(?, '%d/%m/%Y'), STR_TO_DATE(?, '%d/%m/%Y'))");

            $prevPostes = []; // Reset and rebuild prevPostes
            foreach ($_POST['prevPostes'] as $poste) {
                if (!empty($poste['position']) && !empty($poste['start']) && !empty($poste['end'])) {
                    // Validate date format
                    $start = DateTime::createFromFormat('d/m/Y', $poste['start']);
                    $end = DateTime::createFromFormat('d/m/Y', $poste['end']);

                    if ($start && $end && $start < $end) {
                        $insertStmt->execute([
                            $employee['employee_id'], 
                            $poste['position'], 
                            $poste['start'], 
                            $poste['end']
                        ]);
                        
                        $prevPostes[] = [
                            'position' => $poste['position'],
                            'start' => $poste['start'],
                            'end' => $poste['end']
                        ];
                    } else {
                        throw new Exception("تواريخ غير صالحة: " . $poste['position']);
                    }
                }
            }
        }

        // Commit the transaction
        $pdo->commit();
    } catch (Exception $e) {
        // Rollback the transaction if something went wrong
        $pdo->rollBack();
        $error = "خطأ في حفظ المناصب السابقة: " . $e->getMessage();
    }
}
?>

<!-- Rest of the existing HTML remains the same -->
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>شهادة عمل - GRH Depf</title>
    <link rel="stylesheet" href="CSS/work_certificate.css">
    <link rel="stylesheet" href="CSS/icons.css">
</head>
<body>
    <div class="dashboard-container no-print">
        <?php include 'header.php'; ?>

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

    <script src="JS/workcertificate.js"></script>
</body>
</html>
