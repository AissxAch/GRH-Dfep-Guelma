<?php
session_start();
require 'config/dbconnect.php';
require 'config/functions.php';

// Check authentication and admin privileges
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Enhanced backup function with compression option
function generateDatabaseBackup($compress = false) {
    try {
        $pdo = getDBConnection();
        
        // Get all tables
        $tables = [];
        $stmt = $pdo->query("SHOW TABLES");
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }

        if (empty($tables)) {
            throw new Exception("No tables found in database");
        }

        $filename = 'grh_defp_backup_' . date('Y-m-d_H-i-s') . '.sql';
        $output = "-- GRH Depf Database Backup\n";
        $output .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        $output .= "-- Database: grh_defp\n\n";

        foreach ($tables as $table) {
            // Table structure
            $output .= "--\n-- Table structure for table `$table`\n--\n";
            $output .= "DROP TABLE IF EXISTS `$table`;\n";
            $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
            $row = $stmt->fetch(PDO::FETCH_NUM);
            $output .= $row[1] . ";\n\n";

            // Table data - fetch in chunks for large tables
            $output .= "--\n-- Dumping data for table `$table`\n--\n";
            $offset = 0;
            $chunkSize = 1000;
            do {
                $stmt = $pdo->query("SELECT * FROM `$table` LIMIT $offset, $chunkSize");
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($rows as $row) {
                    $values = array_map(function($value) use ($pdo) {
                        return is_null($value) ? 'NULL' : $pdo->quote($value);
                    }, $row);
                    $output .= "INSERT INTO `$table` VALUES(" . implode(',', $values) . ");\n";
                }
                
                $offset += $chunkSize;
            } while (!empty($rows));
            
            $output .= "\n";
        }

        // Handle compression if requested
        if ($compress) {
            $filename .= '.gz';
            $output = gzencode($output, 9);
        }

        // Stream output to browser
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($output));
        echo $output;
        exit();

    } catch (Exception $e) {
        die("Backup failed: " . $e->getMessage());
    }
}

// Handle backup request
if (isset($_GET['action']) && $_GET['action'] == 'backup') {
    $compress = isset($_GET['compress']) && $_GET['compress'] == '1';
    generateDatabaseBackup($compress);
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نسخ احتياطي - GRH Depf</title>
    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="CSS/icons.css">
    <style>
        .backup-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .backup-card {
            text-align: center;
            padding: 2rem;
            border: 2px dashed #ddd;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        
        .backup-icon {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .backup-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }
        
        .backup-btn {
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .btn-backup {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-restore {
            background: var(--success-color);
            color: white;
        }
        
        .btn-backup:hover, .btn-restore:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .instructions {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-top: 2rem;
        }
        
        .instructions h3 {
            color: var(--secondary-color);
            margin-bottom: 1rem;
        }
        
        .instructions ol {
            padding-right: 1.5rem;
        }
        
        .instructions li {
            margin-bottom: 0.5rem;
        }
        .backup-options {
            margin: 1rem 0;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .option-group {
            margin: 1rem 0;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include 'header.php'; ?>

        <main class="dashboard-main">
            <h1 class="dashboard-title">نسخ احتياطي واستعادة قاعدة البيانات</h1>
            
            <div class="backup-container">
                <div class="backup-card">
                    <i class="fas fa-database backup-icon"></i>
                    <h2>نسخ احتياطي لقاعدة البيانات</h2>
                    <p>قم بإنشاء نسخة احتياطية كاملة من قاعدة البيانات الخاصة بالنظام</p>
                    
                    <div class="backup-options">
                        <div class="option-group">
                            <label>
                                <input type="checkbox" id="compress" name="compress" value="1">
                                ضغط النسخة الاحتياطية (توفير المساحة)
                            </label>
                        </div>
                    </div>
                    
                    <div class="backup-actions">
                        <a href="#" id="backupBtn" class="backup-btn btn-backup">
                            <i class="fas fa-download"></i>
                            إنشاء نسخة احتياطية
                        </a>
                        <a href="restore.php" class="backup-btn btn-restore">
                            <i class="fas fa-upload"></i>
                            استعادة نسخة احتياطية
                        </a>
                    </div>
                </div>
                
                <div class="instructions">
                    <h3><i class="fas fa-info-circle"></i> تعليمات النسخ الاحتياطي</h3>
                    <ol>
                        <li>انقر على زر "إنشاء نسخة احتياطية" لتنزيل ملف SQL يحتوي على جميع بيانات النظام</li>
                        <li>احفظ الملف في مكان آمن حيث يمكنك استعادته لاحقًا إذا لزم الأمر</li>
                        <li>للاستعادة، انتقل إلى صفحة "استعادة نسخة احتياطية" وقم بتحميل الملف الذي تم حفظه مسبقًا</li>
                        <li>سيتم استبدال جميع البيانات الحالية بالبيانات من النسخة الاحتياطية</li>
                    </ol>
                    
                    <div class="alert alert-warning" style="margin-top: 1rem; padding: 1rem; background: #fff3cd; border-left: 4px solid #ffc107;">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>تحذير:</strong> عملية الاستعادة ستحذف جميع البيانات الحالية وتستبدلها بالبيانات من النسخة الاحتياطية. تأكد من أن لديك نسخة احتياطية حديثة قبل الاستعادة.
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.getElementById('backupBtn').addEventListener('click', function(e) {
            e.preventDefault();
            const compress = document.getElementById('compress').checked ? '1' : '0';
            window.location.href = 'backup.php?action=backup&compress=' + compress;
        });
    </script>
</body>
</html>