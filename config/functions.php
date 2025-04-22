<?php
function logActivity($pdo, $employee_id, $activity_type, $details = '', $changed_field = null, $old_value = null, $new_value = null) {
    try {
        // Convert arrays/objects to JSON for storage
        if (is_array($old_value)) $old_value = json_encode($old_value);
        if (is_array($new_value)) $new_value = json_encode($new_value);
        if (is_object($old_value)) $old_value = json_encode($old_value);
        if (is_object($new_value)) $new_value = json_encode($new_value);
        
        // Truncate long values to fit in database
        $details = substr($details, 0, 255);
        if ($changed_field) $changed_field = substr($changed_field, 0, 50);
        if ($old_value) $old_value = substr($old_value, 0, 255);
        if ($new_value) $new_value = substr($new_value, 0, 255);
        
        $stmt = $pdo->prepare("
            INSERT INTO activity_log 
            (employee_id, activity_type, details, changed_by, changed_field, old_value, new_value) 
            VALUES (:employee_id, :activity_type, :details, :changed_by, :changed_field, :old_value, :new_value)
        ");
        
        return $stmt->execute([
            ':employee_id' => $employee_id,
            ':activity_type' => $activity_type,
            ':details' => $details,
            ':changed_by' => $_SESSION['user_id'] ?? null,
            ':changed_field' => $changed_field,
            ':old_value' => $old_value,
            ':new_value' => $new_value
        ]);
    } catch (PDOException $e) {
        error_log("Activity Log Error: " . $e->getMessage());
        return false;
    }
}

function logEmployeeHire($pdo, $employee_id, $employee_name) {
    return logActivity(
        $pdo, 
        $employee_id, 
        'hire', 
        "تم تعيين موظف جديد: $employee_name"
    );
}


function logEmployeeModification($pdo, $employee_id, $old_data, $new_data) {
    $logged = false;
    
    // First log a general modification
    $employee_name = $new_data['firstname_ar'] . ' ' . $new_data['lastname_ar'];
    logActivity($pdo, $employee_id, 'modification', "تم تعديل بيانات $employee_name");
    
    // Track specific field changes
    foreach ($new_data as $field => $new_value) {
        if (array_key_exists($field, $old_data) && $old_data[$field] != $new_value) {
            // Skip internal fields that shouldn't be logged
            if (in_array($field, ['updated_at', 'created_at', 'password'])) continue;
            
            // Special handling for department changes
            if ($field == 'department_id') {
                $old_dept = getDepartmentName($pdo, $old_data['department_id']);
                $new_dept = getDepartmentName($pdo, $new_data['department_id']);
                logActivity(
                    $pdo,
                    $employee_id,
                    'modification',
                    "تم تغيير قسم $employee_name",
                    'department',
                    $old_dept,
                    $new_dept
                );
                $logged = true;
                continue;
            }
            
            // Special handling for position changes
            if ($field == 'position') {
                logActivity(
                    $pdo,
                    $employee_id,
                    'modification',
                    "تم تغيير منصب $employee_name",
                    'position',
                    $old_data['position'],
                    $new_data['position']
                );
                $logged = true;
                continue;
            }
            
            // Special handling for status changes
            if ($field == 'is_active') {
                $old_status = $old_data['is_active'] ? 'نشط' : 'غير نشط';
                $new_status = $new_data['is_active'] ? 'نشط' : 'غير نشط';
                logActivity(
                    $pdo,
                    $employee_id,
                    'modification',
                    "تم تغيير حالة $employee_name",
                    'status',
                    $old_status,
                    $new_status
                );
                $logged = true;
                continue;
            }
            
            // For all other fields
            logActivity(
                $pdo,
                $employee_id,
                'modification',
                "تم تعديل $field لـ $employee_name",
                $field,
                $old_data[$field],
                $new_data[$field]
            );
            $logged = true;
        }
    }
    
    return $logged;
}


function logEmployeePromotion($pdo, $employee_id, $employee_name, $old_position, $new_position) {
    return logActivity(
        $pdo, 
        $employee_id, 
        'promotion', 
        "تم ترقية $employee_name",
        'position',
        $old_position,
        $new_position
    );
}

function logEmployeeDeletion($pdo, $employee_id, $employee_name) {
    return logActivity(
        $pdo, 
        $employee_id, 
        'delete', 
        "تم حذف الموظف: $employee_name"
    );
}

function getDepartmentName($pdo, $department_id) {
    if (!$department_id) return 'غير محدد';
    
    try {
        $stmt = $pdo->prepare("SELECT name FROM departments WHERE department_id = ?");
        $stmt->execute([$department_id]);
        return $stmt->fetchColumn() ?: 'غير محدد';
    } catch (PDOException $e) {
        error_log("Department Name Error: " . $e->getMessage());
        return 'غير محدد';
    }
}


function validatePositionName($pdo, $position_name) {
    try {
        $stmt = $pdo->prepare("SELECT name FROM positions WHERE name = ? LIMIT 1");
        $stmt->execute([$position_name]);
        return $stmt->fetchColumn() ?: $position_name;
    } catch (PDOException $e) {
        error_log("Position Validation Error: " . $e->getMessage());
        return $position_name;
    }
}


function getHighLevelPositionName($pdo, $position_id) {
    try {
        $stmt = $pdo->prepare("SELECT name FROM high_level_positions WHERE position_id = ?");
        $stmt->execute([$position_id]);
        return $stmt->fetchColumn() ?: '';
    } catch (PDOException $e) {
        error_log("High Level Position Error: " . $e->getMessage());
        return '';
    }
}

function getUsername($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetchColumn() ?: '';
    } catch (PDOException $e) {
        error_log("Username Error: " . $e->getMessage());
        return '';
    }
}

function convertDate($date) {
    // Convert date from dd/mm/yyyy to yyyy-mm-dd format
    $date_parts = explode('/', $date);
    if (count($date_parts) == 3) {
        return sprintf('%04d-%02d-%02d', $date_parts[2], $date_parts[1], $date_parts[0]);
    }
    return false;
}
?>