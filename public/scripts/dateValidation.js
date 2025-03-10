document.addEventListener("DOMContentLoaded", function() {
    const dateInput = document.querySelector('input[name="form[date]"]');
    const errorContainer = document.createElement('div');
    errorContainer.style.color = 'red';
    dateInput.parentElement.appendChild(errorContainer);

    dateInput.addEventListener('input', function () {
        const currentDate = new Date();
        const inputDate = new Date(dateInput.value);

        
        if (inputDate < currentDate) {
            errorContainer.textContent = 'La date doit être égale ou supérieure à aujourd\'hui.';
        } else {
            errorContainer.textContent = ''; 
        }
    });
});
