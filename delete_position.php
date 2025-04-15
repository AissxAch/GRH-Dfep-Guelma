<?php
session_start();
require_once 'config/dbconnect.php';
$pdo = getDBConnection();

if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'message' => 'غير مصرح به']);
    exit();
}

if (isset($_GET['id'])) {
    try {
        $positionId = $_GET['id'];
        
        // Check if position is being used by any employee
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM employees WHERE position = (SELECT name FROM positions WHERE position_id = ?)");
        $stmt->execute([$positionId]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            echo json_encode(['success' => false, 'message' => 'لا يمكن حذف الرتبة لأنها مستخدمة من قبل موظفين']);
            exit();
        }
        
        $stmt = $pdo->prepare("DELETE FROM positions WHERE position_id = ?");
        $stmt->execute([$positionId]);
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'معرف الرتبة غير محدد']);
}
?>