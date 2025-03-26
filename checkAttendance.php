<?php
// Function to parse time entries and determine status
function checkAttendance($timeEntries) {
    if (empty($timeEntries)) {
        return 'Absent';
    }

    $times = preg_split('/\s+/', trim($timeEntries));
    $times = array_filter($times); // Remove empty entries
    
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

// Process uploaded file
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];
    
    if (($handle = fopen($file, "r")) !== FALSE) {
        // Skip header row
        fgetcsv($handle);
        
        $results = [];
        $date = ''; // Initialize date variable
        
        while (($data = fgetcsv($handle)) !== FALSE) {
            $name = $data[1];
            $date = $data[3]; // Store date from each row
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
            $exitTime = isset($uniqueTimes[1]) ? end($uniqueTimes) : 'No exit time';
            
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
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Attendance Checker</title>
    <link rel="stylesheet" href="CSS/checkAttendance.css">
</head>
<body>
    <h1>Attendance Checker</h1>
    <form class="uploadcsv"  method="post" enctype="multipart/form-data">
        <input type="file" name="csv_file" accept=".csv" required>
        <button class="btndl1"  type="submit">Process File</button>
    </form>

    <?php if (!empty($results)): ?>
    <h2>Attendance Issues</h2>
    <h3>Date: <?php echo htmlspecialchars($date) ?></h3>
    <table>
        <tr>
            <th>Name</th>
            <th>Entry Time</th>
            <th>Exit Time</th>
            <th>Status</th>
        </tr>
        <?php foreach ($results as $row): ?>
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