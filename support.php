<?php
$page_title = "Support - E-Czar";
include 'includes/config.php';
include 'includes/header.php';
?>

<style>
.support-container {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 20px;
}

.support-header {
    text-align: center;
    margin-bottom: 3rem;
}

.support-header h1 {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    background: linear-gradient(to right, var(--sunset-orange), var(--sunset-pink));
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
}

.support-header p {
    color: var(--text-muted);
    font-size: 1.1rem;
}

.support-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    margin-bottom: 3rem;
}

@media (max-width: 768px) {
    .support-grid {
        grid-template-columns: 1fr;
    }
}

/* Contact Form Styles */
.contact-form {
    background: linear-gradient(145deg, var(--sunset-darker), var(--sunset-dark));
    padding: 2rem;
    border-radius: 10px;
    border: 1px solid rgba(255, 123, 37, 0.2);
}

.contact-form h2 {
    color: var(--sunset-orange);
    margin-bottom: 1.5rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    color: var(--text-light);
}

.form-group input,
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 0.8rem;
    background: rgba(22, 33, 62, 0.8);
    border: 1px solid var(--sunset-purple);
    border-radius: 4px;
    color: var(--text-light);
    transition: all 0.3s;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    border-color: var(--sunset-orange);
    box-shadow: 0 0 0 2px rgba(255, 123, 37, 0.2);
    outline: none;
}

.submit-btn {
    background: linear-gradient(to right, var(--sunset-orange), var(--sunset-red));
    color: white;
    border: none;
    padding: 1rem 2rem;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.3s;
    width: 100%;
    font-weight: bold;
}

.submit-btn:hover {
    background: linear-gradient(to right, var(--sunset-red), var(--sunset-purple));
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 123, 37, 0.3);
}

/* FAQ Styles */
.faq-section {
    background: linear-gradient(145deg, var(--sunset-darker), var(--sunset-dark));
    padding: 2rem;
    border-radius: 10px;
    border: 1px solid rgba(255, 123, 37, 0.2);
}

.faq-section h2 {
    color: var(--sunset-orange);
    margin-bottom: 1.5rem;
}

.faq-item {
    margin-bottom: 1.5rem;
    border-bottom: 1px solid rgba(255, 123, 37, 0.2);
    padding-bottom: 1rem;
}

.faq-question {
    color: var(--text-light);
    font-weight: bold;
    margin-bottom: 0.5rem;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.faq-question i {
    color: var(--sunset-orange);
    transition: transform 0.3s;
}

.faq-answer {
    color: var(--text-muted);
    display: none;
    padding: 1rem 0;
    line-height: 1.6;
}

.faq-item.active .faq-question i {
    transform: rotate(180deg);
}

.faq-item.active .faq-answer {
    display: block;
}

/* Quick Links */
.quick-links {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 3rem;
}

.quick-link-card {
    background: linear-gradient(145deg, var(--sunset-darker), var(--sunset-dark));
    padding: 1.5rem;
    border-radius: 8px;
    border: 1px solid rgba(255, 123, 37, 0.2);
    text-align: center;
    transition: all 0.3s;
}

.quick-link-card:hover {
    transform: translateY(-5px);
    border-color: var(--sunset-orange);
}

.quick-link-card i {
    font-size: 2rem;
    color: var(--sunset-orange);
    margin-bottom: 1rem;
}

.quick-link-card h3 {
    color: var(--text-light);
    margin-bottom: 0.5rem;
}

.quick-link-card p {
    color: var(--text-muted);
    font-size: 0.9rem;
}
</style>

<div class="support-container">
    <div class="support-header">
        <h1>How Can We Help You?</h1>
        <p>Get in touch with our support team or browse through our frequently asked questions.</p>
    </div>

    <div class="quick-links">
        <div class="quick-link-card">
            <i class="fas fa-shipping-fast"></i>
            <h3>Shipping Info</h3>
            <p>Track your order or learn about our shipping policies</p>
        </div>
        <div class="quick-link-card">
            <i class="fas fa-exchange-alt"></i>
            <h3>Returns & Refunds</h3>
            <p>Learn about our return policy and process</p>
        </div>
        <div class="quick-link-card">
            <i class="fas fa-user-shield"></i>
            <h3>Account Help</h3>
            <p>Get help with your account or payment issues</p>
        </div>
    </div>

    <div class="support-grid">
        <div class="contact-form">
            <h2><i class="fas fa-envelope"></i> Contact Us</h2>
            <form id="supportForm" action="process_support.php" method="POST">
                <div class="form-group">
                    <label for="name">Your Name</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="subject">Subject</label>
                    <select id="subject" name="subject" required>
                        <option value="">Select a topic...</option>
                        <option value="order">Order Issue</option>
                        <option value="shipping">Shipping Question</option>
                        <option value="return">Return/Refund</option>
                        <option value="account">Account Help</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea id="message" name="message" rows="5" required></textarea>
                </div>
                <button type="submit" class="submit-btn">Send Message</button>
            </form>
        </div>

        <div class="faq-section">
            <h2><i class="fas fa-question-circle"></i> Frequently Asked Questions</h2>
            <div class="faq-item">
                <div class="faq-question">
                    How do I track my order? <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Once your order ships, you'll receive a tracking number via email. You can also track your order by logging into your account and viewing your order history.
                </div>
            </div>
            <div class="faq-item">
                <div class="faq-question">
                    What is your return policy? <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    We accept returns within 30 days of purchase. Items must be unused and in their original packaging. Please contact our support team to initiate a return.
                </div>
            </div>
            <div class="faq-item">
                <div class="faq-question">
                    How long does shipping take? <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Standard shipping typically takes 3-5 business days. Express shipping options are available at checkout for faster delivery.
                </div>
            </div>
            <div class="faq-item">
                <div class="faq-question">
                    Do you ship internationally? <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Yes, we ship to most countries worldwide. Shipping costs and delivery times vary by location. You can see specific shipping options at checkout.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // FAQ Toggle
    document.querySelectorAll('.faq-question').forEach(question => {
        question.addEventListener('click', () => {
            const faqItem = question.parentElement;
            faqItem.classList.toggle('active');
        });
    });

    // Form Submission
    document.getElementById('supportForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const submitBtn = this.querySelector('.submit-btn');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Sending...';
        
        try {
            const formData = new FormData(this);
            const response = await fetch(this.action, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.status === 'success') {
                alert('Thank you for contacting us! We will get back to you soon.');
                this.reset();
            } else {
                alert(data.message || 'An error occurred. Please try again.');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred. Please try again later.');
        }
        
        submitBtn.textContent = originalText;
    });
});
</script>

<?php include 'includes/footer.php'; ?> 