<?php
require_once 'config/dbconnect.php';

header('Content-Type: application/json');

try {
    $pdo = getDBConnection();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Invalid request method');
    }

    if (!isset($_GET['id'])) {
        throw new Exception('Missing position ID');
    }

    $position_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);

    if (!$position_id) {
        throw new Exception('Invalid position ID');
    }

    // Check if position is assigned to any employee
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM employees WHERE position = (SELECT name FROM high_level_positions WHERE position_id = ?)");
    $stmt->execute([$position_id]);
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        throw new Exception('لا يمكن حذف المنصب العالي لأنه مرتبط بموظفين');
    }

    // Delete position
    $stmt = $pdo->prepare("DELETE FROM high_level_positions WHERE position_id = ?");
    $success = $stmt->execute([$position_id]);

    if (!$success) {
        throw new Exception('Failed to delete position');
    }

    echo json_encode([
        'success' => true,
        'message' => 'تم حذف المنصب العالي بنجاح'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>