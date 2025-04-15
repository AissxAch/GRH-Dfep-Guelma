<?php
session_start();
require_once 'config/dbconnect.php';
$pdo = getDBConnection();

if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'message' => 'غير مصرح به']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_position') {
        try {
            $positionId = $_POST['position_id'];
            $name = trim($_POST['name']);
            
            if (empty($name)) {
                throw new Exception('اسم الرتبة مطلوب');
            }
            
            $stmt = $pdo->prepare("UPDATE positions SET name = ? WHERE position_id = ?");
            $stmt->execute([$name, $positionId]);
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'إجراء غير معروف']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'طلب غير صالح']);
}
?>