<<<<<<< HEAD
<?php
header("Content-type: text/html; charset=UTF-8");

// Initialize variables
$docNum = '';
$fullname = '';
$bornPlace = '';
$bornDate = '';
$prevPostes = [];
$actualPoste = ['position' => '', 'start' => ''];
$docDate = date('d/m/Y');

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $docNum = $_POST['docNum'] ?? '';
    $fullname = $_POST['fullname'] ?? '';
    $bornPlace = $_POST['bornPlace'] ?? '';
    $bornDate = $_POST['bornDate'] ?? '';
    $docDate = $_POST['docDate'] ?? date('d/m/Y');
    
    // Process current position
    $actualPoste = [
        'position' => $_POST['actualPoste']['position'] ?? '',
        'start' => $_POST['actualPoste']['start'] ?? ''
    ];
    
    // Process previous positions (optional)
    $prevPostes = [];
    if (isset($_POST['prevPostes']) && is_array($_POST['prevPostes'])) {
        foreach ($_POST['prevPostes'] as $poste) {
            if (!empty($poste['position']) && !empty($poste['start']) && !empty($poste['end'])) {
                $prevPostes[] = [
                    'position' => $poste['position'],
                    'start' => $poste['start'],
                    'end' => $poste['end']
                ];
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>شهادة عمل</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap');
        
        body {
            font-family: 'Amiri', 'Traditional Arabic', serif;
            direction: rtl;
            text-align: center;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .certificate {
            padding: 50px;
            width: 21cm;
            min-height: 29.7cm;
            margin: 20px auto;
            line-height: 2;
            box-sizing: border-box;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            font-weight: bold;
            font-size: 22px;
        }
        .content {
            font-size: 20px;
            text-align: right;
            margin-right: 50px;
        }
        h1 {
            font-size: 26px;
            font-weight: bold;
            margin: 30px 0;
        }
        .signature {
            text-align: left;
            margin-top: 50px;
        }
        .docnum {
            text-align: right;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 30px;
        }
        .position-item {
            margin-right: 20px;
        }
        
        /* Print-specific styles */
        @media print {
            @page {
                size: A4;
                margin: 0;
            }
            body {
                background: none;
                margin: 0;
                padding: 0;
            }
            .certificate {
                box-shadow: none;
                margin: 0;
                padding: 2cm;
                width: auto;
                height: auto;
                page-break-after: always;
            }
            .no-print {
                display: none;
            }
        }
        
        /* Form styling */
        .form-container {
            width: 80%;
            margin: 20px auto;
            padding: 20px;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
            text-align: right;
        }
        label {
            display: inline-block;
            width: 200px;
        }
        input, select {
            padding: 8px;
            width: 300px;
        }
        button {
            padding: 10px 20px;
            background: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        .prev-poste-container {
            margin-bottom: 15px;
            border: 1px dashed #ccc;
            padding: 10px;
        }
        .add-prev-poste {
            background: #2196F3;
            margin-bottom: 20px;
        }
        .remove-prev-poste {
            background: #f44336;
        }
    </style>
</head>
<body>
    <!-- Form for data entry -->
    <div class="form-container no-print">
        <h2>إدخال بيانات الشهادة</h2>
        <form method="post" action="">
            <!-- Basic Information -->
            <div class="form-group">
                <label for="docNum">رقم الوثيقة:</label>
                <input type="text" id="docNum" name="docNum" value="<?= htmlspecialchars($docNum) ?>" required>
            </div>
            <div class="form-group">
                <label for="fullname">الاسم الكامل:</label>
                <input type="text" id="fullname" name="fullname" value="<?= htmlspecialchars($fullname) ?>" required>
            </div>
            <div class="form-group">
                <label for="bornDate">تاريخ الازدياد:</label>
                <input type="text" id="bornDate" name="bornDate" value="<?= htmlspecialchars($bornDate) ?>" required placeholder="DD/MM/YYYY">
            </div>
            <div class="form-group">
                <label for="bornPlace">مكان الازدياد:</label>
                <input type="text" id="bornPlace" name="bornPlace" value="<?= htmlspecialchars($bornPlace) ?>" required>
            </div>
            
            <!-- Current Position (Required) -->
            <h3>المنصب الحالي</h3>
            <div class="form-group">
                <label for="actualPoste">المنصب:</label>
                <input type="text" id="actualPoste" name="actualPoste[position]" value="<?= htmlspecialchars($actualPoste['position']) ?>" required>
            </div>
            <div class="form-group">
                <label for="actualStartDate">تاريخ البدء:</label>
                <input type="text" id="actualStartDate" name="actualPoste[start]" value="<?= htmlspecialchars($actualPoste['start']) ?>" required placeholder="DD/MM/YYYY">
            </div>
            
            <!-- Previous Positions (Optional) -->
            <h3>المناصب السابقة (اختياري)</h3>
            <div id="prevPostesContainer">
                <?php foreach($prevPostes as $index => $poste): ?>
                <div class="prev-poste-container">
                    <div class="form-group">
                        <label>المنصب:</label>
                        <input type="text" name="prevPostes[<?= $index ?>][position]" value="<?= htmlspecialchars($poste['position']) ?>">
                    </div>
                    <div class="form-group">
                        <label>تاريخ البدء:</label>
                        <input type="text" name="prevPostes[<?= $index ?>][start]" value="<?= htmlspecialchars($poste['start']) ?>" placeholder="DD/MM/YYYY">
                    </div>
                    <div class="form-group">
                        <label>تاريخ الانتهاء:</label>
                        <input type="text" name="prevPostes[<?= $index ?>][end]" value="<?= htmlspecialchars($poste['end']) ?>" placeholder="DD/MM/YYYY">
                    </div>
                    <button type="button" class="remove-prev-poste">إزالة</button>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" id="addPrevPoste" class="add-prev-poste">إضافة منصب سابق</button>
            
            <!-- Document Date -->
            <div class="form-group">
                <label for="docDate">تاريخ الوثيقة:</label>
                <input type="text" id="docDate" name="docDate" value="<?= htmlspecialchars($docDate) ?>" required placeholder="DD/MM/YYYY">
            </div>
            
            <!-- Form Buttons -->
            <button type="submit">إنشاء الشهادة</button>
            <button type="button" onclick="window.print()" style="background: #2196F3;">طباعة الشهادة</button>
        </form>
    </div>

    <!-- Certificate Preview -->
    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
    <div class="certificate">
        <div class="header">الجمهوريـــــة الجزائريـــــة الديمقراطيـــــة الشعبيـــــة</div>
        <div class="header">وزارة التكوين والتعليم المهنيين</div>
        <div class="header">مديرية التكوين والتعليم المهنيين لولاية قالمة</div>
        <br>
        <div class="docnum">رقم: 2025/<?= htmlspecialchars($docNum) ?></div>
        <h1>شهادة عمل</h1>
        <div class="content">
            أنـا الممضي أسفله السيـد/ مدير التكويـن المهنـي لولاية قالمـة، أشهــد أن:
            <br><br>
            <strong>السيد:</strong> <?= htmlspecialchars($fullname) ?>
            <br>
            <strong>تاريـخ ومكـان الازديــاد:</strong> <?= htmlspecialchars($bornDate) ?>، <?= htmlspecialchars($bornPlace) ?>
            <br><br>
            <strong>شغل لدى مصالحي المناصب التالية:</strong>
            <br>
            
            <!-- Previous Positions (Optional) -->
            <?php if(!empty($prevPostes)): ?>
                <?php foreach($prevPostes as $poste): ?>
                <div class="position-item">
                    - <?= htmlspecialchars($poste['position']) ?>، من <?= htmlspecialchars($poste['start']) ?> إلى غاية <?= htmlspecialchars($poste['end']) ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <!-- Current Position (Always shown) -->
            <div class="position-item">
                - <?= htmlspecialchars($actualPoste['position']) ?>، من <?= htmlspecialchars($actualPoste['start']) ?> إلى غاية يومنا هذا.
            </div>
            
            <strong>سلمـت هـذه الشهـادة للمعني بالأمـر، بطلـب منـه، لاستعمالـها فـي حـدود مـا يسمـح بـه القانـون.</strong>
            <br><br>
            <p class="signature">حرر بقالمة في <?= htmlspecialchars($docDate) ?></p>
        </div>
    </div>
    <?php endif; ?>

    <script>
        // Add new previous position field
        document.getElementById('addPrevPoste').addEventListener('click', function() {
            const container = document.getElementById('prevPostesContainer');
            const index = container.children.length;
            
            const div = document.createElement('div');
            div.className = 'prev-poste-container';
            div.innerHTML = `
                <div class="form-group">
                    <label>المنصب:</label>
                    <input type="text" name="prevPostes[${index}][position]">
                </div>
                <div class="form-group">
                    <label>تاريخ البدء:</label>
                    <input type="text" name="prevPostes[${index}][start]" placeholder="DD/MM/YYYY">
                </div>
                <div class="form-group">
                    <label>تاريخ الانتهاء:</label>
                    <input type="text" name="prevPostes[${index}][end]" placeholder="DD/MM/YYYY">
                </div>
                <button type="button" class="remove-prev-poste">إزالة</button>
            `;
            
            container.appendChild(div);
        });
        
        // Remove previous position field
        document.addEventListener('click', function(e) {
            if(e.target.classList.contains('remove-prev-poste')) {
                e.target.closest('.prev-poste-container').remove();
            }
        });
        
        // Focus on first field
        document.getElementById('docNum').focus();
    </script>
</body>
=======
<?php
header("Content-type: text/html; charset=UTF-8");

// Initialize variables
$docNum = '';
$fullname = '';
$bornPlace = '';
$bornDate = '';
$prevPostes = [];
$actualPoste = ['position' => '', 'start' => ''];
$docDate = date('d/m/Y');

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $docNum = $_POST['docNum'] ?? '';
    $fullname = $_POST['fullname'] ?? '';
    $bornPlace = $_POST['bornPlace'] ?? '';
    $bornDate = $_POST['bornDate'] ?? '';
    $docDate = $_POST['docDate'] ?? date('d/m/Y');
    
    // Process current position
    $actualPoste = [
        'position' => $_POST['actualPoste']['position'] ?? '',
        'start' => $_POST['actualPoste']['start'] ?? ''
    ];
    
    // Process previous positions (optional)
    $prevPostes = [];
    if (isset($_POST['prevPostes']) && is_array($_POST['prevPostes'])) {
        foreach ($_POST['prevPostes'] as $poste) {
            if (!empty($poste['position']) && !empty($poste['start']) && !empty($poste['end'])) {
                $prevPostes[] = [
                    'position' => $poste['position'],
                    'start' => $poste['start'],
                    'end' => $poste['end']
                ];
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>شهادة عمل</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap');
        
        body {
            font-family: 'Amiri', 'Traditional Arabic', serif;
            direction: rtl;
            text-align: center;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .certificate {
            padding: 50px;
            width: 21cm;
            min-height: 29.7cm;
            margin: 20px auto;
            line-height: 2;
            box-sizing: border-box;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            font-weight: bold;
            font-size: 22px;
        }
        .content {
            font-size: 20px;
            text-align: right;
            margin-right: 50px;
        }
        h1 {
            font-size: 26px;
            font-weight: bold;
            margin: 30px 0;
        }
        .signature {
            text-align: left;
            margin-top: 50px;
        }
        .docnum {
            text-align: right;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 30px;
        }
        .position-item {
            margin-right: 20px;
        }
        
        /* Print-specific styles */
        @media print {
            @page {
                size: A4;
                margin: 0;
            }
            body {
                background: none;
                margin: 0;
                padding: 0;
            }
            .certificate {
                box-shadow: none;
                margin: 0;
                padding: 2cm;
                width: auto;
                height: auto;
                page-break-after: always;
            }
            .no-print {
                display: none;
            }
        }
        
        /* Form styling */
        .form-container {
            width: 80%;
            margin: 20px auto;
            padding: 20px;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
            text-align: right;
        }
        label {
            display: inline-block;
            width: 200px;
        }
        input, select {
            padding: 8px;
            width: 300px;
        }
        button {
            padding: 10px 20px;
            background: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        .prev-poste-container {
            margin-bottom: 15px;
            border: 1px dashed #ccc;
            padding: 10px;
        }
        .add-prev-poste {
            background: #2196F3;
            margin-bottom: 20px;
        }
        .remove-prev-poste {
            background: #f44336;
        }
    </style>
</head>
<body>
    <!-- Form for data entry -->
    <div class="form-container no-print">
        <h2>إدخال بيانات الشهادة</h2>
        <form method="post" action="">
            <!-- Basic Information -->
            <div class="form-group">
                <label for="docNum">رقم الوثيقة:</label>
                <input type="text" id="docNum" name="docNum" value="<?= htmlspecialchars($docNum) ?>" required>
            </div>
            <div class="form-group">
                <label for="fullname">الاسم الكامل:</label>
                <input type="text" id="fullname" name="fullname" value="<?= htmlspecialchars($fullname) ?>" required>
            </div>
            <div class="form-group">
                <label for="bornDate">تاريخ الازدياد:</label>
                <input type="text" id="bornDate" name="bornDate" value="<?= htmlspecialchars($bornDate) ?>" required placeholder="DD/MM/YYYY">
            </div>
            <div class="form-group">
                <label for="bornPlace">مكان الازدياد:</label>
                <input type="text" id="bornPlace" name="bornPlace" value="<?= htmlspecialchars($bornPlace) ?>" required>
            </div>
            
            <!-- Current Position (Required) -->
            <h3>المنصب الحالي</h3>
            <div class="form-group">
                <label for="actualPoste">المنصب:</label>
                <input type="text" id="actualPoste" name="actualPoste[position]" value="<?= htmlspecialchars($actualPoste['position']) ?>" required>
            </div>
            <div class="form-group">
                <label for="actualStartDate">تاريخ البدء:</label>
                <input type="text" id="actualStartDate" name="actualPoste[start]" value="<?= htmlspecialchars($actualPoste['start']) ?>" required placeholder="DD/MM/YYYY">
            </div>
            
            <!-- Previous Positions (Optional) -->
            <h3>المناصب السابقة (اختياري)</h3>
            <div id="prevPostesContainer">
                <?php foreach($prevPostes as $index => $poste): ?>
                <div class="prev-poste-container">
                    <div class="form-group">
                        <label>المنصب:</label>
                        <input type="text" name="prevPostes[<?= $index ?>][position]" value="<?= htmlspecialchars($poste['position']) ?>">
                    </div>
                    <div class="form-group">
                        <label>تاريخ البدء:</label>
                        <input type="text" name="prevPostes[<?= $index ?>][start]" value="<?= htmlspecialchars($poste['start']) ?>" placeholder="DD/MM/YYYY">
                    </div>
                    <div class="form-group">
                        <label>تاريخ الانتهاء:</label>
                        <input type="text" name="prevPostes[<?= $index ?>][end]" value="<?= htmlspecialchars($poste['end']) ?>" placeholder="DD/MM/YYYY">
                    </div>
                    <button type="button" class="remove-prev-poste">إزالة</button>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" id="addPrevPoste" class="add-prev-poste">إضافة منصب سابق</button>
            
            <!-- Document Date -->
            <div class="form-group">
                <label for="docDate">تاريخ الوثيقة:</label>
                <input type="text" id="docDate" name="docDate" value="<?= htmlspecialchars($docDate) ?>" required placeholder="DD/MM/YYYY">
            </div>
            
            <!-- Form Buttons -->
            <button type="submit">إنشاء الشهادة</button>
            <button type="button" onclick="window.print()" style="background: #2196F3;">طباعة الشهادة</button>
        </form>
    </div>

    <!-- Certificate Preview -->
    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
    <div class="certificate">
        <div class="header">الجمهوريـــــة الجزائريـــــة الديمقراطيـــــة الشعبيـــــة</div>
        <div class="header">وزارة التكوين والتعليم المهنيين</div>
        <div class="header">مديرية التكوين والتعليم المهنيين لولاية قالمة</div>
        <br>
        <div class="docnum">رقم: 2025/<?= htmlspecialchars($docNum) ?></div>
        <h1>شهادة عمل</h1>
        <div class="content">
            أنـا الممضي أسفله السيـد/ مدير التكويـن المهنـي لولاية قالمـة، أشهــد أن:
            <br><br>
            <strong>السيد:</strong> <?= htmlspecialchars($fullname) ?>
            <br>
            <strong>تاريـخ ومكـان الازديــاد:</strong> <?= htmlspecialchars($bornDate) ?>، <?= htmlspecialchars($bornPlace) ?>
            <br><br>
            <strong>شغل لدى مصالحي المناصب التالية:</strong>
            <br>
            
            <!-- Previous Positions (Optional) -->
            <?php if(!empty($prevPostes)): ?>
                <?php foreach($prevPostes as $poste): ?>
                <div class="position-item">
                    - <?= htmlspecialchars($poste['position']) ?>، من <?= htmlspecialchars($poste['start']) ?> إلى غاية <?= htmlspecialchars($poste['end']) ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <!-- Current Position (Always shown) -->
            <div class="position-item">
                - <?= htmlspecialchars($actualPoste['position']) ?>، من <?= htmlspecialchars($actualPoste['start']) ?> إلى غاية يومنا هذا.
            </div>
            
            <strong>سلمـت هـذه الشهـادة للمعني بالأمـر، بطلـب منـه، لاستعمالـها فـي حـدود مـا يسمـح بـه القانـون.</strong>
            <br><br>
            <p class="signature">حرر بقالمة في <?= htmlspecialchars($docDate) ?></p>
        </div>
    </div>
    <?php endif; ?>

    <script>
        // Add new previous position field
        document.getElementById('addPrevPoste').addEventListener('click', function() {
            const container = document.getElementById('prevPostesContainer');
            const index = container.children.length;
            
            const div = document.createElement('div');
            div.className = 'prev-poste-container';
            div.innerHTML = `
                <div class="form-group">
                    <label>المنصب:</label>
                    <input type="text" name="prevPostes[${index}][position]">
                </div>
                <div class="form-group">
                    <label>تاريخ البدء:</label>
                    <input type="text" name="prevPostes[${index}][start]" placeholder="DD/MM/YYYY">
                </div>
                <div class="form-group">
                    <label>تاريخ الانتهاء:</label>
                    <input type="text" name="prevPostes[${index}][end]" placeholder="DD/MM/YYYY">
                </div>
                <button type="button" class="remove-prev-poste">إزالة</button>
            `;
            
            container.appendChild(div);
        });
        
        // Remove previous position field
        document.addEventListener('click', function(e) {
            if(e.target.classList.contains('remove-prev-poste')) {
                e.target.closest('.prev-poste-container').remove();
            }
        });
        
        // Focus on first field
        document.getElementById('docNum').focus();
    </script>
</body>
>>>>>>> 5984689475bbf7bd3130afd333533dfa00013697
</html>