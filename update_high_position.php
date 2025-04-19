<?php
require_once 'config/dbconnect.php';

header('Content-Type: application/json');

try {
    $pdo = getDBConnection();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    
    if (!isset($data['position_id'], $data['name'], $data['action']) || $data['action'] !== 'update_high_position') {
        throw new Exception('Missing required fields');
    }

    $position_id = filter_var($data['position_id'], FILTER_VALIDATE_INT);
    $name = trim(filter_var($data['name'], FILTER_SANITIZE_STRING));

    if (!$position_id || empty($name)) {
        throw new Exception('Invalid input data');
    }

    // Check if position already exists
    $stmt = $pdo->prepare("SELECT position_id FROM high_level_positions WHERE name = ? AND position_id != ?");
    $stmt->execute([$name, $position_id]);
    if ($stmt->fetch()) {
        throw new Exception('هذا المنصب العالي مسجل مسبقاً');
    }

    // Update position
    $stmt = $pdo->prepare("UPDATE high_level_positions SET name = ? WHERE position_id = ?");
    $success = $stmt->execute([$name, $position_id]);

    if (!$success) {
        throw new Exception('Failed to update position');
    }

    echo json_encode([
        'success' => true,
        'message' => 'تم تحديث المنصب العالي بنجاح'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>