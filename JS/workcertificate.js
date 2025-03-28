// Add new previous position field
document.getElementById('addPrevPoste').addEventListener('click', function() {
    const container = document.getElementById('prevPostesContainer');
    const index = container.children.length;
    
    const div = document.createElement('div');
    div.className = 'prev-poste-container';
    div.innerHTML = `
        <div class="form-group">
            <label>المنصب:</label>
            <input type="text" name="prevPostes[${index}][position]">
        </div>
        <div class="form-group">
            <label>تاريخ البدء:</label>
            <input type="text" name="prevPostes[${index}][start]" placeholder="DD/MM/YYYY">
        </div>
        <div class="form-group">
            <label>تاريخ الانتهاء:</label>
            <input type="text" name="prevPostes[${index}][end]" placeholder="DD/MM/YYYY">
        </div>
        <button type="button" class="remove-prev-poste">إزالة</button>
    `;
    
    container.appendChild(div);
});

// Remove previous position field
document.addEventListener('click', function(e) {
    if(e.target.classList.contains('remove-prev-poste')) {
        e.target.closest('.prev-poste-container').remove();
    }
});

// Focus on first field
document.getElementById('docNum').focus();