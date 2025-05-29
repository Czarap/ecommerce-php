// Add this to your shop.php or create a separate cart.js file
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.add-to-cart-btn').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.dataset.productId;
            const quantity = this.closest('.product-actions').querySelector('.quantity-input').value;
            
            // Show loading feedback
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
            
            fetch('add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}&quantity=${quantity}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Update cart count in navigation
                    document.querySelectorAll('.cart-count').forEach(el => {
                        el.textContent = `(${data.cart_count})`;
                    });
                    
                    // Show success feedback
                    const feedback = document.createElement('div');
                    feedback.className = 'cart-feedback';
                    feedback.innerHTML = '<i class="fas fa-check"></i> Added to cart';
                    this.parentNode.appendChild(feedback);
                    
                    // Reset button after 2 seconds
                    this.innerHTML = 'Add to Cart';
                    
                    setTimeout(() => {
                        feedback.remove();
                    }, 2000);
                } else {
                    alert(data.message);
                    this.innerHTML = 'Add to Cart';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to add to cart');
                this.innerHTML = 'Add to Cart';
            });
        });
    });
});