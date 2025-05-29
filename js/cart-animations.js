// Cart Animation Functions
function createFlyingDot(button, targetCart) {
    const dot = document.createElement('div');
    const buttonRect = button.getBoundingClientRect();
    const cartRect = targetCart.getBoundingClientRect();
    
    dot.className = 'flying-dot';
    dot.style.cssText = `
        position: fixed;
        width: 12px;
        height: 12px;
        background: var(--sunset-orange);
        border-radius: 50%;
        pointer-events: none;
        z-index: 9999;
        left: ${buttonRect.left + buttonRect.width / 2}px;
        top: ${buttonRect.top + buttonRect.height / 2}px;
        box-shadow: 0 0 10px var(--sunset-orange);
    `;
    
    document.body.appendChild(dot);
    
    const animation = dot.animate([
        {
            transform: 'scale(1)',
            opacity: 1
        },
        {
            transform: `translate(${cartRect.left - buttonRect.left}px, ${cartRect.top - buttonRect.top}px) scale(0.5)`,
            opacity: 0
        }
    ], {
        duration: 800,
        easing: 'cubic-bezier(0.25, 0.46, 0.45, 0.94)'
    });
    
    animation.onfinish = () => {
        dot.remove();
        animateCartIcon(targetCart);
    };
}

function animateCartIcon(cartIcon) {
    cartIcon.classList.add('cart-bump');
    setTimeout(() => cartIcon.classList.remove('cart-bump'), 300);
}

function showToast(message) {
    const toast = document.createElement('div');
    toast.className = 'cart-toast';
    toast.innerHTML = `
        <i class="fas fa-check-circle"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(toast);
    
    requestAnimationFrame(() => {
        toast.classList.add('show');
        setTimeout(() => {
            toast.classList.add('hide');
            setTimeout(() => toast.remove(), 300);
        }, 2000);
    });
}

// Add required styles
const style = document.createElement('style');
style.textContent = `
    .flying-dot {
        transition: all 0.3s;
    }
    
    .cart-bump {
        animation: cartBump 0.3s cubic-bezier(0.36, 0, 0.66, -0.56);
    }
    
    @keyframes cartBump {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.2); }
    }
    
    .cart-toast {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: linear-gradient(to right, var(--sunset-orange), var(--sunset-red));
        color: white;
        padding: 12px 24px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 8px;
        transform: translateY(100px);
        opacity: 0;
        transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        z-index: 9999;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }
    
    .cart-toast.show {
        transform: translateY(0);
        opacity: 1;
    }
    
    .cart-toast.hide {
        transform: translateY(100px);
        opacity: 0;
    }
    
    .cart-toast i {
        color: #fff;
        font-size: 1.2em;
    }
    
    .add-to-cart-btn {
        transition: transform 0.2s;
    }
    
    .add-to-cart-btn:active {
        transform: scale(0.95);
    }
`;

document.head.appendChild(style); 