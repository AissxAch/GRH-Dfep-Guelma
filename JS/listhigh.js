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