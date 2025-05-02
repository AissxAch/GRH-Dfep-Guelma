document.addEventListener('DOMContentLoaded', function() {
    initializeLawSelection();
});

function initializeLawSelection() {
    const lawSelects = document.querySelectorAll('.law-select-container');
    
    lawSelects.forEach(container => {
        const category = container.dataset.category;
        const inputField = container.querySelector('.law-input-field');
        const selectedLawsContainer = container.querySelector('.selected-laws');
        const addButton = container.querySelector('.add-law-btn');
        const dropdown = container.querySelector('.law-dropdown');
        
        // Fetch laws from server
        fetchLaws(category).then(laws => {
            // Populate dropdown
            dropdown.innerHTML = '';
            
            if (laws.length === 0) {
                const noResults = document.createElement('div');
                noResults.className = 'law-option no-results';
                noResults.textContent = 'لا توجد نتائج';
                dropdown.appendChild(noResults);
            } else {
                laws.forEach(law => {
                    const option = document.createElement('div');
                    option.className = 'law-option';
                    option.textContent = law.law_text;
                    option.dataset.id = law.law_id;
                    option.addEventListener('click', () => {
                        addSelectedLaw(law, selectedLawsContainer, inputField);
                        dropdown.style.display = 'none';
                    });
                    dropdown.appendChild(option);
                });
            }
        });
        
        // Toggle dropdown
        inputField.addEventListener('focus', () => {
            dropdown.style.display = 'block';
        });
        
        inputField.addEventListener('blur', () => {
            setTimeout(() => {
                dropdown.style.display = 'none';
            }, 200);
        });
        
        // Filter laws as user types
        inputField.addEventListener('input', () => {
            const searchTerm = inputField.value.toLowerCase();
            const options = dropdown.querySelectorAll('.law-option');
            
            options.forEach(option => {
                if (option.textContent.toLowerCase().includes(searchTerm)) {
                    option.style.display = 'block';
                } else {
                    option.style.display = 'none';
                }
            });
        });
        
        // Add custom law
        addButton.addEventListener('click', () => {
            const customLawText = inputField.value.trim();
            if (customLawText) {
                const customLaw = {
                    law_id: 'custom-' + Date.now(),
                    law_text: customLawText
                };
                addSelectedLaw(customLaw, selectedLawsContainer, inputField);
                inputField.value = '';
            }
        });
    });
}

function fetchLaws(category) {
    return fetch(`get_laws.php?category=${category}`)
        .then(response => response.json())
        .then(data => {
            if (Array.isArray(data)) {
                return data;
            } else {
                console.error('Error fetching laws:', data.error);
                return [];
            }
        })
        .catch(error => {
            console.error('Error:', error);
            return [];
        });
}

function addSelectedLaw(law, container, inputField) {
    // Check if already selected
    if (container.querySelector(`[data-id="${law.law_id}"]`)) {
        return;
    }
    
    const lawElement = document.createElement('div');
    lawElement.className = 'selected-law';
    lawElement.dataset.id = law.law_id;
    
    lawElement.innerHTML = `
        <span>${law.law_text}</span>
        <button type="button" class="remove-law-btn">&times;</button>
        <input type="hidden" name="laws[]" value="${law.law_id}">
        <input type="hidden" name="law_texts[]" value="${law.law_text.replace(/"/g, '&quot;')}">
    `;
    
    lawElement.querySelector('.remove-law-btn').addEventListener('click', () => {
        lawElement.remove();
    });
    
    container.appendChild(lawElement);
    inputField.value = '';
}