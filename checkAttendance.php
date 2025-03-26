<?php
session_start(); // Start session at the very beginning

// Function to parse time entries and determine status
function checkAttendance($timeEntries) {
    if (empty($timeEntries)) {
        return 'Absent';
    }

    $times = preg_split('/\s+/', trim($timeEntries));
    $times = array_filter($times);
    
    if (empty($times)) {
        return 'Absent';
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

    // Get first and last unique time entries
    $firstEntry = reset($uniqueTimes);
    $lastExit = end($uniqueTimes);

    $status = [];
    
    // Check if late (first entry after 8:00)
    $eightAM = strtotime('08:00');
    $firstEntryTime = strtotime($firstEntry);
    if ($firstEntryTime > $eightAM) {
        $minutesLate = round(($firstEntryTime - $eightAM) / 60);
        $status[] = "Late ($minutesLate minutes)";
    }

    // Check if left early (last exit before 16:00)
    $fourPM = strtotime('16:00');
    $lastExitTime = strtotime($lastExit);
    if ($lastExit === $firstEntry) {
        $status[] = "No exit time";
    } elseif ($lastExitTime < $fourPM) {
        $minutesEarly = round(($fourPM - $lastExitTime) / 60);
        $status[] = "Left early ($minutesEarly minutes)";
    }

    return $status ? implode(', ', $status) : 'Present';
}

// Process uploaded file and handle download
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['download_csv'])) {
        // Download CSV file
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="attendance_report.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Name', 'Entry Time', 'Exit Time', 'Status']);
        
        foreach ($_SESSION['results'] as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit();
    }
    elseif (isset($_FILES['csv_file'])) {
        $file = $_FILES['csv_file']['tmp_name'];
        
        if (($handle = fopen($file, "r")) !== FALSE) {
            // Skip header row
            fgetcsv($handle);
            
            $results = [];
            $date = '';
            
            while (($data = fgetcsv($handle)) !== FALSE) {
                $name = $data[1];
                $date = $data[3];
                $timeEntries = $data[4];

                $times = preg_split('/\s+/', trim($timeEntries));
                $times = array_filter($times);
                
                // Remove consecutive duplicates
                $uniqueTimes = [];
                $prevTime = null;
                foreach ($times as $time) {
                    if ($time !== $prevTime) {
                        $uniqueTimes[] = $time;
                        $prevTime = $time;
                    }
                }
                
                $entryTime = isset($uniqueTimes[0]) ? $uniqueTimes[0] : 'No entry';
                $exitTime = (count($uniqueTimes) > 1) ? end($uniqueTimes) : 'No exit time'; // Fixed syntax
                
                $status = checkAttendance($timeEntries);
                
                if ($status !== 'Present') {
                    $results[] = [
                        'Name' => $name,
                        'Entry Time' => $entryTime,
                        'Exit Time' => $exitTime,
                        'Status' => $status
                    ];
                }
            }
            fclose($handle);
            
            // Store results in session
            $_SESSION['results'] = $results;
            $_SESSION['date'] = $date;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Attendance Checker</title>
    <style>
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .absent { background-color: #ffcccc; }
        .late { background-color: #ffffcc; }
        .early { background-color: #ccffff; }
        .button-container { margin: 20px 0; }
        .download-btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .download-btn:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <h1>Attendance Checker</h1>
    <form method="post" enctype="multipart/form-data">
        <input type="file" name="csv_file" accept=".csv" required>
        <button type="submit">Process File</button>
    </form>

    <?php if (!empty($_SESSION['results'])): ?>
    <div class="button-container">
        <form method="post">
            <button type="submit" name="download_csv" class="download-btn">Download as Excel (CSV)</button>
        </form>
    </div>
    
    <h2>Attendance Issues</h2>
    <h3>Date: <?php echo htmlspecialchars($_SESSION['date']) ?></h3>
    <table>
        <tr>
            <th>Name</th>
            <th>Entry Time</th>
            <th>Exit Time</th>
            <th>Status</th>
        </tr>
        <?php foreach ($_SESSION['results'] as $row): ?>
        <tr class="<?php 
            if (strpos($row['Status'], 'Absent') !== false) echo 'absent';
            elseif (strpos($row['Status'], 'Late') !== false) echo 'late';
            elseif (strpos($row['Status'], 'early') !== false) echo 'early';
        ?>">
            <td><?= htmlspecialchars($row['Name']) ?></td>
            <td><?= htmlspecialchars($row['Entry Time']) ?></td>
            <td><?= htmlspecialchars($row['Exit Time']) ?></td>
            <td><?= htmlspecialchars($row['Status']) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>
</body>
</html>