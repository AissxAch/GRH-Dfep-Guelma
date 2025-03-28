// Function to validate date format
function isValidDateFormat(dateString) {
    const regex = /^\d{2}\/\d{2}\/\d{4}$/;
    return regex.test(dateString);
}

// Add new previous position field
document.getElementById('addPrevPoste').addEventListener('click', function() {
    const container = document.getElementById('prevPostesContainer');
    const index = container.children.length;
    
    const div = document.createElement('div');
    div.className = 'prev-poste-container';
    div.innerHTML = `
        <div class="form-group">
            <label>المنصب:</label>
            <input type="text" name="prevPostes[${index}][position]" required>
        </div>
        <div class="form-group">
            <label>تاريخ البدء:</label>
            <input type="text" name="prevPostes[${index}][start]" placeholder="DD/MM/YYYY" required 
                   pattern="\\d{2}/\\d{2}/\\d{4}"
                   oninput="this.setCustomValidity(isValidDateFormat(this.value) ? '' : 'الرجاء إدخال التاريخ بتنسيق DD/MM/YYYY')"
                   oninvalid="this.setCustomValidity('الرجاء إدخال التاريخ بتنسيق DD/MM/YYYY')">
        </div>
        <div class="form-group">
            <label>تاريخ الانتهاء:</label>
            <input type="text" name="prevPostes[${index}][end]" placeholder="DD/MM/YYYY" required
                   pattern="\\d{2}/\\d{2}/\\d{4}"
                   oninput="this.setCustomValidity(isValidDateFormat(this.value) ? '' : 'الرجاء إدخال التاريخ بتنسيق DD/MM/YYYY')"
                   oninvalid="this.setCustomValidity('الرجاء إدخال التاريخ بتنسيق DD/MM/YYYY')">
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

// Date validation helper for client-side validation
function validateDates() {
    const containers = document.querySelectorAll('.prev-poste-container');
    
    for (let container of containers) {
        const positionInput = container.querySelector('input[name$="[position]"]');
        const startInput = container.querySelector('input[name$="[start]"]');
        const endInput = container.querySelector('input[name$="[end]"]');
        
        // Skip validation for completely empty rows
        if (!positionInput.value.trim() && !startInput.value.trim() && !endInput.value.trim()) {
            continue;
        }
        
        // Validate start and end dates
        const startDate = parseDate(startInput.value);
        const endDate = parseDate(endInput.value);
        
        if (!startDate || !endDate || startDate >= endDate) {
            alert('تاريخ البدء يجب أن يكون قبل تاريخ الانتهاء');
            return false;
        }
    }
    
    return true;
}

// Helper function to parse date string
function parseDate(dateString) {
    if (!isValidDateFormat(dateString)) return null;
    
    const [day, month, year] = dateString.split('/').map(Number);
    return new Date(year, month - 1, day);
}

// Attach validation to form submission
document.querySelector('form').addEventListener('submit', function(e) {
    if (!validateDates()) {
        e.preventDefault();
    }
});
// Add client-side validation for dates
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    
    form.addEventListener('submit', function(e) {
        const prevPostesContainer = document.getElementById('prevPostesContainer');
        const prevPosteInputs = prevPostesContainer.querySelectorAll('.prev-poste-container');
        
        for (let container of prevPosteInputs) {
            const position = container.querySelector('input[name$="[position]"]').value.trim();
            const start = container.querySelector('input[name$="[start]"]').value.trim();
            const end = container.querySelector('input[name$="[end]"]').value.trim();
            
            // Skip empty rows
            if (!position && !start && !end) continue;
            
            // Validate non-empty fields
            if (!position || !start || !end) {
                alert('يرجى ملء جميع الحقول للمناصب السابقة');
                e.preventDefault();
                return;
            }
            
            // Basic date format validation
            const dateRegex = /^\d{2}\/\d{2}\/\d{4}$/;
            if (!dateRegex.test(start) || !dateRegex.test(end)) {
                alert('يرجى إدخال التواريخ بالتنسيق الصحيح (DD/MM/YYYY)');
                e.preventDefault();
                return;
            }
        }
    });
});
