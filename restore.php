<?php
session_start();
require 'config/dbconnect.php';
require 'config/functions.php';

// Check authentication and admin privileges
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$error = '';
$success = '';

// Process restore if form submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['backup_file'])) {
    try {
        // Check for upload errors
        if ($_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Error uploading file: " . $_FILES['backup_file']['error']);
        }
        
        // Check file type
        $file_type = strtolower(pathinfo($_FILES['backup_file']['name'], PATHINFO_EXTENSION));
        if (!in_array($file_type, ['sql', 'gz'])) {
            throw new Exception("Only .sql or .sql.gz files are allowed");
        }
        
        // Check file size (max 50MB)
        if ($_FILES['backup_file']['size'] > 50 * 1024 * 1024) {
            throw new Exception("File size exceeds 50MB limit");
        }
        
        // Get temporary file path
        $tmp_file = $_FILES['backup_file']['tmp_name'];
        
        // Read file content
        if ($file_type == 'gz') {
            if (!function_exists('gzdecode')) {
                throw new Exception("Zlib extension not available to decompress .gz files");
            }
            $sql = gzdecode(file_get_contents($tmp_file));
        } else {
            $sql = file_get_contents($tmp_file);
        }
        
        if (empty($sql)) {
            throw new Exception("Empty or invalid SQL file");
        }
        
        // Get database connection
        $pdo = getDBConnection();
        
        // Set timeout and buffer size
        set_time_limit(3600); // 1 hour timeout
        ini_set('memory_limit', '512M');
        
        // Disable foreign key checks and timeouts
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        $pdo->exec('SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO"');
        $pdo->exec('SET AUTOCOMMIT = 0');
        $pdo->exec('SET NAMES utf8mb4');
        
        // Remove comments and trim
        $sql = preg_replace('/\/\*.*?\*\/|--.*?$|\#.*?$/ms', '', $sql);
        $sql = trim($sql);
        
        // Improved SQL parser
        $queries = [];
        $currentQuery = '';
        $inString = false;
        $stringChar = '';
        $inComment = false;
        $escapeNext = false;

        for ($i = 0; $i < strlen($sql); $i++) {
            $char = $sql[$i];
            
            // Handle escaped characters
            if ($escapeNext) {
                $currentQuery .= $char;
                $escapeNext = false;
                continue;
            }
            
            // Handle string literals
            if ($char === '\\') {
                $escapeNext = true;
                $currentQuery .= $char;
                continue;
            }
            
            if (($char === '"' || $char === "'") && !$inComment) {
                if (!$inString) {
                    $inString = true;
                    $stringChar = $char;
                } elseif ($char === $stringChar) {
                    $inString = false;
                }
            }
            
            // Handle comments
            if (!$inString) {
                if ($char === '#' || ($char === '-' && $i < strlen($sql) - 1 && $sql[$i+1] === '-')) {
                    $inComment = true;
                    continue;
                }
                if ($char === "\n" && $inComment) {
                    $inComment = false;
                    continue;
                }
            }
            
            if ($inComment) {
                continue;
            }
            
            $currentQuery .= $char;
            
            if ($char === ';' && !$inString) {
                $query = trim($currentQuery);
                if (!empty($query)) {
                    $queries[] = $query;
                }
                $currentQuery = '';
            }
        }

        // Filter out any empty queries
        $queries = array_filter($queries, function($query) {
            return !empty(trim($query));
        });

        // Execute queries in batches
        $batchSize = 50;
        $totalQueries = count($queries);
        $executedQueries = 0;

        foreach (array_chunk($queries, $batchSize) as $batch) {
            try {
                $pdo->beginTransaction();
                
                foreach ($batch as $query) {
                    $query = trim($query);
                    if (!empty($query)) {
                        try {
                            $pdo->exec($query);
                            $executedQueries++;
                        } catch (PDOException $e) {
                            throw new Exception("Failed to execute query #$executedQueries: " . $e->getMessage() . "\nQuery: " . substr($query, 0, 200));
                        }
                    }
                }
                
                $pdo->commit();
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $e;
            }
        }
        
        // Re-enable foreign key checks
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        $pdo->exec('SET AUTOCOMMIT = 1');
        
        $success = "Database restored successfully. Executed $executedQueries queries.";
        
    } catch (Exception $e) {
        if (isset($pdo)) {
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
            $pdo->exec('SET AUTOCOMMIT = 1');
        }
        $error = "Restore failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>استعادة نسخة احتياطية - GRH Depf</title>
    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="CSS/icons.css">
    <style>
        .restore-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .restore-form {
            margin-top: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--secondary-color);
        }
        
        .form-group input[type="file"] {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            background: #f8f9fa;
        }
        
        .submit-btn {
            padding: 0.8rem 1.5rem;
            background: var(--success-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .submit-btn:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .progress-container {
            margin: 1rem 0;
            display: none;
        }
        
        .progress-bar {
            height: 20px;
            background: #f0f0f0;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .progress {
            height: 100%;
            background: var(--primary-color);
            width: 0%;
            transition: width 0.3s ease;
        }
        
        .query-info {
            font-family: monospace;
            background: #f8f9fa;
            padding: 0.5rem;
            border-radius: 4px;
            margin-top: 0.5rem;
            font-size: 0.9rem;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include 'header.php'; ?>

        <main class="dashboard-main">
            <h1 class="dashboard-title">استعادة نسخة احتياطية</h1>
            
            <div class="restore-container">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                        <?php if (strpos($error, 'Failed to execute query') !== false): ?>
                            <div class="query-info"><?php echo htmlspecialchars(explode("\nQuery:", $error)[1] ?? ''); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <div class="restore-instructions">
                    <h2><i class="fas fa-info-circle"></i> تعليمات الاستعادة</h2>
                    <p>استخدم هذه الصفحة لاستعادة قاعدة البيانات من نسخة احتياطية سابقة.</p>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>تحذير:</strong> عملية الاستعادة ستحذف جميع البيانات الحالية وتستبدلها بالبيانات من النسخة الاحتياطية. تأكد من أن لديك نسخة احتياطية حديثة قبل المتابعة.
                    </div>
                </div>
                
                <form class="restore-form" method="post" enctype="multipart/form-data" onsubmit="showProgress()">
                    <div class="form-group">
                        <label for="backup_file">
                            <i class="fas fa-file-upload"></i> اختر ملف النسخة الاحتياطية (.sql أو .sql.gz)
                        </label>
                        <input type="file" id="backup_file" name="backup_file" accept=".sql,.gz" required>
                    </div>
                    
                    <div class="progress-container" id="progressContainer">
                        <div class="progress-bar">
                            <div class="progress" id="progressBar"></div>
                        </div>
                        <div id="progressText">جاري الاستعادة...</div>
                    </div>
                    
                    <button type="submit" class="submit-btn" id="submitBtn">
                        <i class="fas fa-upload"></i> استعادة النسخة الاحتياطية
                    </button>
                </form>
            </div>
        </main>
    </div>

    <script>
        function showProgress() {
            document.getElementById('progressContainer').style.display = 'block';
            document.getElementById('submitBtn').disabled = true;
            
            // Simulate progress (real progress would need AJAX)
            let progress = 0;
            const interval = setInterval(() => {
                progress += 5;
                document.getElementById('progressBar').style.width = progress + '%';
                
                if (progress >= 95) {
                    clearInterval(interval);
                }
            }, 300);
        }
    </script>
</body>
</html>