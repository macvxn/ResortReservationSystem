/**
 * Auth Enhancements - Progressive enhancement only
 * All functionality works without this script
 */

document.addEventListener('DOMContentLoaded', function() {
    // Password visibility toggle (optional enhancement)
    const passwordInputs = document.querySelectorAll('input[type="password"]');
    
    passwordInputs.forEach(input => {
        const wrapper = document.createElement('div');
        wrapper.className = 'password-wrapper';
        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);
        
        const toggleBtn = document.createElement('button');
        toggleBtn.type = 'button';
        toggleBtn.className = 'password-toggle';
        toggleBtn.innerHTML = '👁️';
        toggleBtn.setAttribute('aria-label', 'Toggle password visibility');
        wrapper.appendChild(toggleBtn);
        
        toggleBtn.addEventListener('click', function() {
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            this.innerHTML = type === 'password' ? '👁️' : '👁️‍🗨️';
        });
    });
    
    // Form validation enhancement
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = 'Logging in...';
                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Login';
                }, 3000);
            }
        });
    }
    
    // Auto-focus first error field
    const errorAlert = document.querySelector('.alert-danger');
    if (errorAlert) {
        const firstErrorField = form.querySelector('input:invalid, input[data-error="true"]');
        if (firstErrorField) {
            setTimeout(() => {
                firstErrorField.focus();
                firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 100);
        }
    }
});