document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
    button.addEventListener('click', function () {
        const field = document.getElementById(button.dataset.passwordToggle);
        const isVisible = field.type === 'password';
        field.type = isVisible ? 'text' : 'password';
        button.setAttribute('aria-pressed', isVisible ? 'true' : 'false');
        button.setAttribute('aria-label', isVisible ? 'Hide password' : 'Show password');
    });
});

document.querySelectorAll('form[data-password-form]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
        const password = form.querySelector('[data-new-password]');
        if (!password) return;
        const value = password.value;
        const valid = value.length >= 6 && /[A-Z]/.test(value) && /[0-9]/.test(value) && /[^A-Za-z0-9]/.test(value);
        if (!valid) {
            event.preventDefault();
            password.setCustomValidity('Use at least 6 characters, including an uppercase letter, a number, and a symbol.');
            password.reportValidity();
        } else {
            password.setCustomValidity('');
        }
    });
    form.querySelectorAll('[data-new-password]').forEach(function (field) {
        field.addEventListener('input', function () { field.setCustomValidity(''); });
    });
});
