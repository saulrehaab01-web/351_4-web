document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('.js-validate-form');
    if (form) {
        form.addEventListener('submit', (event) => {
            const emailField = form.querySelector('input[type="email"]');
            if (emailField && emailField.value.trim() === '') {
                event.preventDefault();
                alert('Please enter a valid email address.');
            }
        });
    }
});
