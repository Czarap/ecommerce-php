<?php
$page_title = "About Us";
include 'includes/header.php';
?>

<div class="about-container">
    <div class="about-header">
        <h1>About E-Czar</h1>
        <p class="subtitle">Your Trusted Online Shopping Destination</p>
    </div>

    <div class="about-content">
        <div class="about-section">
            <div class="icon-wrapper">
                <i class="fas fa-shopping-bag"></i>
            </div>
            <h2>Who We Are</h2>
            <p>E-Czar is a leading e-commerce platform that connects buyers with trusted sellers. We provide a secure, user-friendly marketplace where customers can discover quality products at competitive prices.</p>
        </div>

        <div class="about-section">
            <div class="icon-wrapper">
                <i class="fas fa-bullseye"></i>
            </div>
            <h2>Our Mission</h2>
            <p>To create a seamless online shopping experience by providing a reliable platform that empowers both buyers and sellers, while ensuring quality, security, and customer satisfaction.</p>
        </div>

        <div class="about-section">
            <div class="icon-wrapper">
                <i class="fas fa-star"></i>
            </div>
            <h2>Why Choose Us</h2>
            <ul>
                <li>Wide selection of quality products</li>
                <li>Secure payment options</li>
                <li>Verified sellers</li>
                <li>Excellent customer support</li>
                <li>Easy returns and refunds</li>
            </ul>
        </div>

        <div class="about-section">
            <div class="icon-wrapper">
                <i class="fas fa-handshake"></i>
            </div>
            <h2>Our Commitment</h2>
            <p>We are committed to providing a safe and reliable platform for online shopping. Our team works tirelessly to ensure that all transactions are secure and that both buyers and sellers have a positive experience.</p>
        </div>
    </div>

    <div class="join-section">
        <h2>Join Our Community</h2>
        <p>Whether you're a buyer looking for great deals or a seller wanting to grow your business, E-Czar is here to help you succeed.</p>
        <div class="cta-buttons">
            <a href="register.php" class="btn">Sign Up Now</a>
        </div>
    </div>
</div>

<style>
.about-container {
    max-width: 1200px;
    margin: 3rem auto;
    padding: 0 1rem;
}

.about-header {
    text-align: center;
    margin-bottom: 3rem;
}

.about-header h1 {
    color: var(--sunset-orange);
    font-size: 2.5rem;
    margin-bottom: 1rem;
    text-shadow: 0 0 10px var(--sunset-glow);
}

.subtitle {
    color: var(--text-light);
    font-size: 1.2rem;
    opacity: 0.9;
}

.about-content {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
}

.about-section {
    background: rgba(26, 26, 46, 0.8);
    padding: 2rem;
    border-radius: 10px;
    border: 1px solid var(--sunset-purple);
    transition: transform 0.3s, box-shadow 0.3s;
}

.about-section:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(255, 123, 37, 0.2);
    border-color: var(--sunset-orange);
}

.icon-wrapper {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, var(--sunset-orange), var(--sunset-red));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1.5rem;
}

.icon-wrapper i {
    font-size: 1.8rem;
    color: white;
}

.about-section h2 {
    color: var(--sunset-light);
    margin-bottom: 1rem;
    font-size: 1.4rem;
}

.about-section p {
    color: var(--text-muted);
    line-height: 1.6;
}

.about-section ul {
    color: var(--text-muted);
    list-style-type: none;
    padding: 0;
}

.about-section ul li {
    margin-bottom: 0.5rem;
    padding-left: 1.5rem;
    position: relative;
}

.about-section ul li:before {
    content: "•";
    color: var(--sunset-orange);
    position: absolute;
    left: 0;
}

.join-section {
    text-align: center;
    background: rgba(26, 26, 46, 0.5);
    padding: 3rem;
    border-radius: 10px;
    border: 1px dashed var(--sunset-orange);
    margin-top: 3rem;
}

.join-section h2 {
    color: var(--sunset-light);
    margin-bottom: 1rem;
}

.join-section p {
    color: var(--text-muted);
    margin-bottom: 2rem;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

.cta-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

.btn {
    display: inline-block;
    padding: 12px 24px;
    background: linear-gradient(135deg, var(--sunset-orange), var(--sunset-red));
    color: white;
    text-decoration: none;
    border-radius: 6px;
    transition: all 0.3s;
    font-weight: 500;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255, 123, 37, 0.3);
    color: white;
}

.btn-outline {
    background: transparent;
    border: 2px solid var(--sunset-orange);
    color: var(--sunset-orange);
}

.btn-outline:hover {
    background: var(--sunset-orange);
    color: white;
}

@media (max-width: 768px) {
    .about-header h1 {
        font-size: 2rem;
    }
    
    .about-content {
        grid-template-columns: 1fr;
    }
    
    .about-section {
        padding: 1.5rem;
    }
    
    .join-section {
        padding: 2rem 1rem;
    }
}
</style>

<?php include 'includes/footer.php'; ?> 