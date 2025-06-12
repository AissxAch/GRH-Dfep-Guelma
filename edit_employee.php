<?php
session_start();
require_once 'config/dbconnect.php';
$pdo = getDBConnection();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$errors = [];
$success = '';
$employee = [];
$previous_positions = [];
$departments = [];
$high_level_positions = [];
$current_high_level_position = null;

// Fetch positions
$positions = [];
try {
    $stmt = $pdo->query("SELECT * FROM positions ORDER BY name ASC");
    $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "خطأ في جلب المناصب: " . $e->getMessage();
}

// Fetch high level positions
try {
    $stmt = $pdo->query("SELECT * FROM high_level_positions ORDER BY name ASC");
    $high_level_positions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "خطأ في جلب المناصب العليا: " . $e->getMessage();
}

// Fetch departments
try {
    $stmt = $pdo->query("SELECT * FROM departments");
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = 'خطأ في جلب الأقسام: ' . $e->getMessage();
}

// Fetch employee data if ID is provided
if (isset($_GET['id'])) {
    $employee_id = intval($_GET['id']);
    
    try {
        // Get employee data
        $stmt = $pdo->prepare("SELECT * FROM employees WHERE employee_id = ?");
        $stmt->execute([$employee_id]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$employee) {
            $errors[] = 'الموظف غير موجود';
        } else {
            // Get previous positions
            $stmt = $pdo->prepare("SELECT * FROM employee_previous_positions WHERE employee_id = ?");
            $stmt->execute([$employee_id]);
            $previous_positions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get current high level position
            $stmt = $pdo->prepare("SELECT * FROM employee_high_level_history WHERE employee_id = ? AND end_date IS NULL");
            $stmt->execute([$employee_id]);
            $current_high_level_position = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        $errors[] = 'خطأ في قاعدة البيانات: ' . $e->getMessage();
    }
} else {
    $errors[] = 'معرف الموظف غير محدد';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_employee'])) {
    $employee_id = intval($_POST['employee_id']);
    $firstname_ar = trim($_POST['firstname_ar'] ?? '');
    $lastname_ar = trim($_POST['lastname_ar'] ?? '');
    $firstname_en = trim($_POST['firstname_en'] ?? '');
    $lastname_en = trim($_POST['lastname_en'] ?? '');
    $father_lastname = trim($_POST['father_lastname'] ?? '');
    $mother_firstname = trim($_POST['mother_firstname'] ?? '');
    $mother_lastname = trim($_POST['mother_lastname'] ?? '');
    $marital_status = trim($_POST['marital_status'] ?? ''); // Added marital_status

    $birth_date = trim($_POST['birth_date'] ?? '');
    $birth_place = trim($_POST['birth_place'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $bloodtype = trim($_POST['bloodtype'] ?? '');
    $vacances_remain_days = trim($_POST['vacances_remain_days'] ?? '');
    $national_id = trim($_POST['national_id'] ?? '');
    $ssn = trim($_POST['ssn'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $department_id = trim($_POST['department_id'] ?? '');
    $first_hire_date = trim($_POST['first_hire_date'] ?? '');
    $hire_date = trim($_POST['hire_date'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $delete_all_prev_positions = isset($_POST['delete_all_prev_positions']);
    $high_level_position_id = trim($_POST['high_level_position_id'] ?? '');
    $high_level_start_date = trim($_POST['high_level_start_date'] ?? '');

    // Validate required fields
    if (empty($firstname_ar)) $errors[] = 'اللقب العربي مطلوب';
    if (empty($lastname_ar)) $errors[] = 'الاسم العربي مطلوب';
    if (empty($firstname_en)) $errors[] = 'اللقب الإنجليزي مطلوب';
    if (empty($lastname_en)) $errors[] = 'الاسم الإنجليزي مطلوب';
    if (empty($birth_date)) $errors[] = 'تاريخ الميلاد مطلوب';
    if (empty($birth_place)) $errors[] = 'مكان الميلاد مطلوب';
    if (empty($national_id)) $errors[] = 'الرقم الوطني مطلوب';
    if (empty($ssn)) $errors[] = 'رقم الضمان الاجتماعي مطلوب';
    if (empty($phone)) $errors[] = 'رقم الهاتف مطلوب';
    if (empty($position)) $errors[] = 'المنصب مطلوب';
    if (empty($department_id)) $errors[] = 'القسم مطلوب';
    if (empty($hire_date)) $errors[] = 'تاريخ التعيين مطلوب';
    if (empty($first_hire_date)) $errors[] = 'تاريخ أول تعيين مطلوب';
    if (empty($vacances_remain_days)) $errors[] = 'الأيام المتبقية للإجازة مطلوبة';

    // Validate name fields contain only letters
    if (!preg_match('/^[\p{Arabic}\s]+$/u', $firstname_ar)) $errors[] = 'اللقب العربي يجب أن يحتوي على أحرف عربية فقط';
    if (!preg_match('/^[\p{Arabic}\s]+$/u', $lastname_ar)) $errors[] = 'الاسم العربي يجب أن يحتوي على أحرف عربية فقط';
    if (!preg_match('/^[a-zA-Z\s]+$/', $firstname_en)) $errors[] = 'اللقب الفرنسي يجب أن يحتوي على أحرف لاتينية فقط';
    if (!preg_match('/^[a-zA-Z\s]+$/', $lastname_en)) $errors[] = 'الاسم الفرنسي يجب أن يحتوي على أحرف لاتينية فقط';

    // Validate father and mother names contain only Arabic letters
    if (!empty($father_lastname) && !preg_match('/^[\p{Arabic}\s]+$/u', $father_lastname)) $errors[] = 'اسم الأب يجب أن يحتوي على أحرف عربية فقط';
    if (!empty($mother_firstname) && !preg_match('/^[\p{Arabic}\s]+$/u', $mother_firstname)) $errors[] = 'اسم الأم يجب أن يحتوي على أحرف عربية فقط';
    if (!empty($mother_lastname) && !preg_match('/^[\p{Arabic}\s]+$/u', $mother_lastname)) $errors[] = 'لقب الأم يجب أن يحتوي على أحرف عربية فقط';

    // Validate numeric fields contain only numbers
    if (!preg_match('/^\d+$/', $national_id)) $errors[] = 'الرقم الوطني يجب أن يحتوي على أرقام فقط';
    if (!preg_match('/^\d+$/', $ssn)) $errors[] = 'رقم الضمان الاجتماعي يجب أن يحتوي على أرقام فقط';
    if (!preg_match('/^\d+$/', $phone)) $errors[] = 'رقم الهاتف يجب أن يحتوي على أرقام فقط';
    if (!preg_match('/^\d+$/', $vacances_remain_days)) $errors[] = 'أيام الإجازة المتبقية يجب أن تحتوي على أرقام فقط';

    // Validate previous positions
    $prev_positions = $_POST['prev_positions'] ?? [];
    $prev_start_dates = $_POST['prev_start_dates'] ?? [];
    $prev_end_dates = $_POST['prev_end_dates'] ?? [];
    $prev_departments = $_POST['prev_departments'] ?? [];
    $prev_ids = $_POST['prev_ids'] ?? [];

    foreach ($prev_positions as $index => $prev_position) {
        if (!empty($prev_position)) {
            $start = $prev_start_dates[$index] ?? '';
            $end = $prev_end_dates[$index] ?? '';
            $dept_id = $prev_departments[$index] ?? null;

            if (empty($start) || empty($end)) {
                $errors[] = 'يرجى إدخال تواريخ البدء والانتهاء لكل وظيفة سابقة';
            } else if (strtotime($start) > strtotime($end)) {
                $errors[] = 'تاريخ الانتهاء يجب أن يكون بعد تاريخ البدء للوظيفة السابقة: ' . $prev_position;
            }
        }
    }
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Update main employee data (added marital_status)
            $stmt = $pdo->prepare("UPDATE employees SET 
                firstname_ar = ?, lastname_ar = ?, firstname_en = ?, lastname_en = ?, father_lastname = ?, mother_firstname = ?, mother_lastname = ?, marital_status = ?,
                birth_date = ?, birth_place = ?, gender = ?, bloodtype = ?, 
                vacances_remain_days = ?, national_id = ?, ssn = ?, email = ?, 
                phone = ?, address = ?, position = ?, department_id = ?, 
                first_hire_date = ?, hire_date = ?, is_active = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE employee_id = ?");
            
            $stmt->execute([
                $firstname_ar, $lastname_ar, $firstname_en, $lastname_en, $father_lastname, $mother_firstname, $mother_lastname, $marital_status,
                $birth_date, $birth_place, $gender, $bloodtype,
                $vacances_remain_days, $national_id, $ssn, $email ?: null,
                $phone, $address ?: null, $position, $department_id,
                $first_hire_date, $hire_date, $is_active, $employee_id
            ]);

            // Handle high level position
            if (!empty($high_level_position_id)) {
                if ($current_high_level_position) {
                    // Update existing high level position
                    $stmt = $pdo->prepare("UPDATE employee_high_level_history 
                                          SET position_id = ?, start_date = ?
                                          WHERE history_id = ?");
                    $stmt->execute([
                        $high_level_position_id,
                        $high_level_start_date,
                        $current_high_level_position['history_id']
                    ]);
                } else {
                    // Insert new high level position
                    $stmt = $pdo->prepare("INSERT INTO employee_high_level_history 
                                          (employee_id, position_id, start_date)
                                          VALUES (?, ?, ?)");
                    $stmt->execute([
                        $employee_id,
                        $high_level_position_id,
                        $high_level_start_date
                    ]);
                }
            } else {
                // If high level position was removed
                if ($current_high_level_position) {
                    $stmt = $pdo->prepare("DELETE FROM employee_high_level_history 
                                          WHERE history_id = ?");
                    $stmt->execute([$current_high_level_position['history_id']]);
                }
            }

            // Handle previous positions
            if ($delete_all_prev_positions) {
                // Delete all previous positions
                $delete_stmt = $pdo->prepare("DELETE FROM employee_previous_positions WHERE employee_id = ?");
                $delete_stmt->execute([$employee_id]);
            } else {
                // Handle individual position updates
                $prev_positions = $_POST['prev_positions'] ?? [];
                $prev_start_dates = $_POST['prev_start_dates'] ?? [];
                $prev_end_dates = $_POST['prev_end_dates'] ?? [];
                $prev_departments = $_POST['prev_departments'] ?? [];
                $prev_ids = $_POST['prev_ids'] ?? [];

                // Delete positions not in the submitted list
                if (!empty(array_filter($prev_ids, 'is_numeric'))) {
                    $delete_stmt = $pdo->prepare("DELETE FROM employee_previous_positions 
                                                WHERE employee_id = ? AND id NOT IN (" . 
                                                implode(',', array_filter($prev_ids, 'is_numeric')) . ")");
                    $delete_stmt->execute([$employee_id]);
                }

                // Update or insert positions
                $position_stmt = $pdo->prepare("INSERT INTO employee_previous_positions 
                    (id, employee_id, position, department_id, start_date, end_date) 
                    VALUES (?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                    position = VALUES(position), 
                    department_id = VALUES(department_id),
                    start_date = VALUES(start_date), 
                    end_date = VALUES(end_date)");

                foreach ($prev_positions as $index => $prev_position) {
                    if (!empty($prev_position)) {
                        $start_db = $prev_start_dates[$index];
                        $end_db = $prev_end_dates[$index];
                        $dept_id = $prev_departments[$index] ?? null;

                        if ($start_db && $end_db && $dept_id) {
                            $position_stmt->execute([
                                $prev_ids[$index] ?: null,
                                $employee_id,
                                $prev_position,
                                $dept_id,
                                $start_db,
                                $end_db
                            ]);
                        }
                    }
                }
            }

            $pdo->commit();
            $success = 'تم تحديث بيانات الموظف بنجاح';
            
            // Refresh employee data
            $stmt = $pdo->prepare("SELECT * FROM employees WHERE employee_id = ?");
            $stmt->execute([$employee_id]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Refresh previous positions
            $stmt = $pdo->prepare("SELECT * FROM employee_previous_positions WHERE employee_id = ?");
            $stmt->execute([$employee_id]);
            $previous_positions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Refresh high level position
            $stmt = $pdo->prepare("SELECT * FROM employee_high_level_history WHERE employee_id = ? AND end_date IS NULL");
            $stmt->execute([$employee_id]);
            $current_high_level_position = $stmt->fetch(PDO::FETCH_ASSOC);
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
    <title>تعديل موظف - GRH Depf</title>
    <link rel="stylesheet" href="CSS/edit_employee.css">
    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="CSS/icons.css">
    <style>
        .delete-position-btn {
            display: flex;
            align-items: flex-end;
            margin-bottom: 10px;
        }
        .delete-position-button {
            background-color: #e74c3c;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 6px 10px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .delete-position-button:hover {
            background-color: #c0392b;
        }
        .cancel-button {
            background-color: #6c757d;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        .delete-all-container {
            margin: 15px 0;
            padding: 10px;
            background: #fff8f8;
            border: 1px solid #ffdddd;
            border-radius: 5px;
        }
        .delete-all-checkbox {
            margin-left: 10px;
        }
        .disabled-positions {
            opacity: 0.6;
            pointer-events: none;
        }
        .error-text {
            color: #dc3545;
            font-size: 0.8rem;
            margin-top: 0.25rem;
            display: none;
        }
        .input-group {
            position: relative;
            margin-bottom: 1.5rem;
        }
        input.invalid {
            border-color: #dc3545 !important;
        }
        .error-message { color: #dc3545; padding: 10px; margin: 10px 0; border: 1px solid #f5c6cb; border-radius: 5px; }
        .success-message { color: #28a745; padding: 10px; margin: 10px 0; border: 1px solid #c3e6cb; border-radius: 5px; }
        .required { color: red; }
        .form-section { margin-bottom: 2rem; padding: 1.5rem; background: #f8f9fa; border-radius: 8px; }
        .input-row { display: flex; gap: 1rem; margin-bottom: 1rem; }
        .input-row .input-group { flex: 1; }
        .error-text {
            color: #dc3545;
            font-size: 0.8rem;
            margin-top: 0.25rem;
            display: none;
        }

        input.invalid {
            border-color: #dc3545 !important;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include 'header.php'; ?>

        <main class="dashboard-main">
            <h1 class="dashboard-title">تعديل بيانات الموظف</h1>
            
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

            <?php if (!empty($employee)): ?>
            <form class="employee-form" method="POST">
                <input type="hidden" name="employee_id" value="<?= $employee['employee_id'] ?>">
                <!-- Personal Information Section -->
                <div class="form-section">
                    <h2><i class="fas fa-user"></i> المعلومات الشخصية</h2>
                    <div class="input-row">
                        <div class="input-group">
                            <label>اللقب (عربي) <span class="required">*</span></label>
                            <input type="text" name="firstname_ar" value="<?= htmlspecialchars($employee['firstname_ar']) ?>" 
                                   onkeypress="return validateArabicLetter(event, this)" 
                                   oninput="validateArabicInput(this)" required>
                            <div class="error-text" id="firstname_ar_error">اللقب العربي يجب أن يحتوي على أحرف عربية فقط</div>
                        </div>
                        <div class="input-group">
                            <label>الاسم (عربي) <span class="required">*</span></label>
                            <input type="text" name="lastname_ar" value="<?= htmlspecialchars($employee['lastname_ar']) ?>" 
                                   onkeypress="return validateArabicLetter(event, this)" 
                                   oninput="validateArabicInput(this)" required>
                            <div class="error-text" id="lastname_ar_error">الاسم العربي يجب أن يحتوي على أحرف عربية فقط</div>
                        </div>
                    </div>
                    
                    <div class="input-row">
                        <div class="input-group">
                            <label>Nom <span class="required">*</span></label>
                            <input type="text" name="firstname_en" value="<?= htmlspecialchars($employee['firstname_en']) ?>" 
                                   onkeypress="return validateLatinLetter(event, this)" 
                                   oninput="validateLatinInput(this)" required>
                            <div class="error-text" id="firstname_en_error">يجب أن يحتوي على أحرف لاتينية فقط</div>
                        </div>
                        <div class="input-group">
                            <label>Prenom <span class="required">*</span></label>
                            <input type="text" name="lastname_en" value="<?= htmlspecialchars($employee['lastname_en']) ?>" 
                                   onkeypress="return validateLatinLetter(event, this)" 
                                   oninput="validateLatinInput(this)" required>
                            <div class="error-text" id="lastname_en_error">يجب أن يحتوي على أحرف لاتينية فقط</div>
                        </div>
                    </div>

                    <div class="input-row">
                        <div class="input-group">
                            <label>تاريخ الميلاد <span class="required">*</span></label>
                            <input type="date" name="birth_date" 
                                   value="<?= htmlspecialchars($employee['birth_date']) ?>" required>
                        </div>
                        
                        <div class="input-group">
                            <label>مكان الميلاد <span class="required">*</span></label>
                            <input type="text" name="birth_place" value="<?= htmlspecialchars($employee['birth_place']) ?>" required>
                        </div>
                    </div>
                    
                    <div class="input-group">
                        <label>اسم الأب</label>
                        <input type="text" name="father_lastname" 
                               value="<?= htmlspecialchars($employee['father_lastname'] ?? '') ?>"
                               onkeypress="return validateArabicLetter(event, this)" 
                               oninput="validateArabicInput(this)">
                        <div class="error-text" id="father_lastname_error">يجب أن يحتوي على أحرف عربية فقط</div>
                    </div>
                    
                    <div class="input-group">
                        <label>اسم الأم</label>
                        <input type="text" name="mother_firstname" 
                               value="<?= htmlspecialchars($employee['mother_firstname'] ?? '') ?>"
                               onkeypress="return validateArabicLetter(event, this)" 
                               oninput="validateArabicInput(this)">
                        <div class="error-text" id="mother_firstname_error">يجب أن يحتوي على أحرف عربية فقط</div>
                    </div>
    
                    <div class="input-group">
                        <label>لقب الأم</label>
                        <input type="text" name="mother_lastname" 
                               value="<?= htmlspecialchars($employee['mother_lastname'] ?? '') ?>"
                               onkeypress="return validateArabicLetter(event, this)" 
                               oninput="validateArabicInput(this)">
                        <div class="error-text" id="mother_lastname_error">يجب أن يحتوي على أحرف عربية فقط</div>
                    </div>
                    
                    <div class="input-row">
                        <div class="input-group">
                            <label>الحالة الاجتماعية</label>
                            <select name="marital_status">
                                <option value="single" <?= ($employee['marital_status'] ?? '') === 'single' ? 'selected' : '' ?>>أعزب/عزباء</option>
                                <option value="married" <?= ($employee['marital_status'] ?? '') === 'married' ? 'selected' : '' ?>>متزوج/متزوجة</option>
                                <option value="divorced" <?= ($employee['marital_status'] ?? '') === 'divorced' ? 'selected' : '' ?>>مطلق/مطلقة</option>
                                <option value="widowed" <?= ($employee['marital_status'] ?? '') === 'widowed' ? 'selected' : '' ?>>أرمل/أرملة</option>
                            </select>
                        </div>
                        
                        <div class="input-group">
                            <label>النوع <span class="required">*</span></label>
                            <select name="gender" required>
                                <option value="male" <?= $employee['gender'] === 'male' ? 'selected' : '' ?>>ذكر</option>
                                <option value="female" <?= $employee['gender'] === 'female' ? 'selected' : '' ?>>أنثى</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="input-row">
                        <div class="input-group">
                            <label>فصيلة الدم <span class="required">*</span></label>
                            <select name="bloodtype" required>
                                <option value="">اختر...</option>
                                <?php
                                $blood_types = ['O+', 'A+', 'B+', 'AB+', 'O-', 'A-', 'B-', 'AB-'];
                                foreach ($blood_types as $type) {
                                    $selected = $employee['bloodtype'] === $type ? 'selected' : '';
                                    echo "<option value='$type' $selected>$type</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="input-group">
                            <label>الأيام المتبقية للإجازة <span class="required">*</span></label>
                            <input type="text" name="vacances_remain_days" value="<?= htmlspecialchars($employee['vacances_remain_days']) ?>" 
                                   onkeypress="return validateNumber(event, this)"
                                   oninput="validateNumberInput(this)" required>
                            <div class="error-text" id="vacances_remain_days_error">يجب أن يحتوي على أرقام فقط</div>
                        </div>
                    </div>
                    
                    <div class="input-row">
                        <div class="input-group">
                            <label>الرقم الوطني <span class="required">*</span></label>
                            <input type="text" name="national_id" value="<?= htmlspecialchars($employee['national_id']) ?>" 
                                   onkeypress="return validateNumber(event, this)"
                                   oninput="validateNumberInput(this)" required>
                            <div class="error-text" id="national_id_error">يجب أن يحتوي على أرقام فقط</div>
                        </div>
                        <div class="input-group">
                            <label>رقم الضمان الاجتماعي <span class="required">*</span></label>
                            <input type="text" name="ssn" value="<?= htmlspecialchars($employee['ssn']) ?>" 
                                   onkeypress="return validateNumber(event, this)"
                                   oninput="validateNumberInput(this)" required>
                            <div class="error-text" id="ssn_error">يجب أن يحتوي على أرقام فقط</div>
                        </div>
                    </div>
                </div>
                
                <!-- Job Information Section -->
                <div class="form-section">
                    <h2><i class="fas fa-address-card"></i> المعلومات الوظيفية</h2>
                    <div class="input-row">
                        <div class="input-group">
                            <label>تاريخ التعيين <span class="required">*</span></label>
                            <input type="date" name="hire_date" 
                                   value="<?= htmlspecialchars($employee['hire_date']) ?>" required>
                        </div>
                        <div class="input-group">
                            <label>تاريخ أول تعيين <span class="required">*</span></label>
                            <input type="date" name="first_hire_date" 
                                   value="<?= htmlspecialchars($employee['first_hire_date']) ?>" required>
                        </div>
                    </div>

                    <div class="input-row">
                        <div class="input-group">
                            <label>القسم <span class="required">*</span></label>
                            <select name="department_id" required>
                                <option value="">اختر قسمًا...</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= $dept['department_id'] ?>" <?= $employee['department_id'] == $dept['department_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($dept['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="input-group">
                            <label>منصب الشغل <span class="required">*</span></label>
                            <select name="position" required>
                                <option value="">اختر منصب الشغل...</option>
                                <?php foreach ($positions as $pos): ?>
                                    <option value="<?= $pos['name'] ?>" 
                                        <?= $employee['position'] == $pos['name'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($pos['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- High Level Position Section -->
                    <div class="input-row">
                        <div class="input-group">
                            <label>المنصب العالي</label>
                            <select name="high_level_position_id">
                                <option value="">اختر منصبًا عاليًا...</option>
                                <?php foreach ($high_level_positions as $hlp): ?>
                                    <option value="<?= $hlp['position_id'] ?>" 
                                        <?= $current_high_level_position && $current_high_level_position['position_id'] == $hlp['position_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($hlp['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="input-group">
                            <label>تاريخ بداية المنصب العالي</label>
                            <input type="date" name="high_level_start_date" 
                                value="<?= $current_high_level_position ? htmlspecialchars($current_high_level_position['start_date']) : '' ?>">
                        </div>
                    </div>
                    
                    <div class="input-group">
                        <label>
                            <input type="checkbox" name="is_active" <?= $employee['is_active'] ? 'checked' : '' ?>>
                            الحساب نشط
                        </label>
                    </div>
                </div>
                
               <!-- Previous Positions Section -->
                <div class="form-section">
                    <h2><i class="fas fa-history"></i> الوظائف السابقة</h2>
                    
                    <div class="delete-all-container">
                        <label>
                            <input type="checkbox" name="delete_all_prev_positions" id="delete_all_prev_positions" class="delete-all-checkbox">
                            حذف جميع الوظائف السابقة
                        </label>
                        <small style="color: #666;">(سيؤدي تحديد هذا الخيار إلى إزالة جميع الوظائف السابقة للموظف)</small>
                    </div>
                    
                    <div id="previous-positions-container" class="<?= isset($_POST['delete_all_prev_positions']) ? 'disabled-positions' : '' ?>">
                        <?php if (empty($previous_positions)): ?>
                            <div class="previous-position-entry">
                                <div class="input-row">
                                    <div class="input-group">
                                        <label>الوظيفة السابقة</label>
                                        <select name="prev_positions[]">
                                            <option value="">اختر منصب الشغل السابق</option>
                                            <?php foreach ($positions as $pos): ?>
                                                <option value="<?= $pos['name'] ?>">
                                                    <?= htmlspecialchars($pos['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="hidden" name="prev_ids[]" value="">
                                    </div>
                                    <div class="input-group">
                                        <label>القسم <span class="required">*</span></label>
                                        <select name="prev_departments[]" required>
                                            <option value="">اختر قسمًا...</option>
                                            <?php foreach ($departments as $dept): ?>
                                                <option value="<?= $dept['department_id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="input-group">
                                        <label>تاريخ البدء <span class="required">*</span></label>
                                        <input type="date" name="prev_start_dates[]" required
                                            onchange="validateDateRange(this, this.closest('.input-row').querySelector('input[name=\'prev_end_dates[]\']'))">
                                    </div>
                                    <div class="input-group">
                                        <label>تاريخ الانتهاء <span class="required">*</span></label>
                                        <input type="date" name="prev_end_dates[]" required
                                            onchange="validateDateRange(this.closest('.input-row').querySelector('input[name=\'prev_start_dates[]\']'), this)">
                                        <div class="error-text">تاريخ الانتهاء يجب أن يكون بعد تاريخ البدء</div>
                                    </div>
                                    <div class="input-group delete-position-btn">
                                        <button type="button" class="delete-position-button">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($previous_positions as $pos): ?>
                                <div class="previous-position-entry">
                                    <div class="input-row">
                                        <div class="input-group">
                                            <label>الوظيفة السابقة</label>
                                            <select name="prev_positions[]">
                                                <option value="">اختر منصب الشغل السابق</option>
                                                <?php foreach ($positions as $position): ?>
                                                    <option value="<?= $position['name'] ?>" <?= $pos['position'] == $position['name'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($position['name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="hidden" name="prev_ids[]" value="<?= $pos['id'] ?>">
                                        </div>
                                        <div class="input-group">
                                            <label>القسم <span class="required">*</span></label>
                                            <select name="prev_departments[]" required>
                                                <option value="">اختر قسمًا...</option>
                                                <?php foreach ($departments as $dept): ?>
                                                    <option value="<?= $dept['department_id'] ?>" <?= isset($pos['department_id']) && $pos['department_id'] == $dept['department_id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($dept['name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="input-group">
                                            <label>تاريخ البدء <span class="required">*</span></label>
                                            <input type="date" name="prev_start_dates[]" 
                                                value="<?= htmlspecialchars($pos['start_date']) ?>" required
                                                onchange="validateDateRange(this, this.closest('.input-row').querySelector('input[name=\'prev_end_dates[]\']'))">
                                        </div>
                                        <div class="input-group">
                                            <label>تاريخ الانتهاء <span class="required">*</span></label>
                                            <input type="date" name="prev_end_dates[]" 
                                                value="<?= htmlspecialchars($pos['end_date']) ?>" required
                                                onchange="validateDateRange(this.closest('.input-row').querySelector('input[name=\'prev_start_dates[]\']'), this)">
                                            <div class="error-text">تاريخ الانتهاء يجب أن يكون بعد تاريخ البدء</div>
                                        </div>
                                        <div class="input-group delete-position-btn">
                                            <button type="button" class="delete-position-button">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="actions">
                        <button type="button" id="add-previous-position" class="login-button">
                            <i class="fas fa-plus"></i> إضافة وظيفة سابقة
                        </button>
                        <button type="button" id="remove-previous-position" class="cancel-button">
                            <i class="fas fa-minus"></i> حذف وظيفة سابقة
                        </button>
                    </div>
                </div>
                
                <!-- Contact Information Section -->
                <div class="form-section">
                    <h2><i class="fas fa-phone"></i> معلومات الاتصال</h2>
                    <div class="input-row">
                        <div class="input-group">
                            <label>رقم الهاتف <span class="required">*</span></label>
                            <input type="text" name="phone" 
                                   value="<?= htmlspecialchars($employee['phone']) ?>" 
                                   onkeypress="return validateNumber(event, this)"
                                   oninput="validateNumberInput(this)" required>
                            <div class="error-text" id="phone_error">يجب أن يحتوي على أرقام فقط</div>
                        </div>
                        
                        <div class="input-group">
                            <label>البريد الإلكتروني</label>
                            <input type="email" name="email" 
                                   value="<?= htmlspecialchars($employee['email']) ?>">
                        </div>
                    </div>

                    <div class="input-group">
                        <label>العنوان</label>
                        <textarea name="address" rows="3"><?= htmlspecialchars($employee['address']) ?></textarea>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" name="update_employee" class="login-button">
                        <i class="fas fa-save"></i> حفظ التعديلات
                    </button>
                    <a href="employee.php?id=<?= $employee['employee_id'] ?>" class="cancel-button">
                        <i class="fas fa-times"></i> إلغاء
                    </a>
                </div>
            </form>
            <?php else: ?>
                <div class="no-employee">
                    <p>لا يوجد موظف بهذا المعرف</p>
                    <a href="list_employees.php" class="cancel-button">
                        <i class="fas fa-arrow-left"></i> العودة إلى قائمة الموظفين
                    </a>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        // Add Previous Position
        document.getElementById('add-previous-position').addEventListener('click', function() {
            const container = document.getElementById('previous-positions-container');
            const newEntry = container.children[0].cloneNode(true);
            
            // Clear input values and selections
            newEntry.querySelectorAll('input').forEach(input => {
                if (input.type !== 'hidden') {
                    input.value = '';
                    input.classList.remove('invalid');
                } else {
                    input.value = '';
                }
            });
            newEntry.querySelectorAll('select').forEach(select => {
                select.selectedIndex = 0;
            });
            
            // Hide error messages
            newEntry.querySelectorAll('.error-text').forEach(error => {
                error.style.display = 'none';
            });
            
            // Make sure the delete button handler is attached
            attachDeleteHandler(newEntry.querySelector('.delete-position-button'));
            
            container.appendChild(newEntry);
        });

        // Remove Last Position
        document.getElementById('remove-previous-position').addEventListener('click', function() {
            const container = document.getElementById('previous-positions-container');
            if (container.children.length > 1) {
                container.removeChild(container.lastElementChild);
            }
        });
        
        // Function to attach delete handler to a button
        function attachDeleteHandler(button) {
            button.addEventListener('click', function() {
                const container = document.getElementById('previous-positions-container');
                const entry = this.closest('.previous-position-entry');
                
                // Only delete if there's more than one position entry
                if (container.children.length > 1) {
                    entry.remove();
                } else {
                    // If it's the last entry, just clear the fields
                    entry.querySelectorAll('input').forEach(input => {
                        if (input.type !== 'hidden') {
                            input.value = '';
                        } else {
                            input.value = '';
                        }
                    });
                    entry.querySelectorAll('select').forEach(select => {
                        select.selectedIndex = 0;
                    });
                }
            });
        }

        // Handle delete all checkbox
        document.getElementById('delete_all_prev_positions').addEventListener('change', function() {
            const container = document.getElementById('previous-positions-container');
            if (this.checked) {
                container.classList.add('disabled-positions');
                container.querySelectorAll('input, select').forEach(el => {
                    el.disabled = true;
                });
            } else {
                container.classList.remove('disabled-positions');
                container.querySelectorAll('input, select').forEach(el => {
                    el.disabled = false;
                });
            }
        });

        // Attach delete handlers to all existing delete buttons on page load
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.delete-position-button').forEach(button => {
                attachDeleteHandler(button);
            });
            
            // Initialize disabled state if delete all was checked before form submission
            const deleteAllCheckbox = document.getElementById('delete_all_prev_positions');
            if (deleteAllCheckbox.checked) {
                const container = document.getElementById('previous-positions-container');
                container.classList.add('disabled-positions');
                container.querySelectorAll('input, select').forEach(el => {
                    el.disabled = true;
                });
            }
        });

        // Validation functions
        function validateArabicLetter(e, input) {
            var charCode = (e.which) ? e.which : e.keyCode;
            // Allow Arabic letters, space, and backspace
            if (charCode == 32 || charCode == 8) return true;
            // Arabic Unicode range
            if (charCode >= 1536 && charCode <= 1791) return true;
            
            // Show error message
            showError(input, input.name + '_error');
            return false;
        }

        function validateLatinLetter(e, input) {
            var charCode = (e.which) ? e.which : e.keyCode;
            // Allow space and backspace
            if (charCode == 32 || charCode == 8) return true;
            // Latin letters (a-z, A-Z)
            if ((charCode >= 65 && charCode <= 90) || (charCode >= 97 && charCode <= 122)) return true;
            
            // Show error message
            showError(input, input.name + '_error');
            return false;
        }

        function validateNumber(e, input) {
            var charCode = (e.which) ? e.which : e.keyCode;
            // Allow numbers and backspace
            if (charCode == 8) return true;
            if (charCode >= 48 && charCode <= 57) return true;
            
            // Show error message
            showError(input, input.name + '_error');
            return false;
        }

        // Validate Arabic input on paste/change
        function validateArabicInput(input) {
            const arabicRegex = /^[\u0600-\u06FF\s]*$/;
            const errorElement = document.getElementById(input.name + '_error');
            
            if (!arabicRegex.test(input.value)) {
                showError(input, errorElement.id);
            } else {
                hideError(errorElement);
            }
        }

        // Validate Latin input on paste/change
        function validateLatinInput(input) {
            const latinRegex = /^[a-zA-Z\s]*$/;
            const errorElement = document.getElementById(input.name + '_error');
            
            if (!latinRegex.test(input.value)) {
                showError(input, errorElement.id);
            } else {
                hideError(errorElement);
            }
        }

        // Validate Number input on paste/change
        function validateNumberInput(input) {
            const numberRegex = /^\d*$/;
            const errorElement = document.getElementById(input.name + '_error');
            
            if (!numberRegex.test(input.value)) {
                showError(input, errorElement.id);
            } else {
                hideError(errorElement);
            }
        }

        function showError(input, errorId) {
            const errorElement = document.getElementById(errorId);
            if (errorElement) {
                errorElement.style.display = 'block';
                input.classList.add('invalid');
            }
        }

        function hideError(errorElement) {
            if (errorElement) {
                errorElement.style.display = 'none';
                errorElement.previousElementSibling.classList.remove('invalid');
            }
        }

        // Initialize validation for all fields on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Arabic name fields
            const arabicInputs = document.querySelectorAll('input[name="firstname_ar"], input[name="lastname_ar"], input[name="father_lastname"], input[name="mother_firstname"], input[name="mother_lastname"]');
            arabicInputs.forEach(input => {
                input.addEventListener('input', function() {
                    validateArabicInput(this);
                });
            });

            // Latin name fields
            const latinInputs = document.querySelectorAll('input[name="firstname_en"], input[name="lastname_en"]');
            latinInputs.forEach(input => {
                input.addEventListener('input', function() {
                    validateLatinInput(this);
                });
            });

            // Number fields
            const numberInputs = document.querySelectorAll('input[name="national_id"], input[name="ssn"], input[name="phone"], input[name="vacances_remain_days"]');
            numberInputs.forEach(input => {
                input.addEventListener('input', function() {
                    validateNumberInput(this);
                });
            });
        });
        function validateDateRange(startDateInput, endDateInput) {
    const startDate = new Date(startDateInput.value);
    const endDate = new Date(endDateInput.value);
    
    if (startDate && endDate && startDate > endDate) {
        endDateInput.setCustomValidity('تاريخ الانتهاء يجب أن يكون بعد تاريخ البدء');
        endDateInput.reportValidity();
        endDateInput.classList.add('invalid');
        return false;
    } else {
        endDateInput.setCustomValidity('');
        endDateInput.classList.remove('invalid');
        return true;
    }
}
    </script>
</body>
</html>