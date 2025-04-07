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
$employee = null;
$documents = [];
$employee_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch employee details
try {
    $stmt = $pdo->prepare("SELECT full_name_ar FROM employees WHERE employee_id = :employee_id");
    $stmt->execute(['employee_id' => $employee_id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$employee) {
        $error = "الموظف غير موجود في النظام";
    }
} catch (PDOException $e) {
    $error = "خطأ في قاعدة البيانات: " . $e->getMessage();
}

// Only process documents if employee exists
if (!$error) {
    // Handle document upload
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload'])) {
        try {
            $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
            
            if (empty($title)) {
                throw new Exception("عنوان المستند مطلوب");
            }
            
            // Handle file upload
            $upload_dir = 'uploads/documents/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = '';
            if (isset($_FILES['document_file'])) {
                $file = $_FILES['document_file'];
                $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $file_name = uniqid() . '.' . $file_ext;
                $file_path = $upload_dir . $file_name;
                
                if (!move_uploaded_file($file['tmp_name'], $file_path)) {
                    throw new Exception("فشل تحميل الملف");
                }
            }
            
            // Insert into database
            $sql = "INSERT INTO employee_documents 
                    (employee_id, title, file_name, upload_date) 
                    VALUES 
                    (:employee_id, :title, :file_name, NOW())";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':employee_id' => $employee_id,
                ':title' => $title,
                ':file_name' => $file_name
            ]);
            
            $success = "تم تحميل المستند بنجاح";
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }

    // Handle document deletion
    if (isset($_GET['delete'])) {
        try {
            $doc_id = intval($_GET['delete']);
            
            // Get document info first
            $stmt = $pdo->prepare("SELECT file_name FROM employee_documents WHERE id = :id AND employee_id = :employee_id");
            $stmt->execute([':id' => $doc_id, ':employee_id' => $employee_id]);
            $document = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($document) {
                // Delete file if exists
                if (!empty($document['file_name'])) {
                    $file_path = 'uploads/documents/' . $document['file_name'];
                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }
                }
                
                // Delete from database
                $stmt = $pdo->prepare("DELETE FROM employee_documents WHERE id = :id");
                $stmt->execute([':id' => $doc_id]);
                
                $success = "تم حذف المستند بنجاح";
            }
        } catch (PDOException $e) {
            $error = "خطأ في قاعدة البيانات: " . $e->getMessage();
        }
    }

    // Fetch all documents for this employee
    try {
        $stmt = $pdo->prepare("SELECT * FROM employee_documents WHERE employee_id = :employee_id ORDER BY upload_date DESC");
        $stmt->execute([':employee_id' => $employee_id]);
        $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = "خطأ في جلب المستندات: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مستندات الموظف - GRH Depf</title>
    <link rel="stylesheet" href="CSS/document.css">
    <link rel="stylesheet" href="CSS/icons.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include 'header.php'; ?>

        <main class="dashboard-main">
            <?php if (isset($error) && !$employee): ?>
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
                <h1 class="dashboard-title">مستندات الموظف: <?= htmlspecialchars($employee['full_name_ar']) ?></h1>
                
                <?php if (isset($error)): ?>
                    <div class="error-message"><?= $error ?></div>
                <?php endif; ?>
                
                <?php if (isset($success)): ?>
                    <div class="success-message"><?= $success ?></div>
                <?php endif; ?>

                <div class="document-actions">
                    <button class="add-document-btn" id="showUploadForm">
                        <i class="fas fa-plus"></i> إضافة مستند جديد
                    </button>
                    <a href="employee.php?id=<?= $employee_id ?>" class="back-button">
                        <i class="fas fa-arrow-right"></i> العودة لملف الموظف
                    </a>
                </div>

                <!-- Document Upload Form (Initially Hidden) -->
                <form id="documentUploadForm" class="document-form" method="POST" enctype="multipart/form-data" style="display: none;">
                    <div class="form-section">
                        <h2><i class="fas fa-upload"></i> تحميل مستند جديد</h2>
                        
                        <div class="input-group">
                            <label>عنوان المستند <span class="required">*</span></label>
                            <input type="text" name="title" required>
                        </div>
                        
                        <div class="input-group">
                            <label>ملف المستند <span class="required">*</span></label>
                            <input type="file" name="document_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
                            <small class="file-hint">(PDF, Word, JPG, PNG - الحد الأقصى 5MB)</small>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="upload" class="save-button">
                                <i class="fas fa-save"></i> حفظ المستند
                            </button>
                            <button type="button" id="cancelUpload" class="cancel-button">
                                <i class="fas fa-times"></i> إلغاء
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Documents Table -->
                <div class="documents-list">
                    <h2><i class="fas fa-file-alt"></i> قائمة المستندات</h2>
                    
                    <?php if (empty($documents)): ?>
                        <div class="no-documents">
                            <i class="fas fa-folder-open"></i>
                            <p>لا توجد مستندات مسجلة لهذا الموظف</p>
                        </div>
                    <?php else: ?>
                        <div class="documents-table-container">
                            <table class="documents-table">
                                <thead>
                                    <tr>
                                        <th>عنوان المستند</th>
                                        <th>نوع المستند</th>
                                        <th>تاريخ التحميل</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($documents as $doc): ?>
                                        <tr>
                                            <td data-label="العنوان" class="document-title"><?= htmlspecialchars($doc['title']) ?></td>
                                            <td data-label="نوع المستند" class="document-type">
                                                <?php
                                                $ext = pathinfo($doc['file_name'], PATHINFO_EXTENSION);
                                                $icon = 'fa-file';
                                                if (in_array($ext, ['pdf'])) $icon = 'fa-file-pdf';
                                                elseif (in_array($ext, ['doc', 'docx'])) $icon = 'fa-file-word';
                                                elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) $icon = 'fa-file-image';
                                                ?>
                                                <i class="fas <?= $icon ?>"></i>
                                                <?= strtoupper($ext) ?>
                                            </td>
                                            <td data-label="التاريخ" class="document-date">
                                                <i class="fas fa-calendar-alt"></i>
                                                <?= date('d/m/Y', strtotime($doc['upload_date'])) ?>
                                            </td>
                                            <td data-label="الإجراءات" class="document-actions">
                                                <a href="uploads/documents/<?= $doc['file_name'] ?>" class="download-btn" download>
                                                    <i class="fas fa-download"></i>
                                                </a>
                                                <a href="document.php?id=<?= $employee_id ?>&delete=<?= $doc['id'] ?>" class="delete-btn" onclick="return confirm('هل أنت متأكد من حذف هذا المستند؟');">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        // Toggle document upload form visibility
        document.getElementById('showUploadForm').addEventListener('click', function() {
            document.getElementById('documentUploadForm').style.display = 'block';
            this.style.display = 'none';
        });
        
        document.getElementById('cancelUpload').addEventListener('click', function() {
            document.getElementById('documentUploadForm').style.display = 'none';
            document.getElementById('showUploadForm').style.display = 'block';
        });
    </script>
</body>
</html>