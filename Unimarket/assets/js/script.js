/**
 * UniMarket - Main JavaScript File
 * Handles frontend interactions, form validations, and UI enhancements
 */

document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle functionality
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', function() {
            const menu = document.getElementById('mobileMenu');
            if (menu) {
                menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
            }
        });
    }

    // Initialize all tooltips
    initTooltips();

    // Initialize all form validations
    initFormValidations();

    // Initialize product quantity controls
    initQuantityControls();

    // Initialize image preview for file uploads
    initImagePreviews();

    // Initialize date/time pickers
    initDateTimePickers();

    // Initialize cart interactions
    if (document.querySelector('.cart-page')) {
        initCartInteractions();
    }
});

/**
 * Initialize tooltips throughout the application
 */
function initTooltips() {
    const tooltips = document.querySelectorAll('[data-tooltip]');
    
    tooltips.forEach(tooltip => {
        const tooltipText = tooltip.getAttribute('data-tooltip');
        const tooltipElement = document.createElement('span');
        tooltipElement.className = 'tooltip-text';
        tooltipElement.textContent = tooltipText;
        tooltip.appendChild(tooltipElement);
        
        // Position tooltip on hover
        tooltip.addEventListener('mouseenter', function() {
            const rect = this.getBoundingClientRect();
            tooltipElement.style.left = `${rect.width / 2}px`;
            tooltipElement.style.top = `${rect.height + 5}px`;
            tooltipElement.style.opacity = '1';
        });
        
        tooltip.addEventListener('mouseleave', function() {
            tooltipElement.style.opacity = '0';
        });
    });
}

/**
 * Initialize form validation handlers
 */
function initFormValidations() {
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            let isValid = true;
            const requiredFields = this.querySelectorAll('[required]');
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('error');
                    
                    // Create or show error message
                    let errorMsg = field.nextElementSibling;
                    if (!errorMsg || !errorMsg.classList.contains('error-message')) {
                        errorMsg = document.createElement('small');
                        errorMsg.className = 'error-message';
                        errorMsg.textContent = 'This field is required';
                        field.parentNode.insertBefore(errorMsg, field.nextSibling);
                    }
                } else {
                    field.classList.remove('error');
                    const errorMsg = field.nextElementSibling;
                    if (errorMsg && errorMsg.classList.contains('error-message')) {
                        errorMsg.remove();
                    }
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                
                // Scroll to first error
                const firstError = this.querySelector('.error');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
    });
}

/**
 * Initialize quantity controls for product pages
 */
function initQuantityControls() {
    document.querySelectorAll('.quantity-control').forEach(control => {
        const input = control.querySelector('.quantity-input');
        const increment = control.querySelector('.quantity-increment');
        const decrement = control.querySelector('.quantity-decrement');
        
        increment.addEventListener('click', () => {
            input.value = parseInt(input.value) + 1;
            input.dispatchEvent(new Event('change'));
        });
        
        decrement.addEventListener('click', () => {
            if (parseInt(input.value) > 1) {
                input.value = parseInt(input.value) - 1;
                input.dispatchEvent(new Event('change'));
            }
        });
        
        // Validate input
        input.addEventListener('change', () => {
            if (parseInt(input.value) < 1) {
                input.value = 1;
            }
        });
    });
}

/**
 * Initialize image previews for file uploads
 */
function initImagePreviews() {
    document.querySelectorAll('.image-upload').forEach(upload => {
        const input = upload.querySelector('input[type="file"]');
        const preview = upload.querySelector('.image-preview');
        const defaultText = upload.querySelector('.default-text');
        
        if (input && preview) {
            input.addEventListener('change', function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        preview.style.backgroundImage = `url(${e.target.result})`;
                        preview.classList.add('has-image');
                        if (defaultText) defaultText.style.display = 'none';
                    }
                    
                    reader.readAsDataURL(file);
                }
            });
        }
    });
}

/**
 * Initialize date/time pickers for pickup scheduling
 */
function initDateTimePickers() {
    const datePickers = document.querySelectorAll('.date-picker');
    
    if (datePickers.length > 0) {
        // Load flatpickr if not already loaded
        if (typeof flatpickr === 'undefined') {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/flatpickr';
            script.onload = function() {
                setupDateTimePickers();
            };
            document.head.appendChild(script);
            
            const style = document.createElement('link');
            style.rel = 'stylesheet';
            style.href = 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css';
            document.head.appendChild(style);
        } else {
            setupDateTimePickers();
        }
    }
    
    function setupDateTimePickers() {
        document.querySelectorAll('.date-picker').forEach(picker => {
            flatpickr(picker, {
                enableTime: picker.classList.contains('time-picker'),
                dateFormat: picker.classList.contains('time-picker') ? "Y-m-d H:i" : "Y-m-d",
                minDate: "today",
                time_24hr: true
            });
        });
    }
}

/**
 * Initialize cart interactions
 */
function initCartInteractions() {
    // Update cart item quantities
    document.querySelectorAll('.cart-item-quantity').forEach(input => {
        input.addEventListener('change', function() {
            const form = this.closest('form');
            if (form) {
                form.submit();
            }
        });
    });
    
    // Handle remove item buttons
    document.querySelectorAll('.remove-item-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to remove this item from your cart?')) {
                e.preventDefault();
            }
        });
    });
    
    // Handle checkout button
    const checkoutBtn = document.querySelector('.checkout-btn');
    if (checkoutBtn) {
        checkoutBtn.addEventListener('click', function(e) {
            if (document.querySelector('.cart-item').length === 0) {
                e.preventDefault();
                alert('Your cart is empty. Please add items before checking out.');
            }
        });
    }
}

/**
 * AJAX helper function
 */
function makeRequest(url, method = 'GET', data = null) {
    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        xhr.open(method, url);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        
        xhr.onload = function() {
            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    resolve(JSON.parse(xhr.responseText));
                } catch (e) {
                    resolve(xhr.responseText);
                }
            } else {
                reject(xhr.statusText);
            }
        };
        
        xhr.onerror = function() {
            reject(xhr.statusText);
        };
        
        xhr.send(data ? JSON.stringify(data) : null);
    });
}

/**
 * Display flash messages
 */
function displayFlashMessages() {
    const flashMessages = document.querySelector('.flash-messages');
    if (flashMessages) {
        setTimeout(() => {
            flashMessages.style.opacity = '0';
            setTimeout(() => {
                flashMessages.remove();
            }, 500);
        }, 5000);
    }
}

// Display flash messages when page loads
displayFlashMessages();

/**
 * Debounce function for performance optimization
 */
function debounce(func, wait, immediate) {
    let timeout;
    return function() {
        const context = this, args = arguments;
        const later = function() {
            timeout = null;
            if (!immediate) func.apply(context, args);
        };
        const callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow) func.apply(context, args);
    };
}

/**
 * Throttle function for performance optimization
 */
function throttle(func, limit) {
    let lastFunc;
    let lastRan;
    return function() {
        const context = this;
        const args = arguments;
        if (!lastRan) {
            func.apply(context, args);
            lastRan = Date.now();
        } else {
            clearTimeout(lastFunc);
            lastFunc = setTimeout(function() {
                if ((Date.now() - lastRan) >= limit) {
                    func.apply(context, args);
                    lastRan = Date.now();
                }
            }, limit - (Date.now() - lastRan));
        }
    }
}

// Add to cart functionality
document.querySelectorAll('.add-to-cart').forEach(button => {
    button.addEventListener('click', function(e) {
        e.preventDefault();
        
        const productId = this.dataset.productId;
        const quantity = this.closest('form')?.querySelector('input[name="quantity"]')?.value || 1;
        
        makeRequest('/unimarket/ajax/add_to_cart.php', 'POST', {
            product_id: productId,
            quantity: quantity
        })
        .then(response => {
            if (response.success) {
                // Update cart count in header
                const cartCount = document.querySelector('.cart-count');
                if (cartCount) {
                    cartCount.textContent = response.cart_count;
                    cartCount.classList.add('pulse');
                    setTimeout(() => {
                        cartCount.classList.remove('pulse');
                    }, 500);
                }
                
                // Show success message
                showToast('Item added to cart!');
            } else {
                showToast(response.message || 'Failed to add item to cart', 'error');
            }
        })
        .catch(error => {
            showToast('An error occurred. Please try again.', 'error');
        });
    });
});

/**
 * Show toast notification
 */
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('show');
    }, 10);
    
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            toast.remove();
        }, 300);
    }, 3000);
}

// Add CSS for toast notifications
const toastStyles = document.createElement('style');
toastStyles.textContent = `
.toast {
    position: fixed;
    bottom: 20px;
    right: 20px;
    padding: 15px 25px;
    border-radius: 4px;
    color: white;
    background-color: #2e7d32;
    box-shadow: 0 3px 10px rgba(0,0,0,0.2);
    transform: translateY(100px);
    opacity: 0;
    transition: all 0.3s ease;
    z-index: 1000;
}
.toast.show {
    transform: translateY(0);
    opacity: 1;
}
.toast-error {
    background-color: #d32f2f;
}
.toast-success {
    background-color: #2e7d32;
}
.pulse {
    animation: pulse 0.5s;
}
@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
}
`;
document.head.appendChild(toastStyles);