// Helper function to make AJAX requests
function makeRequest(url, method, data) {
    console.log('Making request to:', url, 'with data:', data);
    return fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(data)
    })
    .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        return data;
    })
    .catch(error => {
        console.error('Request error:', error);
        throw error;
    });
}

// Helper function to show toast messages
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('show');
    }, 100);
    
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            document.body.removeChild(toast);
        }, 300);
    }, 3000);
}

// Initialize add to cart functionality
function initAddToCart() {
    document.querySelectorAll('.add-to-cart-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const productId = this.querySelector('input[name="product_id"]').value;
            const quantity = this.querySelector('input[name="quantity"]').value;
            
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
                    showToast(response.message);
                } else {
                    showToast(response.message, 'error');
                }
            })
            .catch(error => {
                showToast('An error occurred. Please try again.', 'error');
            });
        });
    });
}

// Initialize quantity controls
function initQuantityControls() {
    document.querySelectorAll('.quantity-control').forEach(control => {
        const input = control.querySelector('.quantity-input');
        const increment = control.querySelector('.quantity-increment');
        const decrement = control.querySelector('.quantity-decrement');
        
        // Remove any existing event listeners
        const newIncrement = increment.cloneNode(true);
        const newDecrement = decrement.cloneNode(true);
        increment.parentNode.replaceChild(newIncrement, increment);
        decrement.parentNode.replaceChild(newDecrement, decrement);
        
        // Add new event listeners
        newIncrement.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const max = parseInt(input.getAttribute('max'));
            const current = parseInt(input.value) || 1;
            
            if (current < max) {
                input.value = current + 1;
            } else {
                showToast(`Only ${max} available in stock`, 'error');
            }
        });
        
        newDecrement.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const current = parseInt(input.value) || 1;
            if (current > 1) {
                input.value = current - 1;
            }
        });
        
        // Handle direct input
        input.addEventListener('input', function() {
            const max = parseInt(this.getAttribute('max'));
            let value = parseInt(this.value);
            
            if (isNaN(value) || value < 1) {
                value = 1;
            } else if (value > max) {
                value = max;
                showToast(`Only ${max} available in stock`, 'error');
            }
            
            this.value = value;
        });
    });
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing cart functionality');
    initAddToCart();
    initQuantityControls();
}); 