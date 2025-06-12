<?php
session_start();
require 'config/dbconnect.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Get the category from the request
$category = isset($_GET['category']) ? $_GET['category'] : '';

// Initialize result array
$laws = [];

if (!empty($category)) {
    try {
        $pdo = getDBConnection();
        
        // Handle multiple categories with comma
        if (strpos($category, ',') !== false) {
            $categories = explode(',', $category);
            $placeholders = implode(',', array_fill(0, count($categories), '?'));
            
            $sql = "SELECT * FROM laws WHERE law_category IN ($placeholders) AND is_active = 1 ORDER BY law_category";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($categories);
        } else {
            $sql = "SELECT * FROM laws WHERE (law_category = ? OR law_category = 'multiple') AND is_active = 1 ORDER BY law_category";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$category]);
        }
        
        $laws = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $laws = ['error' => $e->getMessage()];
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($laws);