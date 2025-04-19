<?php
session_start();
require_once 'config/dbconnect.php';
$pdo = getDBConnection();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$high_positions = [];
try {
    $stmt = $pdo->query("SELECT * FROM high_level_positions ORDER BY name ASC");
    $high_positions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "خطأ في جلب البيانات: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>قائمة المناصب العليا - GRH Depf</title>
    <link rel="stylesheet" href="CSS/list_employees.css">
    <link rel="stylesheet" href="CSS/icons.css">
    <style>
        /* Same styles as list_positions.php */
        .table-container {
            margin-top: 2rem;
            overflow-x: auto;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            background: #fff;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
        }
        
        thead {
            background-color: #2c3e50;
            color: white;
        }
        
        th, td {
            padding: 15px;
            text-align: center;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        
        tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        tbody tr:hover {
            background-color: #e9f7fe;
            transition: background-color 0.3s ease;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        
        .edit-btn, .delete-btn {
            padding: 5px 10px;
            border-radius: 4px;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.3s ease;
        }
        
        .edit-btn {
            background-color: #3498db;
        }
        
        .edit-btn:hover {
            background-color: #2980b9;
        }
        
        .delete-btn {
            background-color: #e74c3c;
        }
        
        .delete-btn:hover {
            background-color: #c0392b;
        }
        
        .no-data {
            text-align: center;
            padding: 20px;
            color: #7f8c8d;
            font-style: italic;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 25px;
            border-radius: 8px;
            width: 50%;
            max-width: 500px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            animation: modalopen 0.3s;
        }
        
        @keyframes modalopen {
            from {opacity: 0; transform: translateY(-50px);}
            to {opacity: 1; transform: translateY(0);}
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .modal-title {
            font-size: 1.3rem;
            color: #2c3e50;
            margin: 0;
        }
        
        .close-btn {
            color: #aaa;
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        .close-btn:hover {
            color: #333;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #34495e;
        }
        
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        
        .save-btn, .cancel-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .save-btn {
            background-color: #27ae60;
            color: white;
        }
        
        .save-btn:hover {
            background-color: #219653;
        }
        
        .cancel-btn {
            background-color: #e74c3c;
            color: white;
        }
        
        .cancel-btn:hover {
            background-color: #c0392b;
        }
        
        @media (max-width: 768px) {
            th, td {
                padding: 10px 5px;
                font-size: 0.85rem;
            }
            
            .modal-content {
                width: 90%;
                margin: 20% auto;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include 'header.php'; ?>

        <main class="dashboard-main">
            <h1 class="dashboard-title">قائمة المناصب العليا</h1>
            
            <?php if (isset($error)): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th width="5%">#</th>
                            <th width="60%">اسم المنصب العالي</th>
                            <th width="25%">تاريخ الإضافة</th>
                            <th width="10%">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($high_positions)): ?>
                            <tr>
                                <td colspan="4" class="no-data">لا توجد مناصب عليا مسجلة</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($high_positions as $index => $position): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($position['name']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($position['created_at'])) ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="edit-btn" onclick="openEditModal(<?= $position['position_id'] ?>, '<?= htmlspecialchars($position['name'], ENT_QUOTES) ?>')">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="delete-btn" onclick="confirmDelete(<?= $position['position_id'] ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Edit Position Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">تعديل المنصب العالي</h3>
                <span class="close-btn" onclick="closeModal()">&times;</span>
            </div>
            <form id="editForm" method="POST">
                <input type="hidden" id="positionId" name="position_id">
                <div class="form-group">
                    <label for="positionName">اسم المنصب العالي</label>
                    <input type="text" id="positionName" name="name" required>
                </div>
                <div class="modal-actions">
                    <button type="button" class="cancel-btn" onclick="closeModal()">إلغاء</button>
                    <button type="button" class="save-btn" onclick="savePosition()">حفظ التغييرات</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal Functions
        function openEditModal(positionId, positionName) {
            document.getElementById('positionId').value = positionId;
            document.getElementById('positionName').value = positionName;
            document.getElementById('editModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target == modal) {
                closeModal();
            }
        }
        
        // Save Position Function
        function savePosition() {
            const positionId = document.getElementById('positionId').value;
            const positionName = document.getElementById('positionName').value;
            
            if (!positionName.trim()) {
                alert('يرجى إدخال اسم المنصب العالي');
                return;
            }
            
            // Create FormData object
            const formData = new FormData();
            formData.append('position_id', positionId);
            formData.append('name', positionName);
            formData.append('action', 'update_high_position');
            
            // Send AJAX request
            fetch('update_high_position.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('تم تحديث المنصب العالي بنجاح');
                    location.reload(); // Refresh the page to see changes
                } else {
                    alert('حدث خطأ أثناء التحديث: ' + (data.message || ''));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('حدث خطأ أثناء الاتصال بالخادم');
            });
        }
        
        // Delete Position Function
        function confirmDelete(positionId) {
            if (confirm('هل أنت متأكد من حذف هذا المنصب العالي؟')) {
                // Send AJAX request
                fetch('delete_high_position.php?id=' + positionId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('تم حذف المنصب العالي بنجاح');
                        location.reload(); // Refresh the page
                    } else {
                        alert('حدث خطأ أثناء الحذف: ' + (data.message || ''));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('حدث خطأ أثناء الاتصال بالخادم');
                });
            }
        }
    </script>
</body>
</html>