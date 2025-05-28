<?php
session_start();
require_once 'config/dbconnect.php';
$pdo = getDBConnection();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$departments = [];
try {
    $stmt = $pdo->query("SELECT * FROM departments ORDER BY created_at DESC");
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "خطأ في جلب البيانات: " . $e->getMessage();
}

$positions = [];
try {
    $stmt = $pdo->query("SELECT * FROM positions ORDER BY name ASC");
    $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "خطأ في جلب البيانات: " . $e->getMessage();
} 

$errors = [];
$success = '';
$previous_positions = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate main employee data
    $firstname_ar = trim($_POST['firstname_ar'] ?? '');
    $lastname_ar = trim($_POST['lastname_ar'] ?? '');
    $firstname_en = trim($_POST['firstname_en'] ?? '');
    $lastname_en = trim($_POST['lastname_en'] ?? '');
    $birth_date = trim($_POST['birth_date'] ?? '');
    $birth_place = trim($_POST['birth_place'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $bloodtype = trim($_POST['bloodtype'] ?? '');
    $vacances_remain_days = trim($_POST['vacances_remain_days'] ?? 0);
    $national_id = trim($_POST['national_id'] ?? '');
    $ssn = trim($_POST['ssn'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $department_id = trim($_POST['department'] ?? '');
    $hire_date = trim($_POST['hire_date'] ?? '');
    $first_hire_date = trim($_POST['first_hire_date'] ?? '');
    $is_high_level = isset($_POST['is_high_level']) ? 1 : 0;
    $high_level_position = $is_high_level ? trim($_POST['high_level_position'] ?? '') : null;
    $high_level_start_date = $is_high_level ? ($_POST['high_level_start_date'] ?? '') : null;

    // Validate required fields
    if (empty($firstname_ar)) $errors[] = 'اللقب العربي مطلوب';
    if (empty($lastname_ar)) $errors[] = 'الاسم العربي مطلوب';
    if (empty($firstname_en)) $errors[] = 'اللقب الفرنسي مطلوب';
    if (empty($lastname_en)) $errors[] = 'الاسم الفرنسي مطلوب';
    if (empty($birth_date)) $errors[] = 'تاريخ الميلاد مطلوب';
    if (empty($birth_place)) $errors[] = 'مكان الميلاد مطلوب';
    if (empty($national_id)) $errors[] = 'الرقم الوطني مطلوب';
    if (empty($phone)) $errors[] = 'رقم الهاتف مطلوب';
    if (empty($position)) $errors[] = 'المنصب مطلوب';
    if (empty($department_id)) $errors[] = 'القسم مطلوب';
    if (empty($hire_date)) $errors[] = 'تاريخ التعيين مطلوب';
    if (empty($first_hire_date)) $errors[] = 'تاريخ التنصيب مطلوب';

    if ($is_high_level) {
        if (empty($high_level_position)) {
            $errors[] = 'يجب اختيار المنصب العالي';
        }
        if (empty($high_level_start_date)) {
            $errors[] = 'تاريخ بدء المنصب العالي مطلوب';
        }
    }

    // Validate name fields contain only letters
    if (!preg_match('/^[\p{Arabic}\s]+$/u', $firstname_ar)) $errors[] = 'اللقب العربي يجب أن يحتوي على أحرف عربية فقط';
    if (!preg_match('/^[\p{Arabic}\s]+$/u', $lastname_ar)) $errors[] = 'الاسم العربي يجب أن يحتوي على أحرف عربية فقط';
    if (!preg_match('/^[a-zA-Z\s]+$/', $firstname_en)) $errors[] = 'اللقب الفرنسي يجب أن يحتوي على أحرف لاتينية فقط';
    if (!preg_match('/^[a-zA-Z\s]+$/', $lastname_en)) $errors[] = 'الاسم الفرنسي يجب أن يحتوي على أحرف لاتينية فقط';

    // Validate numeric fields contain only numbers
    if (!preg_match('/^\d+$/', $national_id)) $errors[] = 'الرقم الوطني يجب أن يحتوي على أرقام فقط';
    if (!preg_match('/^\d{10}$/', $ssn)) $errors[] = 'رقم الضمان الاجتماعي يجب أن يحتوي على 10 أرقام';
    if (!preg_match('/^\d+$/', $phone)) $errors[] = 'رقم الهاتف يجب أن يحتوي على أرقام فقط';
    if (!preg_match('/^\d+$/', $vacances_remain_days)) $errors[] = 'أيام الإجازة المتبقية يجب أن تحتوي على أرقام فقط';

    // Date validations
    $currentDate = new DateTime();
    $birthDate = new DateTime($birth_date);
    $hireDate = new DateTime($hire_date);
    $minBirthDate = (new DateTime())->modify('-18 years');
    
    // Check if employee is at least 18 years old
    if ($birthDate > $minBirthDate) {
        $errors[] = 'يجب أن يكون الموظف عمره 18 سنة على الأقل';
    }
    
    // Check hire date is after birth date
    if ($hireDate < $birthDate) {
        $errors[] = 'تاريخ التعيين يجب أن يكون بعد تاريخ الميلاد';
    }
    
    // Validate previous positions dates
    $prev_positions = $_POST['prev_positions'] ?? [];
    $prev_start_dates = $_POST['prev_start_dates'] ?? [];
    $prev_end_dates = $_POST['prev_end_dates'] ?? [];
    
    foreach ($prev_positions as $index => $prev_position) {
        if (!empty($prev_position)) {
            $start_date = $prev_start_dates[$index] ?? '';
            $end_date = $prev_end_dates[$index] ?? '';
            
            if (empty($start_date) || empty($end_date)) {
                $errors[] = 'يجب ملء تاريخي البدء والانتهاء للوظيفة السابقة';
                continue;
            }
            
            $startDate = new DateTime($start_date);
            $endDate = new DateTime($end_date);
            
            if ($startDate > $endDate) {
                $errors[] = 'تاريخ البدء للوظيفة السابقة يجب أن يكون قبل تاريخ الانتهاء';
            }
            
            if ($endDate > $hireDate) {
                $errors[] = 'تاريخ انتهاء الوظيفة السابقة يجب أن يكون قبل تاريخ التعيين الحالي';
            }
        }
    }
    
    // Validate high level position dates if applicable
    if ($is_high_level && $high_level_start_date) {
        $highLevelStartDate = new DateTime($high_level_start_date);
        
        if ($highLevelStartDate <= $hireDate) {
            $errors[] = 'تاريخ بدء المنصب العالي يجب أن يكون بعد تاريخ التعيين';
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Insert main employee data
            $stmt = $pdo->prepare("INSERT INTO employees 
                (firstname_ar, lastname_ar, firstname_en, lastname_en, birth_date, birth_place, gender, bloodtype, vacances_remain_days, 
                national_id, ssn, email, phone, address, position, department_id, hire_date, first_hire_date) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute([
                $firstname_ar, $lastname_ar, $firstname_en, $lastname_en, $birth_date, $birth_place, $gender, $bloodtype, $vacances_remain_days,
                $national_id, $ssn, $email ?: null, $phone, $address ?: null, 
                $position,
                $department_id, 
                $hire_date,
                $first_hire_date // Set first_hire_date same as hire_date
            ]);

            $employee_id = $pdo->lastInsertId();

            // Rest of the code remains the same...
            // Insert previous positions
            if (!empty($prev_positions)) {
                $prev_stmt = $pdo->prepare("INSERT INTO employee_previous_positions 
                    (employee_id, position, start_date, end_date) 
                    VALUES (?, ?, ?, ?)");

                foreach ($prev_positions as $index => $prev_position) {
                    if (!empty($prev_position) && !empty($prev_start_dates[$index]) && !empty($prev_end_dates[$index])) {
                        $prev_stmt->execute([
                            $employee_id, 
                            $prev_position, 
                            $prev_start_dates[$index], 
                            $prev_end_dates[$index]
                        ]);
                    }
                }
            }
            
            if ($is_high_level && $high_level_position && $high_level_start_date) {
                $stmt = $pdo->prepare("INSERT INTO employee_high_level_history 
                                    (employee_id, position_id, start_date) 
                                    VALUES (?, ?, ?)");
                $stmt->execute([$employee_id, $high_level_position, $high_level_start_date]);
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
    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="CSS/icons.css">
    <style>
        .error-message { color: #dc3545; padding: 10px; margin: 10px 0; border: 1px solid #f5c6cb; border-radius: 5px; }
        .success-message { color: #28a745; padding: 10px; margin: 10px 0; border: 1px solid #c3e6cb; border-radius: 5px; }
        .required { color: red; }
        .form-section { margin-bottom: 2rem; padding: 1.5rem; background: #f8f9fa; border-radius: 8px; }
        .input-group { margin-bottom: 1rem; position: relative; }
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
        .date-error {
            color: #dc3545;
            font-size: 0.8rem;
            margin-top: 0.25rem;
        }
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

            <form class="employee-form" method="POST" id="employeeForm">
                <!-- Personal Information Section -->
                <div class="form-section">
                    <h2><i class="fas fa-user"></i> المعلومات الشخصية</h2>
                    <div class="input-group">
                        <label>اللقب<span class="required">*</span></label>
                        <input type="text" name="firstname_ar" value="<?= htmlspecialchars($_POST['firstname_ar'] ?? '') ?>" 
                               onkeypress="return validateArabicLetter(event, this)" 
                               oninput="validateArabicInput(this)" required>
                        <div class="error-text" id="firstname_ar_error">اللقب العربي يجب أن يحتوي على أحرف عربية فقط</div>
                    </div>
                    <div class="input-group">
                        <label>الاسم<span class="required">*</span></label>
                        <input type="text" name="lastname_ar" value="<?= htmlspecialchars($_POST['lastname_ar'] ?? '') ?>" 
                               onkeypress="return validateArabicLetter(event, this)" 
                               oninput="validateArabicInput(this)" required>
                        <div class="error-text" id="lastname_ar_error">الاسم العربي يجب أن يحتوي على أحرف عربية فقط</div>
                    </div>

                    <div class="input-group">
                        <label>Nom<span class="required">*</span></label>
                        <input type="text" name="firstname_en" value="<?= htmlspecialchars($_POST['firstname_en'] ?? '') ?>" 
                               onkeypress="return validateLatinLetter(event, this)" 
                               oninput="validateLatinInput(this)" required>
                        <div class="error-text" id="firstname_en_error">يجب أن يحتوي على أحرف لاتينية فقط</div>
                    </div>
                    <div class="input-group">
                        <label>Prenom<span class="required">*</span></label>
                        <input type="text" name="lastname_en" value="<?= htmlspecialchars($_POST['lastname_en'] ?? '') ?>" 
                               onkeypress="return validateLatinLetter(event, this)" 
                               oninput="validateLatinInput(this)" required>
                        <div class="error-text" id="lastname_en_error">يجب أن يحتوي على أحرف لاتينية فقط</div>
                    </div>

                    <div class="input-row">
                        <div class="input-group">
                            <label>تاريخ الميلاد <span class="required">*</span></label>
                            <input type="date" name="birth_date" id="birth_date"
                                   value="<?= htmlspecialchars($_POST['birth_date'] ?? '') ?>" 
                                   max="<?= date('Y-m-d', strtotime('-18 years')) ?>" required>
                            <div class="date-error" id="birth_date_error"></div>
                        </div>
                        
                        <div class="input-group">
                            <label>مكان الميلاد <span class="required">*</span></label>
                            <input type="text" name="birth_place" value="<?= htmlspecialchars($_POST['birth_place'] ?? '') ?>" onkeypress="return validateArabicLetter(event, this)" 
                               oninput="validateArabicInput(this)" required>
                            <div class="error-text" id="birth_place_error"> مكان الميلاد يجب أن يحتوي على أحرف عربية فقط</div>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>الرقم الوطني <span class="required">*</span></label>
                        <input type="text" name="national_id" 
                               value="<?= htmlspecialchars($_POST['national_id'] ?? '') ?>" 
                               onkeypress="return validateNumber(event, this)"
                               oninput="validateNumberInput(this)" required>
                        <div class="error-text" id="national_id_error">يجب أن يحتوي على أرقام فقط</div>
                    </div>

                    <div class="input-group">
                        <label>رقم الضمان الاجتماعي<span class="required">*</span></label>
                        <input type="text" name="ssn" 
                               value="<?= htmlspecialchars($_POST['ssn'] ?? '') ?>" 
                               onkeypress="return validateNumber(event, this)"
                               oninput="validateNumberInput(this)" 
                               minlength="10" maxlength="10" required>
                        <div class="error-text" id="ssn_error">يجب أن يحتوي على 10 أرقام فقط</div>
                    </div>

                    <div class="input-row">
                        <div class="input-group">
                            <label>الجنس <span class="required">*</span></label>
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
                    <div class="input-group">
                        <label>الأيام المتبقية للإجازة</label>
                        <input type="text" name="vacances_remain_days" 
                               value="<?= htmlspecialchars($_POST['vacances_remain_days'] ?? 0) ?>"
                               onkeypress="return validateNumber(event, this)"
                               oninput="validateNumberInput(this)">
                        <div class="error-text" id="vacances_remain_days_error">يجب أن يحتوي على أرقام فقط</div>
                    </div>
                </div>
                
                <!-- Job Information Section -->
                <div class="form-section">
                    <h2><i class="fas fa-address-card"></i> المعلومات الوظيفية</h2>
                    <div class="input-row">
                        <div class="input-group">
                            <label>منصب الشغل <span class="required">*</span></label>
                            <select name="position" required>
                                <option value="">اختر منصب الشغل...</option>
                                <?php foreach ($positions as $pos): ?>
                                    <option value="<?= $pos['name'] ?>" <?= ($_POST['position'] ?? '') == $pos['name'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($pos['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="input-group">
                            <label>تاريخ التعيين <span class="required">*</span></label>
                            <input type="date" name="hire_date" id="hire_date"
                                   value="<?= htmlspecialchars($_POST['hire_date'] ?? '') ?>" required>
                            <div class="date-error" id="hire_date_error"></div>
                        </div>
                        <div class="input-group">
                            <label>تاريخ التنصيب <span class="required">*</span></label>
                            <input type="date" name="first_hire_date" id="first_hire_date"
                                   value="<?= htmlspecialchars($_POST['first_hire_date'] ?? '') ?>" required>
                            <div class="date-error" id="first_hire_date_error"></div>
                        </div>
                    </div>
                    
                    <div class="input-row">
                        <div class="input-group">
                            <label>المصلحة <span class="required">*</span></label>
                            <select name="department" required>
                                <option value="0">اختر مصلحة</option>
                                <?php if ($departments): ?>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?= $dept['department_id'] ?>" <?= ($_POST['department'] ?? '') == $dept['department_id'] ? 'selected' : '' ?>><?= htmlspecialchars($dept['name']) ?></option>
                                    <?php endforeach; ?>
                                <?php endif;?>
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
                                    <label>مناصب الشغل السابقة</label>
                                    <select name="prev_positions[]">
                                        <option value="">اختر منصب الشغل السابق</option>
                                        <?php foreach ($positions as $pos): ?>
                                            <option value="<?= $pos['name'] ?>" <?= ($_POST['prev_positions'][0] ?? '') == $pos['name'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($pos['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="input-group">
                                    <label>تاريخ البدء</label>
                                    <input type="date" name="prev_start_dates[]" class="prev-start-date"
                                           value="<?= htmlspecialchars($_POST['prev_start_dates'][0] ?? '') ?>">
                                </div>
                                <div class="input-group">
                                    <label>تاريخ الانتهاء</label>
                                    <input type="date" name="prev_end_dates[]" class="prev-end-date"
                                           value="<?= htmlspecialchars($_POST['prev_end_dates'][0] ?? '') ?>">
                                    <div class="date-error prev-date-error" style="display: none;">تاريخ البدء يجب أن يكون قبل تاريخ الانتهاء</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="actions">
                        <button type="button" id="add-previous-position" class="login-button" style="width:auto;">
                            <i class="fas fa-plus"></i> إضافة وظيفة سابقة
                        </button>
                        <button type="button" id="remove-previous-position" class="delete-button">
                            <i class="fas fa-minus"></i> حذف وظيفة سابقة
                        </button>
                    </div>
                </div>
                
                <!-- High Level Position Section -->
                <div class="form-section">
                    <h2><i class="fas fa-star"></i> المناصب العليا</h2>
                    
                    <div class="input-group">
                        <label>هل يشغل منصب عالي حالياً؟</label>
                        <input type="checkbox" id="is_high_level" name="is_high_level" value="1" style="width: auto !important;"
                            <?= isset($_POST['is_high_level']) ? 'checked' : '' ?>>
                        <label for="is_high_level" style="display: inline; margin-right: 10px;">نعم</label>
                    </div>

                    <div id="high_level_fields" style="<?= isset($_POST['is_high_level']) ? '' : 'display: none;' ?>">
                        <div class="input-group">
                            <label>اختر المنصب العالي <span class="required">*</span></label>
                            <select name="high_level_position" <?= isset($_POST['is_high_level']) ? 'required' : '' ?>>
                                <option value="">اختر المنصب العالي...</option>
                                <?php 
                                $high_positions = $pdo->query("SELECT * FROM high_level_positions ORDER BY name ASC")->fetchAll();
                                foreach ($high_positions as $pos): ?>
                                    <option value="<?= $pos['position_id'] ?>" 
                                        <?= ($_POST['high_level_position'] ?? '') == $pos['position_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($pos['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="input-group">
                            <label>تاريخ بدء المنصب العالي <span class="required">*</span></label>
                            <input type="date" name="high_level_start_date" id="high_level_start_date"
                                value="<?= htmlspecialchars($_POST['high_level_start_date'] ?? '') ?>" 
                                <?= isset($_POST['is_high_level']) ? 'required' : '' ?>>
                            <div class="date-error" id="high_level_start_date_error"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Contact Information Section -->
                <div class="form-section">
                    <h2><i class="fas fa-phone"></i> معلومات الاتصال</h2>
                    <div class="input-row">
                        <div class="input-group">
                            <label>رقم الهاتف <span class="required">*</span></label>
                            <input type="text" name="phone" 
                                   value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" 
                                   onkeypress="return validateNumber(event, this)"
                                   oninput="validateNumberInput(this)" required>
                            <div class="error-text" id="phone_error">يجب أن يحتوي على أرقام فقط</div>
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
            newEntry.querySelectorAll('select').forEach(select => select.selectedIndex = 0);
            
            container.appendChild(newEntry);
        });

        // Remove Last Position
        document.getElementById('remove-previous-position').addEventListener('click', function() {
            const container = document.getElementById('previous-positions-container');
            if (container.children.length > 1) {
                container.removeChild(container.lastElementChild);
            }
        });

        // Toggle high level position fields
        document.getElementById('is_high_level').addEventListener('change', function() {
            const fields = document.getElementById('high_level_fields');
            fields.style.display = this.checked ? 'block' : 'none';
            
            // Toggle required attribute
            fields.querySelectorAll('[required]').forEach(el => {
                el.required = this.checked;
            });
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

        // Date validation functions
        function validateDates() {
            const birthDate = new Date(document.getElementById('birth_date').value);
            const hireDate = new Date(document.getElementById('hire_date').value);
            const highLevelStartDate = document.getElementById('high_level_start_date')?.value ? 
                                     new Date(document.getElementById('high_level_start_date').value) : null;
            
            const birthDateError = document.getElementById('birth_date_error');
            const hireDateError = document.getElementById('hire_date_error');
            const highLevelStartDateError = document.getElementById('high_level_start_date_error');
            
            let isValid = true;
            
            // Validate birth date (must be at least 18 years ago)
            if (birthDate) {
                const minBirthDate = new Date();
                minBirthDate.setFullYear(minBirthDate.getFullYear() - 18);
                
                if (birthDate > minBirthDate) {
                    birthDateError.textContent = 'يجب أن يكون الموظف عمره 18 سنة على الأقل';
                    birthDateError.style.display = 'block';
                    isValid = false;
                } else {
                    birthDateError.style.display = 'none';
                }
            }
            
            // Validate hire date is after birth date
            if (birthDate && hireDate && hireDate < birthDate) {
                hireDateError.textContent = 'تاريخ التعيين يجب أن يكون بعد تاريخ الميلاد';
                hireDateError.style.display = 'block';
                isValid = false;
            } else {
                hireDateError.style.display = 'none';
            }
            
            // Validate high level start date is after hire date
            if (highLevelStartDate && hireDate && highLevelStartDate < hireDate) {
                highLevelStartDateError.textContent = 'تاريخ بدء المنصب العالي يجب أن يكون بعد تاريخ التعيين';
                highLevelStartDateError.style.display = 'block';
                isValid = false;
            } else if (highLevelStartDateError) {
                highLevelStartDateError.style.display = 'none';
            }
            
            // Validate previous positions dates
            const prevStartDates = document.querySelectorAll('.prev-start-date');
            const prevEndDates = document.querySelectorAll('.prev-end-date');
            const prevDateErrors = document.querySelectorAll('.prev-date-error');
            
            prevStartDates.forEach((startDateInput, index) => {
                const startDate = new Date(startDateInput.value);
                const endDate = new Date(prevEndDates[index].value);
                
                if (startDate && endDate && startDate > endDate) {
                    prevDateErrors[index].style.display = 'block';
                    isValid = false;
                } else {
                    prevDateErrors[index].style.display = 'none';
                }
                
                // Validate previous positions dates are before hire date
                if (hireDate && endDate && endDate > hireDate) {
                    prevDateErrors[index].textContent = 'تاريخ انتهاء الوظيفة السابقة يجب أن يكون قبل تاريخ التعيين';
                    prevDateErrors[index].style.display = 'block';
                    isValid = false;
                } else if (startDate && endDate && startDate > endDate) {
                    prevDateErrors[index].textContent = 'تاريخ البدء يجب أن يكون قبل تاريخ الانتهاء';
                    prevDateErrors[index].style.display = 'block';
                    isValid = false;
                }
            });
            
            return isValid;
        }
        
        // Form submission validation
        document.getElementById('employeeForm').addEventListener('submit', function(e) {
            if (!validateDates()) {
                e.preventDefault();
            }
        });

        // Initialize validation for all fields on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Arabic name fields
            const arabicInputs = document.querySelectorAll('input[name="firstname_ar"], input[name="lastname_ar"]');
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
            
            // Date fields change listeners
            const dateFields = ['birth_date', 'hire_date', 'high_level_start_date'];
            dateFields.forEach(field => {
                const el = document.getElementById(field);
                if (el) {
                    el.addEventListener('change', validateDates);
                }
            });
            
            // Previous positions date fields
            document.addEventListener('change', function(e) {
                if (e.target.classList.contains('prev-start-date') || e.target.classList.contains('prev-end-date')) {
                    validateDates();
                }
            });
        });
    </script>
</body>
</html>