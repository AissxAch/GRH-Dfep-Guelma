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
$success = null;
$laws = [];
$editLaw = null;

// Get all laws
try {
    $stmt = $pdo->query("SELECT * FROM laws ORDER BY law_category, created_at DESC");
    $laws = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "خطأ في قاعدة البيانات: " . $e->getMessage();
}

// Process delete law
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM laws WHERE law_id = ?");
        $stmt->execute([$_GET['delete']]);
        $success = "تم حذف القانون بنجاح";
        header("Location: manage_laws.php?success=" . urlencode($success));
        exit();
    } catch (PDOException $e) {
        $error = "خطأ في حذف القانون: " . $e->getMessage();
    }
}

// Get law for editing
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM laws WHERE law_id = ?");
        $stmt->execute([$_GET['edit']]);
        $editLaw = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$editLaw) {
            $error = "القانون غير موجود";
        }
    } catch (PDOException $e) {
        $error = "خطأ في قاعدة البيانات: " . $e->getMessage();
    }
}

// Process form submission for add/edit law
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lawText = $_POST['law_text'] ?? '';
    $lawCategory = $_POST['law_category'] ?? '';
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $lawId = $_POST['law_id'] ?? '';
    
    // Validate input
    if (empty($lawText) || empty($lawCategory)) {
        $error = "يرجى ملء جميع الحقول المطلوبة";
    } else {
        try {
            // Update or insert
            if (!empty($lawId)) {
                $stmt = $pdo->prepare("UPDATE laws SET law_text = ?, law_category = ?, is_active = ? WHERE law_id = ?");
                $stmt->execute([$lawText, $lawCategory, $isActive, $lawId]);
                $success = "تم تحديث القانون بنجاح";
            } else {
                $stmt = $pdo->prepare("INSERT INTO laws (law_text, law_category, is_active) VALUES (?, ?, ?)");
                $stmt->execute([$lawText, $lawCategory, $isActive]);
                $success = "تم إضافة القانون بنجاح";
            }
            
            // Redirect to prevent form resubmission
            header("Location: manage_laws.php?success=" . urlencode($success));
            exit();
        } catch (PDOException $e) {
            $error = "خطأ في حفظ القانون: " . $e->getMessage();
        }
    }
}

// Process success message from redirect
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة القوانين - GRH Depf</title>
    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="CSS/icons.css">
    <link rel="stylesheet" href="CSS/manage_laws.css">
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="dashboard-container">
        <main class="dashboard-main">
            <h1 class="dashboard-title">إدارة القوانين</h1>
            
            <?php if ($success): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>
            
            <div class="laws-container">
                <h2><i class="fas fa-plus-circle"></i> <?= $editLaw ? 'تعديل القانون' : 'إضافة قانون جديد' ?></h2>
                <form method="post" class="laws-form">
                    <?php if ($editLaw): ?>
                    <input type="hidden" name="law_id" value="<?= htmlspecialchars($editLaw['law_id']) ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="law_text">نص القانون:</label>
                        <textarea id="law_text" name="law_text" required><?= $editLaw ? htmlspecialchars($editLaw['law_text']) : '' ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="law_category">فئة القانون:</label>
                        <select id="law_category" name="law_category" required>
                            <option value="">اختر الفئة</option>
                            <option value="deduction" <?= ($editLaw && $editLaw['law_category'] == 'deduction') ? 'selected' : '' ?>>قرار الخصم</option>
                            <option value="annual_leave" <?= ($editLaw && $editLaw['law_category'] == 'annual_leave') ? 'selected' : '' ?>>العطلة السنوية</option>
                            <option value="sick_leave" <?= ($editLaw && $editLaw['law_category'] == 'sick_leave') ? 'selected' : '' ?>>العطلة المرضية</option>
                            <option value="multiple" <?= ($editLaw && $editLaw['law_category'] == 'multiple') ? 'selected' : '' ?>>متعدد (يظهر في جميع القرارات)</option>
                        </select>
                    </div>
                    
                    <div class="form-check">
                        <input type="checkbox" id="is_active" name="is_active" <?= (!$editLaw || $editLaw['is_active']) ? 'checked' : '' ?>>
                        <label for="is_active">نشط</label>
                    </div>
                    
                    <div class="form-buttons">
                        <button type="submit">
                            <i class="fas fa-save"></i>
                            <?= $editLaw ? 'تحديث القانون' : 'إضافة القانون' ?>
                        </button>
                        <?php if ($editLaw): ?>
                        <a href="manage_laws.php" class="button secondary">
                            <i class="fas fa-times"></i>
                            إلغاء التعديل
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
                
                <h2><i class="fas fa-list"></i> قائمة القوانين</h2>
                <?php if (count($laws) > 0): ?>
                <table class="laws-table">
                    <thead>
                        <tr>
                            <th width="5%">#</th>
                            <th width="60%">نص القانون</th>
                            <th width="15%">الفئة</th>
                            <th width="10%">الحالة</th>
                            <th width="10%">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($laws as $index => $law): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= htmlspecialchars(substr($law['law_text'], 0, 100)) . (strlen($law['law_text']) > 100 ? '...' : '') ?></td>
                            <td>
                                <?php
                                $categoryName = '';
                                $categoryClass = '';
                                
                                switch ($law['law_category']) {
                                    case 'deduction':
                                        $categoryName = 'قرار الخصم';
                                        $categoryClass = 'category-deduction';
                                        break;
                                    case 'annual_leave':
                                        $categoryName = 'العطلة السنوية';
                                        $categoryClass = 'category-annual_leave';
                                        break;
                                    case 'sick_leave':
                                        $categoryName = 'العطلة المرضية';
                                        $categoryClass = 'category-sick_leave';
                                        break;
                                    case 'multiple':
                                        $categoryName = 'متعدد';
                                        $categoryClass = 'category-multiple';
                                        break;
                                }
                                ?>
                                <span class="law-category <?= $categoryClass ?>"><?= $categoryName ?></span>
                            </td>
                            <td>
                                <?= $law['is_active'] ? '<span style="color: green;">نشط</span>' : '<span style="color: red;">غير نشط</span>' ?>
                            </td>
                            <td class="law-actions">
                                <a href="manage_laws.php?edit=<?= $law['law_id'] ?>" class="edit-law" title="تعديل">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="manage_laws.php?delete=<?= $law['law_id'] ?>" class="delete-law" title="حذف" 
                                   onclick="return confirm('هل أنت متأكد من حذف هذا القانون؟');">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="no-data">لا توجد قوانين مضافة حتى الآن.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>