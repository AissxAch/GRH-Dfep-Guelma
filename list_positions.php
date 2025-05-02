<?php
session_start();
require_once 'config/dbconnect.php';
$pdo = getDBConnection();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$positions = [];
try {
    $stmt = $pdo->query("SELECT * FROM positions ORDER BY name ASC");
    $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "خطأ في جلب البيانات: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>قائمة الرتب - GRH Depf</title>
    <link rel="stylesheet" href="CSS/icons.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="CSS/list.css">
</head>

<body>
    <div class="dashboard-container">
        <?php include 'header.php'; ?>

        <main class="dashboard-main">
            <h1 class="dashboard-title">قائمة الرتب</h1>

            <?php if (isset($error)): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th width="5%">#</th>
                            <th width="60%">اسم الرتبة</th>
                            <th width="25%">تاريخ الإضافة</th>
                            <th width="10%">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($positions)): ?>
                            <tr>
                                <td colspan="4" class="no-data">لا توجد رتب مسجلة</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($positions as $index => $position): ?>
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
                <h3 class="modal-title">تعديل الرتبة</h3>
                <span class="close-btn" onclick="closeModal()">&times;</span>
            </div>
            <form id="editForm" method="POST">
                <input type="hidden" id="positionId" name="position_id">
                <div class="form-group">
                    <label for="positionName">اسم الرتبة</label>
                    <input type="text" id="positionName" name="name" required>
                </div>
                <div class="modal-actions">
                    <button type="button" class="cancel-btn" onclick="closeModal()">إلغاء</button>
                    <button type="button" class="save-btn" onclick="savePosition()">حفظ التغييرات</button>
                </div>
            </form>
        </div>
    </div>

    <script src="JS/lists.js"></script>
</body>

</html>