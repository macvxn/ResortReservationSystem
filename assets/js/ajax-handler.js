// assets/js/ajax-handler.js
/**
 * Unified AJAX Form Handler for Aura Luxe Resort System
 * Handles ALL forms with class 'ajax-form'
 * Provides graceful degradation for non-JS users
 */

class AjaxFormHandler {
    constructor() {
        this.csrfToken = null;
        this.init();
    }
    
    init() {
        // Set up CSRF token if available
        this.setupCSRFToken();
        
        // Intercept all form submissions
        document.addEventListener('submit', (e) => {
            const form = e.target;
            
            // Only process forms marked for AJAX handling
            if (form.classList.contains('ajax-form')) {
                e.preventDefault();
                this.submitForm(form);
            }
        });
        
        // Handle AJAX links
        document.addEventListener('click', (e) => {
            const link = e.target.closest('.ajax-link');
            if (link) {
                e.preventDefault();
                this.handleAjaxLink(link);
            }
        });
        
        console.log('AJAX Form Handler initialized');
    }
    
    setupCSRFToken() {
        // Try to get CSRF token from meta tag
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        if (metaTag) {
            this.csrfToken = metaTag.getAttribute('content');
        }
    }
    
    submitForm(form) {
        const formData = new FormData(form);
        const submitButton = form.querySelector('[type="submit"]');
        const originalButtonText = submitButton ? submitButton.innerHTML : '';
        
        // Show loading state
        this.showLoadingState(submitButton, originalButtonText);
        
        // Add CSRF token if available
        if (this.csrfToken) {
            formData.append('csrf_token', this.csrfToken);
        }
        
        // Add AJAX indicator
        formData.append('ajax', 'true');
        
        // Determine if we need file upload handling
        const hasFiles = form.querySelector('input[type="file"]');
        const isMultipart = form.enctype === 'multipart/form-data';
        
        // Prepare fetch options
        const fetchOptions = {
            method: 'POST',
            body: formData,
            headers: {}
        };
        
        // Add AJAX header
        fetchOptions.headers['X-Requested-With'] = 'XMLHttpRequest';
        
        fetch(form.action, fetchOptions)
        .then(response => {
            // Check if response is JSON
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return response.json();
            } else {
                // Not JSON - likely a redirect or HTML response
                console.warn('Non-JSON response received, falling back to normal submission');
                this.handleNonAjaxResponse(form, response);
                return null;
            }
        })
        .then(data => {
            if (!data) return;
            
            if (data.success) {
                this.handleSuccess(form, data);
            } else {
                this.handleErrors(form, data);
            }
        })
        .catch(error => {
            console.error('AJAX Error:', error);
            this.showAlert('Network error. Please try again.', 'danger');
            // Fall back to normal form submission
            this.fallbackToNormalSubmission(form);
        })
        .finally(() => {
            // Restore button state
            this.restoreButtonState(submitButton, originalButtonText);
        });
    }
    
    showLoadingState(button, originalText) {
        if (button) {
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            button.classList.add('btn-loading');
        }
    }
    
    restoreButtonState(button, originalText) {
        if (button) {
            button.disabled = false;
            button.innerHTML = originalText;
            button.classList.remove('btn-loading');
        }
    }
    
    handleNonAjaxResponse(form, response) {
        // For non-JSON responses, check if it's a redirect
        if (response.redirected) {
            window.location.href = response.url;
        } else {
            // Fallback to normal submission
            this.fallbackToNormalSubmission(form);
        }
    }
    
    handleSuccess(form, data) {
        // Show success message
        if (data.message) {
            this.showAlert(data.message, 'success');
        }
        
        // Handle redirect if provided
        if (data.redirect) {
            setTimeout(() => {
                window.location.href = data.redirect;
            }, data.redirect_delay || 1500);
        }
        
        // Reset form if needed
        if (data.reset_form) {
            form.reset();
            
            // Clear any validation errors
            form.querySelectorAll('.is-invalid').forEach(el => {
                el.classList.remove('is-invalid');
            });
            form.querySelectorAll('.invalid-feedback').forEach(el => {
                el.remove();
            });
        }
        
        // Trigger custom event for success
        const successEvent = new CustomEvent('ajax:success', {
            detail: { form: form, data: data }
        });
        form.dispatchEvent(successEvent);
        
        // Call custom success callback if defined on form
        if (form.dataset.successCallback) {
            if (typeof window[form.dataset.successCallback] === 'function') {
                window[form.dataset.successCallback](form, data);
            }
        }
    }
    
    handleErrors(form, data) {
        // Show general error alert
        if (data.message) {
            this.showAlert(data.message, 'danger');
        }
        
        // Clear previous error states
        this.clearFormErrors(form);
        
        // Apply field-specific errors
        if (data.errors && typeof data.errors === 'object') {
            this.applyFieldErrors(form, data.errors);
        }
        
        // Focus on first error field
        const firstError = form.querySelector('.is-invalid');
        if (firstError) {
            firstError.focus();
        }
        
        // Trigger custom event for errors
        const errorEvent = new CustomEvent('ajax:error', {
            detail: { form: form, data: data }
        });
        form.dispatchEvent(errorEvent);
        
        // Call custom error callback if defined on form
        if (form.dataset.errorCallback) {
            if (typeof window[form.dataset.errorCallback] === 'function') {
                window[form.dataset.errorCallback](form, data);
            }
        }
    }
    
    clearFormErrors(form) {
        form.querySelectorAll('.is-invalid').forEach(el => {
            el.classList.remove('is-invalid');
        });
        form.querySelectorAll('.invalid-feedback').forEach(el => {
            el.remove();
        });
    }
    
    applyFieldErrors(form, errors) {
        Object.entries(errors).forEach(([field, message]) => {
            // Try to find the input by name
            let input = form.querySelector(`[name="${field}"]`);
            
            // If not found, try by ID
            if (!input) {
                input = document.getElementById(field);
            }
            
            // If still not found, try by name with brackets (for arrays)
            if (!input && field.includes('[')) {
                const sanitizedField = field.replace(/\[/g, '\\[').replace(/\]/g, '\\]');
                input = form.querySelector(`[name="${sanitizedField}"]`);
            }
            
            if (input) {
                input.classList.add('is-invalid');
                
                // Add error message
                const errorDiv = document.createElement('div');
                errorDiv.className = 'invalid-feedback';
                errorDiv.innerHTML = message;
                
                // Insert after input, but before any existing help text
                const parent = input.parentNode;
                if (input.nextSibling) {
                    parent.insertBefore(errorDiv, input.nextSibling);
                } else {
                    parent.appendChild(errorDiv);
                }
            }
        });
    }
    
    showAlert(message, type = 'info') {
        // Remove existing alerts of same type
        const existingAlert = document.querySelector('.ajax-alert');
        if (existingAlert) {
            existingAlert.remove();
        }
        
        // Determine alert class
        let alertClass = 'alert-info';
        if (type === 'success') alertClass = 'alert-success';
        if (type === 'danger') alertClass = 'alert-danger';
        if (type === 'warning') alertClass = 'alert-warning';
        
        // Create alert element
        const alertDiv = document.createElement('div');
        alertDiv.className = `ajax-alert alert ${alertClass} alert-dismissible fade show`;
        alertDiv.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            max-width: 500px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            animation: slideInRight 0.3s ease;
        `;
        
        alertDiv.innerHTML = `
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <div class="d-flex align-items-center">
                <i class="fas ${this.getAlertIcon(type)} mr-2"></i>
                <div>
                    <strong>${this.getAlertTitle(type)}</strong>
                    <div class="small">${message}</div>
                </div>
            </div>
        `;
        
        document.body.appendChild(alertDiv);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
        
        // Add close button functionality
        alertDiv.querySelector('.close').addEventListener('click', () => {
            alertDiv.remove();
        });
        
        // Add CSS animation if not already present
        if (!document.querySelector('#ajax-alert-animations')) {
            const style = document.createElement('style');
            style.id = 'ajax-alert-animations';
            style.textContent = `
                @keyframes slideInRight {
                    from {
                        transform: translateX(100%);
                        opacity: 0;
                    }
                    to {
                        transform: translateX(0);
                        opacity: 1;
                    }
                }
            `;
            document.head.appendChild(style);
        }
    }
    
    getAlertIcon(type) {
        switch(type) {
            case 'success': return 'fa-check-circle';
            case 'danger': return 'fa-exclamation-circle';
            case 'warning': return 'fa-exclamation-triangle';
            default: return 'fa-info-circle';
        }
    }
    
    getAlertTitle(type) {
        switch(type) {
            case 'success': return 'Success!';
            case 'danger': return 'Error!';
            case 'warning': return 'Warning!';
            default: return 'Info';
        }
    }
    
    fallbackToNormalSubmission(form) {
        console.log('Falling back to normal form submission');
        
        // Remove AJAX class to prevent infinite loop
        form.classList.remove('ajax-form');
        
        // Create hidden input to indicate fallback
        const fallbackInput = document.createElement('input');
        fallbackInput.type = 'hidden';
        fallbackInput.name = 'ajax_fallback';
        fallbackInput.value = 'true';
        form.appendChild(fallbackInput);
        
        // Submit the form normally
        form.submit();
    }
    
    handleAjaxLink(link) {
        const url = link.href;
        const method = link.dataset.method || 'GET';
        const confirmMessage = link.dataset.confirm;
        
        // Handle confirmation if required
        if (confirmMessage) {
            if (!confirm(confirmMessage)) {
                return;
            }
        }
        
        // Show loading state on link
        const originalText = link.innerHTML;
        link.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        link.classList.add('disabled');
        
        fetch(url, {
            method: method,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showAlert(data.message || 'Operation successful', 'success');
                
                if (data.redirect) {
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1500);
                }
                
                // Trigger reload if needed
                if (data.reload) {
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                }
            } else {
                this.showAlert(data.message || 'Operation failed', 'danger');
            }
        })
        .catch(error => {
            console.error('AJAX Link Error:', error);
            this.showAlert('An error occurred', 'danger');
        })
        .finally(() => {
            // Restore link
            link.innerHTML = originalText;
            link.classList.remove('disabled');
        });
    }
    
    // Public method to manually submit a form via AJAX
    submitFormManually(form) {
        if (form.classList.contains('ajax-form')) {
            this.submitForm(form);
        }
    }
    
    // Public method to show alerts
    showManualAlert(message, type = 'info') {
        this.showAlert(message, type);
    }
    
    // Public method to clear form errors
    clearFormErrorsManually(form) {
        this.clearFormErrors(form);
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Create global instance
    window.ajaxHandler = new AjaxFormHandler();
    
    // Also expose as global function for backward compatibility
    window.submitAjaxForm = (formId) => {
        const form = document.getElementById(formId);
        if (form && window.ajaxHandler) {
            window.ajaxHandler.submitFormManually(form);
        }
    };
    
    // Add global alert function
    window.showAjaxAlert = (message, type) => {
        if (window.ajaxHandler) {
            window.ajaxHandler.showManualAlert(message, type);
        }
    };
});

// Export for module systems (if needed)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AjaxFormHandler;
}