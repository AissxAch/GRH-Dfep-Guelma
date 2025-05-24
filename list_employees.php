<?php
session_start();
require 'config/dbconnect.php';
$pdo = getDBConnection();

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}


try {
    $sql = "SELECT * FROM employees ORDER BY employee_id ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("خطأ في جلب البيانات: " . $e->getMessage());
}

// Function to format date in DD/MM/YYYY format
function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

// Function to generate full Arabic name
function getFullNameAr($employee) {
    return $employee['firstname_ar'] . ' ' . $employee['lastname_ar'];
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>قائمة الموظفين - GRH Depf</title>
    <link rel="stylesheet" href="CSS/employee.css">
    <link rel="stylesheet" href="CSS/icons.css">
    <link rel="stylesheet" href="CSS/style.css">
    
</head>
<body>
    <div class="dashboard-container">
        <?php include 'header.php'; ?>

        <main class="dashboard-main">
            <h1 class="dashboard-title">قائمة الموظفين</h1>
            
            <?php if (isset($error)): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if (isset($success)): ?>
                <div class="success-message">
                    <i class="fas fa-check"></i>
                    <p><?= htmlspecialchars($success) ?></p>
                </div>
            <?php endif; ?>

            <div class="employee-actions">
                <a href="add_employee.php" class="action-button edit-button">
                    <i class="fas fa-user-plus"></i> إضافة موظف جديد
                </a>
                <div class="search-form">
                    <div class="input-group">
                        <input type="text" id="searchInput" placeholder="ابحث بالاسم أو الرقم الوطني" 
                               onkeyup="searchEmployees()">
                    </div>
                </div>
            </div>

            <div class="employees-list">
                <?php if (empty($employees)): ?>
                    <div class="no-results">
                        <i class="fas fa-user-slash"></i>
                        <p>لا توجد نتائج</p>
                    </div>
                <?php else: ?>
                    <div class="responsive-table">
                        <table class="employees-table" id="employeesTable">
                            <thead>
                                <tr>
                                    <th>الرقم الوظيفي</th>
                                    <th>الاسم الكامل</th>
                                    <th>منصب الشغل</th>
                                    <th>الرقم الوطني</th>
                                    <th>تاريخ التعيين</th>
                                    <th>تاريخ التنصيب</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody id="employeesTableBody">
                                <?php foreach ($employees as $employee): ?>
                                    <tr>
                                        <td data-label="الرقم الوظيفي"><?= htmlspecialchars($employee['employee_id']) ?></td>
                                        <td data-label="الاسم"><?= htmlspecialchars(getFullNameAr($employee)) ?></td>
                                        <td data-label="الوظيفة"><?= htmlspecialchars($employee['position']) ?></td>
                                        <td data-label="الرقم الوطني"><?= htmlspecialchars($employee['national_id']) ?></td>
                                        <td data-label="تاريخ التعيين">
                                            <?= formatDate($employee['hire_date']) ?>
                                        </td>
                                        <td data-label="تاريخ التنصيب">
                                            <?= $employee['first_hire_date'] ? formatDate($employee['first_hire_date']) : 'غير محدد' ?>
                                        </td>
                                        <td data-label="الإجراءات" class="actions-cell">
                                            <a href="employee.php?id=<?= $employee['employee_id'] ?>" 
                                               class="view-btn" title="عرض الملف">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit_employee.php?id=<?= $employee['employee_id'] ?>" 
                                               class="edit-btn" title="تعديل">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="promote_employee.php?id=<?= $employee['employee_id'] ?>" 
                                               class="edit-btn" title="ترقية">
                                                <i class="fas fa-arrow-up"></i>
                                            </a>
                                            <a href="extract.php?id=<?= $employee['employee_id'] ?>" 
                                               class="documents-btn" title="إستخراج الملف">
                                                <i class="fas fa-file-export"></i>
                                            </a>
                                            <a href="document.php?id=<?= $employee['employee_id'] ?>" 
                                               class="documents-btn" title="عرض المستندات">
                                                <i class="fas fa-file-alt"></i>
                                            </a>
                                            <a href="delete_employee.php?id=<?= $employee['employee_id'] ?>"class="delete-btn" title="حذف">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        // Store all employees data for client-side filtering
        const allEmployees = <?= json_encode(array_map(function($employee) {
            return [
                'employee_id' => $employee['employee_id'],
                'firstname_ar' => $employee['firstname_ar'],
                'lastname_ar' => $employee['lastname_ar'],
                'position' => $employee['position'],
                'national_id' => $employee['national_id'],
                'hire_date' => $employee['hire_date']
            ];
        }, $employees)) ?>;
        
        function searchEmployees() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toUpperCase();
            const tableBody = document.getElementById('employeesTableBody');
            
            // Clear existing table rows
            tableBody.innerHTML = '';
            
            // Filter employees based on search input
            const filteredEmployees = allEmployees.filter(employee => {
                const fullNameAr = (employee.firstname_ar + ' ' + employee.lastname_ar).toUpperCase();
                return (
                    fullNameAr.includes(filter) ||
                    employee.national_id.includes(filter) ||
                    employee.position.toUpperCase().includes(filter) ||
                    employee.employee_id.toString().includes(filter)
                );
            });
            
            // Display filtered results
            if (filteredEmployees.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="6" style="text-align: center;">
                            <div class="no-results">
                                <i class="fas fa-user-slash"></i>
                                <p>لا توجد نتائج مطابقة</p>
                            </div>
                        </td>
                    </tr>
                `;
            } else {
                filteredEmployees.forEach(employee => {
                    const row = document.createElement('tr');
                    
                    // Format hire date in DD/MM/YYYY format
                    const hireDate = new Date(employee.hire_date);
                    const day = String(hireDate.getDate()).padStart(2, '0');
                    const month = String(hireDate.getMonth() + 1).padStart(2, '0');
                    const year = hireDate.getFullYear();
                    const formattedDate = `${day}/${month}/${year}`;
                    
                    row.innerHTML = `
                        <td data-label="الرقم الوظيفي">${employee.employee_id}</td>
                        <td data-label="الاسم">${escapeHtml(employee.firstname_ar + ' ' + employee.lastname_ar)}</td>
                        <td data-label="الوظيفة">${escapeHtml(employee.position)}</td>
                        <td data-label="الرقم الوطني">${employee.national_id}</td>
                        <td data-label="تاريخ التعيين">${formattedDate}</td>
                        <td data-label="الإجراءات" class="actions-cell">
                            <a href="employee.php?id=${employee.employee_id}" 
                               class="view-btn" title="عرض الملف">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="edit_employee.php?id=${employee.employee_id}" 
                               class="edit-btn" title="تعديل">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="extract.php?id=${employee.employee_id}" 
                               class="documents-btn" title="إستخراج الملف">
                                <i class="fas fa-file-export"></i>
                            </a>
                            <a href="document.php?id=${employee.employee_id}" 
                               class="documents-btn" title="عرض المستندات">
                                <i class="fas fa-file-alt"></i>
                            </a>
                            <a href="list_employees.php?delete=${employee.employee_id}" 
                               class="delete-btn" title="حذف"
                               onclick="return confirm('هل أنت متأكد من حذف هذا الموظف؟');">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    `;
                    
                    tableBody.appendChild(row);
                });
            }
        }
        
        // Helper function to escape HTML
        function escapeHtml(unsafe) {
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }
        
        // Search when Enter key is pressed
        document.getElementById('searchInput').addEventListener('keyup', function(event) {
            if (event.key === 'Enter') {
                searchEmployees();
            }
        });
    </script>
</body>
</html>