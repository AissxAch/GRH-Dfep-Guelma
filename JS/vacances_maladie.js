function toggleLawInput() {
    const lawSelect = document.getElementById('lawSelect');
    const lawCustomContainer = document.getElementById('lawCustomContainer');
    const lawCustomInput = document.getElementById('lawCustomInput');
    
    if (lawSelect.value === 'other') {
        lawCustomContainer.style.display = 'block';
        lawCustomInput.focus();
    } else {
        lawCustomContainer.style.display = 'none';
        lawCustomInput.value = '';
    }
}

document.querySelector('form').addEventListener('submit', function(e) {
    const lawSelect = document.getElementById('lawSelect');
    const lawCustomInput = document.getElementById('lawCustomInput');
    
    if (lawSelect.value === 'other') {
        if (!lawCustomInput.value.trim()) {
            e.preventDefault();
            alert('الرجاء إدخال النص القانوني المطلوب');
            lawCustomInput.focus();
            return;
        }
        
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'law';
        hiddenInput.value = lawCustomInput.value;
        this.appendChild(hiddenInput);
    }
});
