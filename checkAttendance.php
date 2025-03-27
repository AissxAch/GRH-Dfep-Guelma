<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

function checkAttendance($timeEntries) {
    if (empty($timeEntries)) {
        return 'غائب';
    }

    $times = preg_split('/\s+/', trim($timeEntries));
    $times = array_filter($times);
    
    if (empty($times)) {
        return 'غائب';
    }

    // إزالة التكرارات المتتالية
    $uniqueTimes = [];
    $prevTime = null;
    foreach ($times as $time) {
        if ($time !== $prevTime) {
            $uniqueTimes[] = $time;
            $prevTime = $time;
        }
    }

    $firstEntry = reset($uniqueTimes);
    $lastExit = end($uniqueTimes);

    $status = [];
    
    // التحقق من التأخر
    $entretime = strtotime($_POST['start_time']);
    $firstEntryTime = strtotime($firstEntry);
    if ($firstEntryTime > $entretime) {
        $minutesLate = round(($firstEntryTime - $entretime) / 60);
        $status[] = "تأخر ($minutesLate دقيقة)";
    }

    // التحقق من الخروج المبكر
    $leavetime = strtotime($_POST['end_time']);
    $lastExitTime = strtotime($lastExit);
    if ($lastExit === $firstEntry) {
        $status[] = "لا يوجد وقت خروج";
    } elseif ($lastExitTime < $leavetime) {
        $minutesEarly = round(($leavetime - $lastExitTime) / 60);
        $status[] = "خروج مبكر ($minutesEarly دقيقة)";
    }

    return $status ? implode('، ', $status) : 'حاضر';
}

// معالجة الملف
$results = [];
$date = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];
    
    if (($handle = fopen($file, "r"))) {
        fgetcsv($handle); // تخطي العناوين
        
        while (($data = fgetcsv($handle)) !== FALSE) {
            $name = $data[1];
            $date = $data[3] ?? date('Y-m-d');
            $timeEntries = $data[4] ?? '';

            $times = preg_split('/\s+/', trim($timeEntries));
            $times = array_filter($times);
            
            $uniqueTimes = [];
            $prevTime = null;
            foreach ($times as $time) {
                if ($time !== $prevTime) {
                    $uniqueTimes[] = $time;
                    $prevTime = $time;
                }
            }
            
            $entryTime = $uniqueTimes[0] ?? "لا يوجد وقت دخول";
            $exitTime = (count($uniqueTimes) > 1) ? end($uniqueTimes) : "لا يوجد وقت خروج";
            
            $status = checkAttendance($timeEntries);
            
            if ($status !== 'حاضر') {
                $results[] = [
                    'Name' => $name,
                    'Entry Time' => $entryTime,
                    'Exit Time' => $exitTime,
                    'Status' => $status
                ];
            }
        }
        fclose($handle);
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام متابعة الحضور - GRH Depf</title>
    <link rel="stylesheet" href="CSS/login.css">
    <link rel="stylesheet" href="CSS/checkAttendance.css">
</head>
<body>
    <div class="dashboard-container">
    <header class="dashboard-header">
    <div class="header-content">
        <div class="header-brand">
            <div class="brand-text">
                <h1>نظام إدارة الموارد البشرية</h1>
                <p>م.ت.ت.م قالمة</p>
            </div>
        </div>
        
        <div class="header-actions">
            <div class="user-profile">
                <i class="fas fa-user-circle"></i>
                <div class="profile-info">
                    <span class="username"><?php echo htmlspecialchars($_SESSION['fullname']); ?></span>
                    <span class="role">مستخدم نظام</span>
                </div>
            </div>
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span>تسجيل الخروج</span>
            </a>
        </div>
    </div>
    
    <nav class="header-nav">
        <a href="index.php" class="nav-link"><i class="fas fa-home"></i> الرئيسية</a>
    </nav>
</header>

        <main class="dashboard-main">
            <h1 class="dashboard-title">نظام متابعة الحضور</h1>
            
            <form class="upload-form" method="post" enctype="multipart/form-data">
                <!-- Add this inside the <form> section -->
                <div class="time-selection">
                    <label for="start_time"><i class="fas fa-clock"></i> وقت بداية الدوام:</label>
                    <div class="input-time-group">
                        <input class="times" type="time" id="start_time" name="start_time" 
                            value="<?= $_POST['start_time'] ?? '08:00' ?>" required>
                    </div>
                    
                    <label for="end_time"><i class="fas fa-clock"></i> وقت نهاية الدوام:</label>
                    <div class="input-time-group">
                        <input class="times" type="time" id="end_time" name="end_time" 
                            value="<?= $_POST['end_time'] ?? '16:00' ?>" required>
                    </div>
                </div>
                <div class="input-group file-input">
                    <label for="csv_file">
                        <i class="fas fa-file-upload"></i>
                        اختر ملف CSV
                    </label>
                    <input type="file" name="csv_file" id="csv_file" accept=".csv" required>
                    <button type="submit" class="login-button">
                        <i class="fas fa-chart-bar"></i>
                        عرض النتائج
                    </button>
                </div>
            </form>

            <?php if (!empty($results)): ?>
            <div class="attendance-report">
                <h2>تقرير الحضور اليومي</h2>
                <div class="report-date">
                    <i class="fas fa-calendar-alt"></i>
                    تاريخ التقرير: <?php echo htmlspecialchars($date) ?>
                </div>

                <div class="attendance-table">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>الموظف</th>
                                <th>وقت الدخول</th>
                                <th>وقت الخروج</th>
                                <th>الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $row): ?>
                                <tr class="<?php
                                    if (strpos($row['Status'], 'غائب') !== false) echo 'absent';
                                    elseif (strpos($row['Status'], 'تأخر') !== false) echo 'late';
                                    elseif (strpos($row['Status'], 'مبكر') !== false) echo 'early';
                                ?>">
                                <td><?= htmlspecialchars($row['Name']) ?></td>
                                <td><?= htmlspecialchars($row['Entry Time']) ?></td>
                                <td><?= htmlspecialchars($row['Exit Time']) ?></td>
                                <td>
                                    <i class="status-icon <?php
                                        if (strpos($row['Status'], 'غائب') !== false) echo 'fas fa-times-circle';
                                        elseif (strpos($row['Status'], 'تأخر') !== false) echo 'fas fa-clock';
                                        else echo 'fas fa-check-circle';
                                    ?>"></i>
                                    <?= htmlspecialchars($row['Status']) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>