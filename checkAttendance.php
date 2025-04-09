<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

function checkAttendance($timeEntries) {
    if (empty($timeEntries)) {
        return [
            'status_am' => 'غياب صباحي',
            'status_pm' => 'غياب مسائي',
            'entry_am' => 'لا يوجد',
            'exit_am' => 'لا يوجد',
            'entry_pm' => 'لا يوجد',
            'exit_pm' => 'لا يوجد'
        ];
    }

    $times = preg_split('/\s+/', trim($timeEntries));
    $times = array_filter($times);
    
    if (empty($times)) {
        return [
            'status_am' => 'غياب صباحي',
            'status_pm' => 'غياب مسائي',
            'entry_am' => 'لا يوجد',
            'exit_am' => 'لا يوجد',
            'entry_pm' => 'لا يوجد',
            'exit_pm' => 'لا يوجد'
        ];
    }

    // Remove consecutive duplicates
    $uniqueTimes = [];
    $prevTime = null;
    foreach ($times as $time) {
        if ($time !== $prevTime) {
            $uniqueTimes[] = $time;
            $prevTime = $time;
        }
    }

    // Initialize variables
    $entryAM = "لا يوجد";
    $exitAM = "لا يوجد";
    $entryPM = "لا يوجد";
    $exitPM = "لا يوجد";
    $statusAM = "حاضر";
    $statusPM = "حاضر";

    // Morning session (8:00-12:00)
    $morningEntries = array_filter($uniqueTimes, function($time) {
        $timeValue = strtotime($time);
        return $timeValue >= strtotime('06:00') && $timeValue <= strtotime('12:30');
    });
    
    if (empty($morningEntries)) {
        $statusAM = "غياب صباحي";
    } else {
        $entryAM = reset($morningEntries);
        $exitAM = count($morningEntries) > 1 ? end($morningEntries) : "لا يوجد";

        // Check lateness
        $morningStart = strtotime($_POST['start_time_am'] ?? '08:00');
        if (strtotime($entryAM) > $morningStart) {
            $minutesLate = round((strtotime($entryAM) - $morningStart) / 60);
            $statusAM = "تأخر ($minutesLate دقيقة)";
        }

        // Check early exit
        $morningEnd = strtotime($_POST['end_time_am'] ?? '12:00');
        if ($exitAM !== "لا يوجد" && strtotime($exitAM) < $morningEnd) {
            $minutesEarly = round(($morningEnd - strtotime($exitAM))) / 60;
            $statusAM = "خروج مبكر ($minutesEarly دقيقة)";
        }

        // Check missing exit
        if ($exitAM === "لا يوجد") {
            $statusAM = "لا يوجد خروج";
        }
    }

    // Afternoon session (13:00-16:00)
    $afternoonEntries = array_filter($uniqueTimes, function($time) {
        $timeValue = strtotime($time);
        return $timeValue >= strtotime('12:30') && $timeValue <= strtotime('18:00');
    });
    
    if (empty($afternoonEntries)) {
        $statusPM = "غياب مسائي";
    } else {
        $entryPM = reset($afternoonEntries);
        $exitPM = count($afternoonEntries) > 1 ? end($afternoonEntries) : "لا يوجد";

        // Check lateness
        $afternoonStart = strtotime($_POST['start_time_pm'] ?? '13:00');
        if (strtotime($entryPM) > $afternoonStart) {
            $minutesLate = round((strtotime($entryPM) - $afternoonStart) / 60);
            $statusPM = "تأخر ($minutesLate دقيقة)";
        }

        // Check early exit
        $afternoonEnd = strtotime($_POST['end_time_pm'] ?? '16:00');
        if ($exitPM !== "لا يوجد" && strtotime($exitPM) < $afternoonEnd) {
            $minutesEarly = round(($afternoonEnd - strtotime($exitPM)) / 60);
            $statusPM = "خروج مبكر ($minutesEarly دقيقة)";
        }

        // Check missing exit
        if ($exitPM === "لا يوجد") {
            $statusPM = "لا يوجد خروج";
        }
    }

    return [
        'status_am' => $statusAM,
        'status_pm' => $statusPM,
        'entry_am' => $entryAM,
        'exit_am' => $exitAM,
        'entry_pm' => $entryPM,
        'exit_pm' => $exitPM
    ];
}

// Process file
$results = [];
$date = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];
    
    if (($handle = fopen($file, "r"))) {
        fgetcsv($handle); // Skip headers
        
        while (($data = fgetcsv($handle)) !== FALSE) {
            $name = $data[1];
            $date = $data[3] ?? date('Y-m-d');
            $timeEntries = $data[4] ?? '';

            $attendance = checkAttendance($timeEntries);
            
            // Only show employees with attendance issues
            if ($attendance['status_am'] !== 'حاضر' || $attendance['status_pm'] !== 'حاضر') {
                $results[] = [
                    'Name' => $name,
                    'Entry AM' => $attendance['entry_am'],
                    'Exit AM' => $attendance['exit_am'],
                    'Status AM' => $attendance['status_am'],
                    'Entry PM' => $attendance['entry_pm'],
                    'Exit PM' => $attendance['exit_pm'],
                    'Status PM' => $attendance['status_pm']
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
    <link rel="stylesheet" href="CSS/checkAttendance.css">
    <link rel="stylesheet" href="CSS/icons.css">
    <style>
        .absent { background-color: #ffdddd; color: #d32f2f; }
        .present { background-color: #ddffdd; color: #388e3c; }
        .late { background-color: #fff3cd; color: #ffa000; }
        .early { background-color: #e3f2fd; color: #1976d2; }
        .no-exit { background-color: #ffecb3; color: #ff6f00; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: center;
            border: 1px solid #ddd;
        }
        th {
            background-color: #f5f5f5;
            font-weight: bold;
            position: relative;
        }
        .time-selection {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        .time-group {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
        }
        .filter-controls {
            margin: 15px 0;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }
        .filter-select {
            padding: 8px 12px;
            border-radius: 4px;
            border: 1px solid #ddd;
            min-width: 150px;
        }
        .filter-btn {
            padding: 8px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .filter-btn:hover {
            background-color: #45a049;
        }
        .reset-btn {
            padding: 8px 15px;
            background-color: #f44336;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .reset-btn:hover {
            background-color: #d32f2f;
        }
        .no-results {
            text-align: center;
            padding: 20px;
            color: #666;
            font-style: italic;
            display: none;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include 'header.php'; ?>

        <main class="dashboard-main">
            <h1 class="dashboard-title">نظام متابعة الحضور</h1>
            
            <form class="upload-form" method="post" enctype="multipart/form-data">
                <div class="time-selection">
                    <div class="time-group">
                        <h3>الفترة الصباحية</h3>
                        <label for="start_time_am"><i class="fas fa-clock"></i> وقت بداية الدوام:</label>
                        <input class="times" type="time" id="start_time_am" name="start_time_am" 
                            value="<?= htmlspecialchars($_POST['start_time_am'] ?? '08:00') ?>" required>
                        
                        <label for="end_time_am"><i class="fas fa-clock"></i> وقت نهاية الدوام:</label>
                        <input class="times" type="time" id="end_time_am" name="end_time_am" 
                            value="<?= htmlspecialchars($_POST['end_time_am'] ?? '12:00') ?>" required>
                    </div>
                    
                    <div class="time-group">
                        <h3>الفترة المسائية</h3>
                        <label for="start_time_pm"><i class="fas fa-clock"></i> وقت بداية الدوام:</label>
                        <input class="times" type="time" id="start_time_pm" name="start_time_pm" 
                            value="<?= htmlspecialchars($_POST['start_time_pm'] ?? '13:00') ?>" required>
                        
                        <label for="end_time_pm"><i class="fas fa-clock"></i> وقت نهاية الدوام:</label>
                        <input class="times" type="time" id="end_time_pm" name="end_time_pm" 
                            value="<?= htmlspecialchars($_POST['end_time_pm'] ?? '16:00') ?>" required>
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

                <!-- Filter controls -->
                <div class="filter-controls">
                    <select id="amFilter" class="filter-select">
                        <option value="">جميع حالات الصباح</option>
                        <option value="حاضر">حاضر</option>
                        <option value="غياب صباحي">غياب صباحي</option>
                        <option value="تأخر">تأخر</option>
                        <option value="خروج مبكر">خروج مبكر</option>
                        <option value="لا يوجد خروج">لا يوجد خروج</option>
                    </select>
                    
                    <select id="pmFilter" class="filter-select">
                        <option value="">جميع حالات المساء</option>
                        <option value="حاضر">حاضر</option>
                        <option value="غياب مسائي">غياب مسائي</option>
                        <option value="تأخر">تأخر</option>
                        <option value="خروج مبكر">خروج مبكر</option>
                        <option value="لا يوجد خروج">لا يوجد خروج</option>
                    </select>
                    
                    <button id="applyFilter" class="filter-btn">تطبيق الفلتر</button>
                    <button id="resetFilter" class="reset-btn">إعادة تعيين</button>
                </div>

                <div class="attendance-table">
                    <table class="table" id="attendanceTable">
                        <thead>
                            <tr>
                                <th>الموظف</th>
                                <th>الدخول الصباحي</th>
                                <th>الخروج الصباحي</th>
                                <th class="status-col">حالة الصباح</th>
                                <th>الدخول المسائي</th>
                                <th>الخروج المسائي</th>
                                <th class="status-col">حالة المساء</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['Name']) ?></td>
                                    <td><?= htmlspecialchars($row['Entry AM']) ?></td>
                                    <td><?= htmlspecialchars($row['Exit AM']) ?></td>
                                    <td class="status-am <?= getStatusClass($row['Status AM']) ?>">
                                        <?= htmlspecialchars($row['Status AM']) ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['Entry PM']) ?></td>
                                    <td><?= htmlspecialchars($row['Exit PM']) ?></td>
                                    <td class="status-pm <?= getStatusClass($row['Status PM']) ?>">
                                        <?= htmlspecialchars($row['Status PM']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div id="noResults" class="no-results">لا توجد نتائج مطابقة لمعايير البحث</div>
                </div>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const table = document.getElementById('attendanceTable');
                    const amFilter = document.getElementById('amFilter');
                    const pmFilter = document.getElementById('pmFilter');
                    const applyBtn = document.getElementById('applyFilter');
                    const resetBtn = document.getElementById('resetFilter');
                    const noResultsMsg = document.getElementById('noResults');
                    const rows = table.querySelectorAll('tbody tr');
                    
                    function filterTable() {
                        const amValue = amFilter.value;
                        const pmValue = pmFilter.value;
                        let visibleRows = 0;
                        
                        rows.forEach(row => {
                            const amStatus = row.querySelector('.status-am').textContent.trim();
                            const pmStatus = row.querySelector('.status-pm').textContent.trim();
                            
                            // Check if status starts with the filter value (to handle cases like "تأخر (5 دقيقة)")
                            const amMatch = !amValue || amStatus.startsWith(amValue);
                            const pmMatch = !pmValue || pmStatus.startsWith(pmValue);
                            
                            if (amMatch && pmMatch) {
                                row.style.display = '';
                                visibleRows++;
                            } else {
                                row.style.display = 'none';
                            }
                        });
                        
                        // Show/hide no results message
                        if (visibleRows === 0) {
                            noResultsMsg.style.display = 'block';
                        } else {
                            noResultsMsg.style.display = 'none';
                        }
                    }
                    
                    // Apply filter when button is clicked
                    applyBtn.addEventListener('click', filterTable);
                    
                    // Reset all filters
                    resetBtn.addEventListener('click', function() {
                        amFilter.value = '';
                        pmFilter.value = '';
                        filterTable();
                    });
                    
                    // Apply filter automatically when dropdown changes
                    amFilter.addEventListener('change', filterTable);
                    pmFilter.addEventListener('change', filterTable);
                    
                    // Initial filter (in case page is reloaded with filters)
                    filterTable();
                });
            </script>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>

<?php
function getStatusClass($status) {
    if (strpos($status, 'غياب') !== false) return 'absent';
    if (strpos($status, 'تأخر') !== false) return 'late';
    if (strpos($status, 'خروج') !== false) return 'early';
    if (strpos($status, 'لا يوجد') !== false) return 'no-exit';
    return 'present';
}
?>