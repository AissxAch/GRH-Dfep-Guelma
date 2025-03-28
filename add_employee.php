<?php
session_start();
require_once 'config/dbconnect.php';
$pdo= getDBConnection();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$errors = [];
$success = '';
$previous_positions = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate main employee data
    $full_name_ar = trim($_POST['full_name_ar'] ?? '');
    $full_name_en = trim($_POST['full_name_en'] ?? '');
    $birth_date = trim($_POST['birth_date'] ?? '');
    $birth_place = trim($_POST['birth_place'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $bloodtype = trim($_POST['bloodtype'] ?? '');
    $national_id = trim($_POST['national_id'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $employee_position = trim($_POST['position'] ?? ''); // Changed variable name
    $department = trim($_POST['department'] ?? '');
    $hire_date = trim($_POST['hire_date'] ?? '');

    // Validate required fields
    if (empty($full_name_ar)) $errors[] = 'الاسم العربي مطلوب';
    if (empty($full_name_en)) $errors[] = 'الاسم الإنجليزي مطلوب';
    if (empty($birth_date)) $errors[] = 'تاريخ الميلاد مطلوب';
    if (empty($birth_place)) $errors[] = 'مكان الميلاد مطلوب';
    if (empty($national_id)) $errors[] = 'الرقم الوطني مطلوب';
    if (empty($phone)) $errors[] = 'رقم الهاتف مطلوب';
    if (empty($employee_position)) $errors[] = 'المنصب مطلوب'; // Updated validation
    if (empty($department)) $errors[] = 'القسم مطلوب';
    if (empty($hire_date)) $errors[] = 'تاريخ التعيين مطلوب';

    // Date validation and conversion
    function convertDate($date) {
        $date = DateTime::createFromFormat('d/m/Y', $date);
        return $date ? $date->format('Y-m-d') : null;
    }

    $birth_date_db = convertDate($birth_date);
    $hire_date_db = convertDate($hire_date);

    if (!$birth_date_db) $errors[] = 'صيغة تاريخ الميلاد غير صحيحة (dd/mm/yyyy)';
    if (!$hire_date_db) $errors[] = 'صيغة تاريخ التعيين غير صحيحة (dd/mm/yyyy)';

    // Validate previous positions
    $prev_positions = $_POST['prev_positions'] ?? [];
    $prev_start_dates = $_POST['prev_start_dates'] ?? [];
    $prev_end_dates = $_POST['prev_end_dates'] ?? [];

    foreach ($prev_positions as $index => $prev_position) { // Changed variable name
        if (!empty($prev_position)) {
            $start = $prev_start_dates[$index] ?? '';
            $end = $prev_end_dates[$index] ?? '';

            if (empty($start) || empty($end)) {
                $errors[] = 'يرجى إدخال تواريخ البدء والانتهاء لكل وظيفة سابقة';
            } elseif (strtotime($start) > strtotime($end)) {
                $errors[] = 'تاريخ البدء يجب أن يكون قبل تاريخ الانتهاء للوظيفة السابقة';
            }

            $previous_positions[] = [
                'position' => $prev_position, // Using renamed variable
                'start_date' => $start,
                'end_date' => $end
            ];
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Insert main employee data
            $stmt = $pdo->prepare("INSERT INTO employees 
                (full_name_ar, full_name_en, birth_date, birth_place, gender, bloodtype, 
                national_id, email, phone, address, position, department, hire_date) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute([
                $full_name_ar, $full_name_en, $birth_date_db, $birth_place, $gender, $bloodtype,
                $national_id, $email ?: null, $phone, $address ?: null, 
                $employee_position, // Using corrected variable name
                $department, 
                $hire_date_db
            ]);

            $employee_id = $pdo->lastInsertId();

            // Insert previous positions
            if (!empty($previous_positions)) {
                $prev_stmt = $pdo->prepare("INSERT INTO employee_previous_positions 
                    (employee_id, position, start_date, end_date) 
                    VALUES (?, ?, ?, ?)");

                foreach ($previous_positions as $prev_pos) {
                    $prev_stmt->execute([
                        $employee_id, 
                        $prev_pos['position'], 
                        $prev_pos['start_date'], 
                        $prev_pos['end_date']
                    ]);
                }
            }

            $pdo->commit();
            $success = 'تم إضافة الموظف بنجاح';
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = 'خطأ في قاعدة البيانات: ' . $e->getMessage();
        }
    }
}
?>


<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة موظف جديد - GRH Depf</title>
    <link rel="stylesheet" href="CSS/add_employee.css">
    <link rel="stylesheet" href="CSS/icons.css">
    <style>
        .error-message { color: #dc3545; padding: 10px; margin: 10px 0; border: 1px solid #f5c6cb; border-radius: 5px; }
        .success-message { color: #28a745; padding: 10px; margin: 10px 0; border: 1px solid #c3e6cb; border-radius: 5px; }
        .required { color: red; }
        .form-section { margin-bottom: 2rem; padding: 1.5rem; background: #f8f9fa; border-radius: 8px; }
        .input-group { margin-bottom: 1rem; }
        .input-row { display: flex; gap: 1rem; margin-bottom: 1rem; }
        .input-row .input-group { flex: 1; }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include 'header.php'; ?>

        <main class="dashboard-main">
            <h1 class="dashboard-title">إضافة موظف جديد</h1>
            
            <?php if (!empty($errors)): ?>
                <div class="error-message">
                    <?php foreach ($errors as $error): ?>
                        <p><?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form class="employee-form" method="POST">
                <!-- Personal Information Section -->
                <div class="form-section">
                    <h2><i class="fas fa-user"></i> المعلومات الشخصية</h2>
                    <div class="input-group">
                        <label>الاسم الكامل (عربي) <span class="required">*</span></label>
                        <input type="text" name="full_name_ar" value="<?= htmlspecialchars($_POST['full_name_ar'] ?? '') ?>" required>
                    </div>
                    
                    <div class="input-group">
                        <label>الاسم الكامل (إنجليزي) <span class="required">*</span></label>
                        <input type="text" name="full_name_en" value="<?= htmlspecialchars($_POST['full_name_en'] ?? '') ?>" required>
                    </div>

                    <div class="input-row">
                        <div class="input-group">
                            <label>تاريخ الميلاد <span class="required">*</span></label>
                            <input type="text" name="birth_date" placeholder="dd/mm/yyyy" 
                                   value="<?= htmlspecialchars($_POST['birth_date'] ?? '') ?>" pattern="\d{2}/\d{2}/\d{4}" required>
                        </div>
                        
                        <div class="input-group">
                            <label>مكان الميلاد <span class="required">*</span></label>
                            <input type="text" name="birth_place" value="<?= htmlspecialchars($_POST['birth_place'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="input-row">
                        <div class="input-group">
                            <label>النوع <span class="required">*</span></label>
                            <select name="gender" required>
                                <option value="male" <?= ($_POST['gender'] ?? '') === 'male' ? 'selected' : '' ?>>ذكر</option>
                                <option value="female" <?= ($_POST['gender'] ?? '') === 'female' ? 'selected' : '' ?>>أنثى</option>
                            </select>
                        </div>
                        
                        <div class="input-group">
                            <label>فصيلة الدم <span class="required">*</span></label>
                            <select name="bloodtype" required>
                                <option value="">اختر...</option>
                                <?php
                                $blood_types = ['O+', 'A+', 'B+', 'AB+', 'O-', 'A-', 'B-', 'AB-'];
                                foreach ($blood_types as $type) {
                                    $selected = ($_POST['bloodtype'] ?? '') === $type ? 'selected' : '';
                                    echo "<option value='$type' $selected>$type</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Previous Positions Section -->
                <div class="form-section">
                    <h2><i class="fas fa-history"></i> الوظائف السابقة</h2>
                    <div id="previous-positions-container">
                        <div class="previous-position-entry">
                            <div class="input-row">
                                <div class="input-group">
                                    <label>الوظيفة السابقة</label>
                                    <input type="text" name="prev_positions[]" 
                                           value="<?= htmlspecialchars($_POST['prev_positions'][0] ?? '') ?>"
                                           placeholder="اسم الوظيفة السابقة">
                                </div>
                                <div class="input-group">
                                    <label>تاريخ البدء</label>
                                    <input type="date" name="prev_start_dates[]" 
                                           value="<?= htmlspecialchars($_POST['prev_start_dates'][0] ?? '') ?>">
                                </div>
                                <div class="input-group">
                                    <label>تاريخ الانتهاء</label>
                                    <input type="date" name="prev_end_dates[]" 
                                           value="<?= htmlspecialchars($_POST['prev_end_dates'][0] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="actions">
                        <button type="button" id="add-previous-position" class="login-button">
                            <i class="fas fa-plus"></i> إضافة وظيفة سابقة
                        </button>
                        <button type="button" id="remove-previous-position" class="delete-button">
                            <i class="fas fa-minus"></i> حذف وظيفة سابقة
                        </button>
                    </div>
                </div>

                <!-- Job Information Section -->
                <div class="form-section">
                    <h2><i class="fas fa-address-card"></i> المعلومات الوظيفية</h2>
                    <div class="input-row">
                        <div class="input-group">
                            <label>الرقم الوطني <span class="required">*</span></label>
                            <input type="text" name="national_id" 
                                   value="<?= htmlspecialchars($_POST['national_id'] ?? '') ?>" required>
                        </div>
                        
                        <div class="input-group">
                            <label>تاريخ التعيين <span class="required">*</span></label>
                            <input type="text" name="hire_date" placeholder="dd/mm/yyyy" 
                                   value="<?= htmlspecialchars($_POST['hire_date'] ?? '') ?>" pattern="\d{2}/\d{2}/\d{4}" required>
                        </div>
                    </div>

                    <div class="input-row">
                        <div class="input-group">
                            <label>القسم <span class="required">*</span></label>
                            <select name="department" required>
                                <option value="HR" <?= ($_POST['department'] ?? '') === 'HR' ? 'selected' : '' ?>>الموارد البشرية</option>
                                <option value="Finance" <?= ($_POST['department'] ?? '') === 'Finance' ? 'selected' : '' ?>>المالية</option>
                                <option value="IT" <?= ($_POST['department'] ?? '') === 'IT' ? 'selected' : '' ?>>تقنية المعلومات</option>
                                <option value="Operations" <?= ($_POST['department'] ?? '') === 'Operations' ? 'selected' : '' ?>>العمليات</option>
                            </select>
                        </div>
                        
                        <div class="input-group">
                            <label>المنصب <span class="required">*</span></label>
                            <input type="text" name="position" 
                                value="<?= htmlspecialchars($_POST['position'] ?? '') ?>" required>
                        </div>
                    </div>
                </div>

                <!-- Contact Information Section -->
                <div class="form-section">
                    <h2><i class="fas fa-phone"></i> معلومات الاتصال</h2>
                    <div class="input-row">
                        <div class="input-group">
                            <label>رقم الهاتف <span class="required">*</span></label>
                            <input type="tel" name="phone" 
                                   value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" required>
                        </div>
                        
                        <div class="input-group">
                            <label>البريد الإلكتروني</label>
                            <input type="email" name="email" 
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="input-group">
                        <label>العنوان</label>
                        <textarea name="address" rows="3"><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" class="login-button">
                        <i class="fas fa-save"></i> حفظ البيانات
                    </button>
                    <a href="index.php" class="cancel-button">
                        <i class="fas fa-times"></i> إلغاء
                    </a>
                </div>
            </form>
        </main>
    </div>

    <script>
        // Add Previous Position
        document.getElementById('add-previous-position').addEventListener('click', function() {
            const container = document.getElementById('previous-positions-container');
            const newEntry = container.children[0].cloneNode(true);
            
            // Clear input values
            newEntry.querySelectorAll('input').forEach(input => input.value = '');
            
            container.appendChild(newEntry);
        });

        // Remove Last Position
        document.getElementById('remove-previous-position').addEventListener('click', function() {
            const container = document.getElementById('previous-positions-container');
            if (container.children.length > 1) {
                container.removeChild(container.lastElementChild);
            }
        });
    </script>
</body>
</html>