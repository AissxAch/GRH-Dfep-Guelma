<?php
session_start();
require_once 'config/dbconnect.php';
$pdo= getDBConnection();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
// Date validation and conversion
function convertDate($date) {
    if (empty($date)) return null;
    
    // Handle both formats if needed
    $dateObj = DateTime::createFromFormat('d/m/Y', $date);
    if (!$dateObj) {
        $dateObj = DateTime::createFromFormat('Y-m-d', $date);
    }
    
    return $dateObj ? $dateObj->format('Y-m-d') : null;
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
    $is_high_level = isset($_POST['is_high_level']) ? 1 : 0;
    $high_level_position = $is_high_level ? trim($_POST['high_level_position'] ?? '') : null;
    $high_level_start_date = $is_high_level ? convertDate($_POST['high_level_start_date'] ?? '') : null;

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
    if ($is_high_level) {
        if (empty($high_level_position)) {
            $errors[] = 'يجب اختيار المنصب العالي';
        }
        if (!$high_level_start_date) {
            $errors[] = 'صيغة تاريخ بدء المنصب العالي غير صحيحة (dd/mm/yyyy)';
        }
    }


    $birth_date_db = convertDate($birth_date);
    $hire_date_db = convertDate($hire_date);

    if (!$birth_date_db) $errors[] = 'صيغة تاريخ الميلاد غير صحيحة (dd/mm/yyyy)';
    if (!$hire_date_db) $errors[] = 'صيغة تاريخ التعيين غير صحيحة (dd/mm/yyyy)';

    // Validate previous positions
    $prev_positions = $_POST['prev_positions'] ?? [];
    $prev_start_dates = $_POST['prev_start_dates'] ?? [];
    $prev_end_dates = $_POST['prev_end_dates'] ?? [];

    foreach ($prev_positions as $index => $prev_position) {
        if (!empty($prev_position)) {
            $start = $prev_start_dates[$index] ?? '';
            $end = $prev_end_dates[$index] ?? '';
    
            if (empty($start) || empty($end)) {
                $errors[] = 'يرجى إدخال تواريخ البدء والانتهاء لكل وظيفة سابقة';
            } else {
                $start_db = convertDate($start);
                $end_db = convertDate($end);
                
                if (!$start_db || !$end_db) {
                    $errors[] = 'صيغة التاريخ غير صحيحة (يجب أن تكون dd/mm/yyyy)';
                }
            }
    
            $previous_positions[] = [
                'position' => $prev_position,
                'start_date' => $start_db,
                'end_date' => $end_db
            ];
        }
    }

    foreach ($previous_positions as $prev_pos) {
        if (!$prev_pos['start_date'] || !$prev_pos['end_date']) {
            $errors[] = 'يوجد خطأ في تواريخ الوظائف السابقة';
            break;
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
    
            // Insert main employee data
            $stmt = $pdo->prepare("INSERT INTO employees 
                (firstname_ar, lastname_ar, firstname_en, lastname_en, birth_date, birth_place, gender, bloodtype, vacances_remain_days, 
                national_id, ssn, email, phone, address, position, department_id, hire_date) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute([
                $firstname_ar, $lastname_ar, $firstname_en, $lastname_en, $birth_date_db, $birth_place, $gender, $bloodtype, $vacances_remain_days,
                $national_id, $ssn, $email ?: null, $phone, $address ?: null, 
                $position,
                $department_id, 
                $hire_date_db
            ]);
    
            $employee_id = $pdo->lastInsertId(); // Correct employee ID
    
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
            
            // Remove this line - it's incorrect:
            // $employee_id = $pdo->lastInsertId();
            
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
                        <label>اللقب<span class="required">*</span></label>
                        <input type="text" name="firstname_ar" value="<?= htmlspecialchars($_POST['firstname_ar'] ?? '') ?>" required>
                    </div>
                    <div class="input-group">
                        <label>الاسم<span class="required">*</span></label>
                        <input type="text" name="lastname_ar" value="<?= htmlspecialchars($_POST['lastname_ar'] ?? '') ?>" required>
                    </div>
                    
                    <div class="input-group">
                        <label>Nom<span class="required">*</span></label>
                        <input type="text" name="firstname_en" value="<?= htmlspecialchars($_POST['firstname_en'] ?? '') ?>" required>
                    </div>
                    <div class="input-group">
                        <label>Prenom<span class="required">*</span></label>
                        <input type="text" name="lastname_en" value="<?= htmlspecialchars($_POST['lastname_en'] ?? '') ?>" required>
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

                    <div class="input-group">
                            <label>الرقم الوطني <span class="required">*</span></label>
                            <input type="text" name="national_id" 
                                   value="<?= htmlspecialchars($_POST['national_id'] ?? '') ?>" required>
                    </div>

                    <div class="input-group">
                            <label>رقم الضمان الاجتماعي<span class="required">*</span></label>
                            <input type="text" name="ssn" 
                                   value="<?= htmlspecialchars($_POST['ssn'] ?? '') ?>" required>
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
                        <input type="number" name="vacances_remain_days" value="<?= htmlspecialchars($_POST['vacances_remain_days'] ?? 0) ?>">
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
                            <input type="text" name="hire_date" placeholder="dd/mm/yyyy" 
                            value="<?= htmlspecialchars($_POST['hire_date'] ?? '') ?>" pattern="\d{2}/\d{2}/\d{4}" required>
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
                                            <input type="text" name="prev_start_dates[]" placeholder="dd/mm/yyyy" 
                                                value="<?= htmlspecialchars($_POST['prev_start_dates'][0] ?? '') ?>" pattern="\d{2}/\d{2}/\d{4}">
                                        </div>
                                        <div class="input-group">
                                            <label>تاريخ الانتهاء</label>
                                            <input type="text" name="prev_end_dates[]" placeholder="dd/mm/yyyy" 
                                                value="<?= htmlspecialchars($_POST['prev_end_dates'][0] ?? '') ?>" pattern="\d{2}/\d{2}/\d{4}">
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
                            <input type="text" name="high_level_start_date" placeholder="dd/mm/yyyy" 
                                value="<?= htmlspecialchars($_POST['high_level_start_date'] ?? '') ?>" 
                                pattern="\d{2}/\d{2}/\d{4}" 
                                <?= isset($_POST['is_high_level']) ? 'required' : '' ?>>
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
        // Toggle high level position fields
        document.getElementById('is_high_level').addEventListener('change', function() {
            const fields = document.getElementById('high_level_fields');
            fields.style.display = this.checked ? 'block' : 'none';
            
            // Toggle required attribute
            fields.querySelectorAll('[required]').forEach(el => {
                el.required = this.checked;
            });
        });
    </script>
</body>
</html>