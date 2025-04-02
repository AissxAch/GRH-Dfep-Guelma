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
    $position = trim($_POST['position'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $hire_date = trim($_POST['hire_date'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Validate required fields
    if (empty($full_name_ar)) $errors[] = 'الاسم العربي مطلوب';
    if (empty($full_name_en)) $errors[] = 'الاسم الإنجليزي مطلوب';
    if (empty($birth_date)) $errors[] = 'تاريخ الميلاد مطلوب';
    if (empty($birth_place)) $errors[] = 'مكان الميلاد مطلوب';
    if (empty($national_id)) $errors[] = 'الرقم الوطني مطلوب';
    if (empty($phone)) $errors[] = 'رقم الهاتف مطلوب';
    if (empty($position)) $errors[] = 'المنصب مطلوب';
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

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Update main employee data
            $stmt = $pdo->prepare("UPDATE employees SET 
                full_name_ar = ?, full_name_en = ?, birth_date = ?, birth_place = ?, 
                gender = ?, bloodtype = ?, national_id = ?, email = ?, phone = ?, 
                address = ?, position = ?, department = ?, hire_date = ?, is_active = ?,
                updated_at = CURRENT_TIMESTAMP 
                WHERE employee_id = ?");
            
            $stmt->execute([
                $full_name_ar, $full_name_en, $birth_date_db, $birth_place, $gender, 
                $bloodtype, $national_id, $email ?: null, $phone, $address ?: null, 
                $position, $department, $hire_date_db, $is_active, $employee_id
            ]);

            // Handle previous positions
            $prev_positions = $_POST['prev_positions'] ?? [];
            $prev_start_dates = $_POST['prev_start_dates'] ?? [];
            $prev_end_dates = $_POST['prev_end_dates'] ?? [];
            $prev_ids = $_POST['prev_ids'] ?? [];

            // First delete all existing positions not in the submitted list
            $delete_stmt = $pdo->prepare("DELETE FROM employee_previous_positions 
                                        WHERE employee_id = ? AND id NOT IN (" . 
                                        implode(',', array_filter($prev_ids, 'is_numeric')) . ")");
            $delete_stmt->execute([$employee_id]);

            // Update or insert positions
            $position_stmt = $pdo->prepare("INSERT INTO employee_previous_positions 
                (id, employee_id, position, start_date, end_date) 
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                position = VALUES(position), 
                start_date = VALUES(start_date), 
                end_date = VALUES(end_date)");

            foreach ($prev_positions as $index => $prev_position) {
                if (!empty($prev_position)) {
                    $position_stmt->execute([
                        $prev_ids[$index] ?: null,
                        $employee_id,
                        $prev_position,
                        $prev_start_dates[$index],
                        $prev_end_dates[$index]
                    ]);
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
    <link rel="stylesheet" href="CSS/icons.css">
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
            
            <?php if (($success)): ?>
                <div class="success-message"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <?php if (!empty($employee)): ?>
            <form class="employee-form" method="POST">
                <input type="hidden" name="employee_id" value="<?= $employee['employee_id'] ?>">
                
                <!-- Personal Information Section -->
                <div class="form-section">
                    <h2><i class="fas fa-user"></i> المعلومات الشخصية</h2>
                    <div class="input-group">
                        <label>الاسم الكامل (عربي) <span class="required">*</span></label>
                        <input type="text" name="full_name_ar" value="<?= htmlspecialchars($employee['full_name_ar']) ?>" required>
                    </div>
                    
                    <div class="input-group">
                        <label>الاسم الكامل (إنجليزي) <span class="required">*</span></label>
                        <input type="text" name="full_name_en" value="<?= htmlspecialchars($employee['full_name_en']) ?>" required>
                    </div>

                    <div class="input-row">
                        <div class="input-group">
                            <label>تاريخ الميلاد <span class="required">*</span></label>
                            <input type="text" name="birth_date" placeholder="dd/mm/yyyy" 
                                   value="<?= date('d/m/Y', strtotime($employee['birth_date'])) ?>" pattern="\d{2}/\d{2}/\d{4}" required>
                        </div>
                        
                        <div class="input-group">
                            <label>مكان الميلاد <span class="required">*</span></label>
                            <input type="text" name="birth_place" value="<?= htmlspecialchars($employee['birth_place']) ?>" required>
                        </div>
                    </div>

                    <div class="input-row">
                        <div class="input-group">
                            <label>النوع <span class="required">*</span></label>
                            <select name="gender" required>
                                <option value="male" <?= $employee['gender'] === 'male' ? 'selected' : '' ?>>ذكر</option>
                                <option value="female" <?= $employee['gender'] === 'female' ? 'selected' : '' ?>>أنثى</option>
                            </select>
                        </div>
                        
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
                    </div>
                </div>

                <!-- Previous Positions Section -->
                <div class="form-section">
                    <h2><i class="fas fa-history"></i> الوظائف السابقة</h2>
                    <div id="previous-positions-container">
                        <?php if (empty($previous_positions)): ?>
                            <div class="previous-position-entry">
                                <div class="input-row">
                                    <div class="input-group">
                                        <label>الوظيفة السابقة</label>
                                        <input type="text" name="prev_positions[]" placeholder="اسم الوظيفة السابقة">
                                        <input type="hidden" name="prev_ids[]" value="">
                                    </div>
                                    <div class="input-group">
                                        <label>تاريخ البدء</label>
                                        <input type="date" name="prev_start_dates[]">
                                    </div>
                                    <div class="input-group">
                                        <label>تاريخ الانتهاء</label>
                                        <input type="date" name="prev_end_dates[]">
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($previous_positions as $pos): ?>
                                <div class="previous-position-entry">
                                    <div class="input-row">
                                        <div class="input-group">
                                            <label>الوظيفة السابقة</label>
                                            <input type="text" name="prev_positions[]" 
                                                   value="<?= htmlspecialchars($pos['position']) ?>"
                                                   placeholder="اسم الوظيفة السابقة">
                                            <input type="hidden" name="prev_ids[]" value="<?= $pos['id'] ?>">
                                        </div>
                                        <div class="input-group">
                                            <label>تاريخ البدء</label>
                                            <input type="date" name="prev_start_dates[]" 
                                                   value="<?= $pos['start_date'] ?>">
                                        </div>
                                        <div class="input-group">
                                            <label>تاريخ الانتهاء</label>
                                            <input type="date" name="prev_end_dates[]" 
                                                   value="<?= $pos['end_date'] ?>">
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
                                   value="<?= htmlspecialchars($employee['national_id']) ?>" required>
                        </div>
                        
                        <div class="input-group">
                            <label>تاريخ التعيين <span class="required">*</span></label>
                            <input type="text" name="hire_date" placeholder="dd/mm/yyyy" 
                                   value="<?= date('d/m/Y', strtotime($employee['hire_date'])) ?>" pattern="\d{2}/\d{2}/\d{4}" required>
                        </div>
                    </div>

                    <div class="input-row">
                        <div class="input-group">
                            <label>القسم <span class="required">*</span></label>
                            <select name="department" required>
                                <option value="الإدارة" <?= $employee['department'] === 'الإدارة' ? 'selected' : '' ?>>الإدارة</option>
                                <option value="الموارد البشرية" <?= $employee['department'] === 'الموارد البشرية' ? 'selected' : '' ?>>الموارد البشرية</option>
                                <option value="المبيعات" <?= $employee['department'] === 'المبيعات' ? 'selected' : '' ?>>المبيعات</option>
                                <option value="التسويق" <?= $employee['department'] === 'التسويق' ? 'selected' : '' ?>>التسويق</option>
                                <option value="التقنية" <?= $employee['department'] === 'التقنية' ? 'selected' : '' ?>>التقنية</option>
                            </select>
                        </div>
                        
                        <div class="input-group">
                            <label>المنصب <span class="required">*</span></label>
                            <input type="text" name="position" 
                                value="<?= htmlspecialchars($employee['position']) ?>" required>
                        </div>
                    </div>
                    
                    <div class="input-group">
                        <label>
                            <input type="checkbox" name="is_active" <?= $employee['is_active'] ? 'checked' : '' ?>>
                            الحساب نشط
                        </label>
                    </div>
                </div>

                <!-- Contact Information Section -->
                <div class="form-section">
                    <h2><i class="fas fa-phone"></i> معلومات الاتصال</h2>
                    <div class="input-row">
                        <div class="input-group">
                            <label>رقم الهاتف <span class="required">*</span></label>
                            <input type="tel" name="phone" 
                                   value="<?= htmlspecialchars($employee['phone']) ?>" required>
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
                    <a href="list_employees.php" class="back-button">
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
            
            // Clear input values
            newEntry.querySelectorAll('input').forEach(input => {
                if (input.type !== 'hidden') {
                    input.value = '';
                }
            });
            
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