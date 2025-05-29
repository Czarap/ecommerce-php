<?php
// footer.php
?>
    </main>
    
    <footer class="site-footer">
        <div class="footer-wave"></div>
        <div class="footer-container">
            <div class="footer-section" data-aos="fade-up">
                <h3><i class="fas fa-store"></i> E-Czar</h3>
                <p>Your premium e-commerce destination with the best products at sunset prices.</p>

            </div>
            <div class="footer-section" data-aos="fade-up" data-aos-delay="100">
                <h3><i class="fas fa-link"></i> Quick Links</h3>
                <ul>
                    <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="shop.php"><i class="fas fa-shopping-bag"></i> Shop</a></li>
                    <li><a href="account.php"><i class="fas fa-user"></i> My Account</a></li>
                    <li><a href="cart.php"><i class="fas fa-shopping-cart"></i> Cart</a></li>
                </ul>
            </div>
            <div class="footer-section" data-aos="fade-up" data-aos-delay="200">
                <h3><i class="fas fa-envelope"></i> Contact Us</h3>
                <div class="contact-info">
                    <p><i class="fas fa-envelope"></i> 202280308@psu.palawan.edu.ph</p>
                    <p><i class="fas fa-phone"></i> 09191234567</p>
                    <p><i class="fas fa-map-marker-alt"></i> Palawan, Philippines</p>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> E-Czar. All rights reserved.</p>
        </div>
    </footer>

    <style>
    /* Footer Styles - Enhanced Dark Sunset Theme */
    .site-footer {
        background: linear-gradient(135deg, var(--sunset-darker), var(--sunset-dark));
        color: var(--sunset-light);
        padding: 4rem 0 2rem;
        position: relative;
        margin-top: 4rem;
        box-shadow: 0 -10px 20px rgba(0, 0, 0, 0.2);
    }

    .footer-wave {
        position: absolute;
        top: -20px;
        left: 0;
        width: 100%;
        height: 20px;
        background: linear-gradient(45deg, var(--sunset-orange) 25%, transparent 25%) -10px 0,
                    linear-gradient(-45deg, var(--sunset-orange) 25%, transparent 25%) -10px 0,
                    linear-gradient(45deg, transparent 75%, var(--sunset-orange) 75%),
                    linear-gradient(-45deg, transparent 75%, var(--sunset-orange) 75%);
        background-size: 20px 20px;
        opacity: 0.3;
    }

    .footer-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 15px;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 3rem;
    }

    .footer-section {
        opacity: 0;
        animation: fadeInUp 0.6s ease forwards;
    }

    .footer-section:nth-child(1) { animation-delay: 0.2s; }
    .footer-section:nth-child(2) { animation-delay: 0.4s; }
    .footer-section:nth-child(3) { animation-delay: 0.6s; }

    .footer-section h3 {
        color: var(--sunset-orange);
        margin-bottom: 1.5rem;
        font-size: 1.4rem;
        display: flex;
        align-items: center;
        gap: 10px;
        text-shadow: 0 0 10px rgba(255, 123, 37, 0.3);
    }

    .footer-section h3 i {
        font-size: 1.2rem;
    }

    .footer-section p {
        line-height: 1.6;
        color: var(--text-muted);
        margin-bottom: 1rem;
    }

    .footer-section ul {
        list-style: none;
        padding: 0;
    }

    .footer-section ul li {
        margin-bottom: 1rem;
        transform: translateX(-10px);
        opacity: 0;
        animation: slideInRight 0.5s ease forwards;
    }

    .footer-section ul li:nth-child(1) { animation-delay: 0.3s; }
    .footer-section ul li:nth-child(2) { animation-delay: 0.4s; }
    .footer-section ul li:nth-child(3) { animation-delay: 0.5s; }
    .footer-section ul li:nth-child(4) { animation-delay: 0.6s; }

    .footer-section a {
        color: var(--sunset-light);
        text-decoration: none;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .footer-section a:hover {
        color: var(--sunset-orange);
        transform: translateX(5px);
    }

    .footer-section a i {
        font-size: 0.9rem;
        transition: transform 0.3s ease;
    }

    .footer-section a:hover i {
        transform: translateX(3px);
    }

    .social-links {
        display: flex;
        gap: 15px;
        margin-top: 1.5rem;
    }

    .social-link {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        background: rgba(255, 123, 37, 0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--sunset-orange);
        transition: all 0.3s ease;
        border: 1px solid rgba(255, 123, 37, 0.2);
    }

    .social-link:hover {
        background: var(--sunset-orange);
        color: white;
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(255, 123, 37, 0.3);
    }

    .contact-info p {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 1rem;
        opacity: 0;
        animation: fadeInUp 0.5s ease forwards;
    }

    .contact-info p:nth-child(1) { animation-delay: 0.4s; }
    .contact-info p:nth-child(2) { animation-delay: 0.5s; }
    .contact-info p:nth-child(3) { animation-delay: 0.6s; }

    .contact-info i {
        color: var(--sunset-orange);
    }

    .footer-bottom {
        text-align: center;
        padding-top: 2rem;
        margin-top: 3rem;
        border-top: 1px solid rgba(255, 123, 37, 0.2);
        color: var(--text-muted);
        font-size: 0.9rem;
        opacity: 0;
        animation: fadeIn 0.5s ease forwards 0.8s;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(-10px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .footer-container {
            grid-template-columns: 1fr;
            gap: 2rem;
            text-align: center;
        }

        .footer-section h3 {
            justify-content: center;
        }

        .social-links {
            justify-content: center;
        }

        .footer-section a {
            justify-content: center;
        }

        .contact-info p {
            justify-content: center;
        }
    }
    </style>
</body>
</html>