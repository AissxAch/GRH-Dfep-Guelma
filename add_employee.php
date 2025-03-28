<?php
session_start();
require 'config/dbconnect.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Initialize variables
$error = '';
$success = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Sanitize inputs
        $full_name_ar = filter_input(INPUT_POST, 'full_name_ar', FILTER_SANITIZE_STRING);
        $full_name_en = filter_input(INPUT_POST, 'full_name_en', FILTER_SANITIZE_STRING);
        $birth_date = $_POST['birth_date'];
        $birth_place = filter_input(INPUT_POST, 'birth_place', FILTER_SANITIZE_STRING);
        $gender = $_POST['gender'];
        $bloodtype = $_POST['bloodtype'];
        $national_id = filter_input(INPUT_POST, 'national_id', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
        $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING);
        $position = filter_input(INPUT_POST, 'position', FILTER_SANITIZE_STRING);
        $department = $_POST['department'];
        $hire_date = $_POST['hire_date'];

        // Validate required fields
        if (empty($full_name_ar) || empty($national_id) || empty($phone)) {
            throw new Exception("الرجاء ملء جميع الحقول الإلزامية");
        }

        // Insert into database
        $sql = "INSERT INTO employees (
            full_name_ar, full_name_en, birth_date, birth_place, gender, 
            bloodtype, national_id, email, phone, address, 
            position, department, hire_date
        ) VALUES (
            :full_name_ar, :full_name_en, :birth_date, :birth_place, :gender, 
            :bloodtype, :national_id, :email, :phone, :address, 
            :position, :department, :hire_date
        )";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':full_name_ar' => $full_name_ar,
            ':full_name_en' => $full_name_en,
            ':birth_date' => $birth_date,
            ':birth_place' => $birth_place,
            ':gender' => $gender,
            ':bloodtype' => $bloodtype,
            ':national_id' => $national_id,
            ':email' => $email,
            ':phone' => $phone,
            ':address' => $address,
            ':position' => $position,
            ':department' => $department,
            ':hire_date' => $hire_date
        ]);

        $success = "تمت إضافة الموظف بنجاح!";
    } catch (PDOException $e) {
        $error = "خطأ في قاعدة البيانات: " . $e->getMessage();
    } catch (Exception $e) {
        $error = $e->getMessage();
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
</head>
<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <!-- Use the same header from index.php -->
            <?php include 'header.php'; ?>
        </header>

        <main class="dashboard-main">
            <h1 class="dashboard-title">إضافة موظف جديد</h1>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>

            <form class="employee-form" method="POST">
                <div class="form-section">
                    <h2><i class="fas fa-user"></i> المعلومات الشخصية</h2>
                    <div class="input-group">
                        <label>الاسم الكامل (عربي) <span class="required">*</span></label>
                        <input type="text" name="full_name_ar" required>
                    </div>
                    
                    <div class="input-group">
                        <label>الاسم الكامل (إنجليزي)<span class="required">*</span></label>
                        <input type="text" name="full_name_en" required>
                    </div>

                    <div class="input-row">
                        <div class="input-group">
                            <label>تاريخ الميلاد <span class="required">*</span></label>
                            <input type="text" name="birth_date" placeholder="dd/mm/yyyy" pattern="\d{2}/\d{2}/\d{4}" required>
                        </div>
                        
                        <div class="input-group">
                            <label>مكان الميلاد<span class="required">*</span></label>
                            <input type="text" name="birth_place" required>
                        </div>
                    </div>

                    <div class="input-row">
                        <div class="input-group">
                            <label>النوع <span class="required">*</span></label>
                            <select name="gender" required>
                                <option value="male">ذكر</option>
                                <option value="female">أنثى</option>
                            </select>
                        </div>
                        
                        <div class="input-group">
                            <label>فصيلة الدم<span class="required">*</span></label>
                            <select name="bloodtype" required>
                                <option value="">اختر...</option>
                                <option value="O+">O+</option>
                                <option value="A+">A+</option>
                                <option value="B+">B+</option>
                                <option value="AB+">AB+</option>
                                <option value="O-">O-</option>
                                <option value="A-">A-</option>
                                <option value="B-">B-</option>
                                <option value="AB-">AB-</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h2><i class="fas fa-address-card"></i> المعلومات الوظيفية</h2>
                    <div class="input-row">
                        <div class="input-group">
                            <label>الرقم الوطني <span class="required">*</span></label>
                            <input type="text" name="national_id" required>
                        </div>
                        
                        <div class="input-group">
                            <label>تاريخ التعيين <span class="required">*</span></label>
                            <input type="text" name="hire_date" placeholder="dd/mm/yyyy" pattern="\d{2}/\d{2}/\d{4}" required>
                        </div>
                    </div>

                    <div class="input-row">
                        <div class="input-group">
                            <label>القسم <span class="required">*</span></label>
                            <select name="department" required>
                                <?php
                                // // Fetch departments from the database
                                // try {
                                //     $stmt = $pdo->query("SELECT id, name FROM departments");
                                //     while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                //         echo '<option value="' . htmlspecialchars($row['id']) . '">' . htmlspecialchars($row['name']) . '</option>';
                                //     }
                                // } catch (PDOException $e) {
                                //     echo '<option value="">خطأ في جلب الأقسام</option>';
                                // }
                                ?> 
                                <option value="HR">الموارد البشرية</option>
                                <option value="Finance">المالية</option>
                                <option value="IT">تقنية المعلومات</option>
                                <option value="Operations">العمليات</option>
                            </select>
                        </div>
                        
                        <div class="input-group">
                            <label>المنصب <span class="required">*</span></label>
                            <input type="text" name="position" required>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h2><i class="fas fa-phone"></i> معلومات الاتصال</h2>
                    <div class="input-row">
                        <div class="input-group">
                            <label>رقم الهاتف <span class="required">*</span></label>
                            <input type="tel" name="phone" required>
                        </div>
                        
                        <div class="input-group">
                            <label>البريد الإلكتروني</label>
                            <input type="email" name="email">
                        </div>
                    </div>

                    <div class="input-group">
                        <label>العنوان</label>
                        <textarea name="address" rows="3"></textarea>
                    </div>
                </div>

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
</body>
</html>