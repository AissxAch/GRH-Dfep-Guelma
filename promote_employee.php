<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Database connection
include 'config/dbconnect.php';
$pdo = getDBConnection();

$employee = null;
$error = "";
$success = "";

// Handle employee search
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['id'])) {
    $search_id = trim($_GET['id']);
    
    $query = "SELECT e.employee_id, e.firstname_ar, e.lastname_ar, e.position, e.department_id, d.name AS department_name
              FROM employees e
              LEFT JOIN departments d ON e.department_id = d.department_id
              WHERE e.employee_id = :id OR e.national_id = :id";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':id', $search_id);
    $stmt->execute();
    
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$employee) {
        $error = "لم يتم العثور على موظف بهذا الرقم.";
    }
}

// Handle promotion form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['employee_id']) && isset($_POST['new_position']) && isset($_POST['end_date']) && isset($_POST['start_date'])) {
    $employee_id = $_POST['employee_id'];
    $new_position = $_POST['new_position'];
    $end_date = $_POST['end_date'];
    $start_date = $_POST['start_date'];
    
    // Validate inputs
    if (empty($new_position) || empty($end_date) || empty($start_date)) {
        $error = "يرجى ملء جميع الحقول المطلوبة.";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Get current employee details
            $stmt = $pdo->prepare("SELECT position, department_id, hire_date FROM employees WHERE employee_id = :employee_id");
            $stmt->bindParam(':employee_id', $employee_id);
            $stmt->execute();
            $current = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($current) {
                // Insert current position into employee_previous_positions
                $stmt = $pdo->prepare("INSERT INTO employee_previous_positions (employee_id, position, department_id, start_date, end_date)
                                       VALUES (:employee_id, :position, :department_id, :start_date, :end_date)");
                $stmt->bindParam(':employee_id', $employee_id);
                $stmt->bindParam(':position', $current['position']);
                $stmt->bindParam(':department_id', $current['department_id']);
                $stmt->bindParam(':start_date', $current['hire_date']);
                $stmt->bindParam(':end_date', $end_date);
                $stmt->execute();
                
                // Update employee's position
                $stmt = $pdo->prepare("UPDATE employees SET position = :new_position, hire_date = :start_date, updated_at = NOW() WHERE employee_id = :employee_id");
                $stmt->bindParam(':new_position', $new_position);
                $stmt->bindParam(':start_date', $start_date);
                $stmt->bindParam(':employee_id', $employee_id);
                $stmt->execute();
                
                $pdo->commit();
                $success = "تم ترقية الموظف بنجاح.";
                $employee = null; // Clear employee data to reset form
            } else {
                $error = "لم يتم العثور على الموظف.";
                $pdo->rollBack();
            }
        } catch(PDOException $e) {
            $pdo->rollBack();
            $error = "خطأ أثناء ترقية الموظف: " . $e->getMessage();
        }
    }
}

// Fetch all positions for the dropdown
$positions = [];
$stmt = $pdo->prepare("SELECT position_id, name FROM positions ORDER BY name");
$stmt->execute();
$positions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ترقية موظف - GRH Depf</title>
    <link rel="stylesheet" href="CSS/promote_employee.css">
    <link rel="stylesheet" href="CSS/icons.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include 'header.php'; ?>

        <main class="dashboard-main">
            <h1 class="dashboard-title">ترقية موظف</h1>
            <!-- Error/Success Messages -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <!-- Promotion Form -->
            <?php if ($employee): ?>
                <div class="card">
                    <h3>بيانات الموظف</h3>
                    <p><strong>الاسم:</strong> <?php echo htmlspecialchars($employee['firstname_ar'] . ' ' . $employee['lastname_ar']); ?></p>
                    <p><strong>الرتبة الحالية:</strong> <?php echo htmlspecialchars($employee['position']); ?></p>
                    <p><strong>القسم:</strong> <?php echo htmlspecialchars($employee['department_name'] ?: 'غير محدد'); ?></p>

                    <form action="promote_employee.php" method="POST">
                        <input type="hidden" name="employee_id" value="<?php echo $employee['employee_id']; ?>">
                        
                        <div class="form-group">
                            <label for="new_position">الرتبة الجديدة:</label>
                            <select name="new_position" id="new_position" required>
                                <option value="">اختر الرتبة...</option>
                                <?php foreach ($positions as $position): ?>
                                    <option value="<?php echo htmlspecialchars($position['name']); ?>" <?php echo $position['name'] == $employee['position'] ? 'disabled' : ''; ?>>
                                        <?php echo htmlspecialchars($position['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="end_date">تاريخ انتهاء الرتبة الحالية:</label>
                            <input type="date" name="end_date" id="end_date" required>
                        </div>
                        <div class="form-group">
                            <label for="start_date">تاريخ بدء الرتبة الجديدة:</label>
                            <input type="date" name="start_date" id="start_date" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">تأكيد الترقية</button>
                    </form>
                </div>
            <?php endif; ?>
        </main>

        <footer class="dashboard-footer">
            <p>جميع الحقوق محفوظة © م.ت.ت.م قالمة <?php echo date('Y'); ?></p>
        </footer>
    </div>
</body>
</html>