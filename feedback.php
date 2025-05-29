<?php
$page_title = "Customer Feedback";
include 'includes/header.php';
?>

<style>
/* Dark Sunset Theme */
:root {
    --sunset-dark: #1a1a2e;
    --sunset-darker: #16213e;
    --sunset-orange: #ff7b25;
    --sunset-pink: #ff4d6d;
    --sunset-purple: #6a2c70;
    --text-light: #f8f9fa;
    --text-muted: #adb5bd;
}

.feedback-container {
    max-width: 800px;
    margin: 2rem auto;
    padding: 2rem;
}

.feedback-header {
    text-align: center;
    margin-bottom: 3rem;
}

.feedback-title {
    font-size: 2.5rem;
    background: linear-gradient(to right, var(--sunset-orange), var(--sunset-pink));
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    margin-bottom: 1rem;
}

.feedback-subtitle {
    color: var(--text-muted);
    font-size: 1.1rem;
}

.feedback-form {
    background: rgba(26, 26, 46, 0.8);
    backdrop-filter: blur(10px);
    border-radius: 15px;
    padding: 2.5rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(255, 77, 109, 0.2);
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    color: var(--sunset-orange);
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    background: rgba(22, 33, 62, 0.5);
    border: 1px solid rgba(255, 77, 109, 0.2);
    border-radius: 8px;
    color: var(--text-light);
    transition: all 0.3s ease;
}

.form-control::placeholder {
    color: rgba(255, 255, 255, 0.7);
}

/* For Firefox */
.form-control::-moz-placeholder {
    color: rgba(255, 255, 255, 0.7);
    opacity: 1;
}

/* For Internet Explorer */
.form-control:-ms-input-placeholder {
    color: rgba(255, 255, 255, 0.7);
}

/* For Edge */
.form-control::-ms-input-placeholder {
    color: rgba(255, 255, 255, 0.7);
}

.form-control:focus {
    outline: none;
    border-color: var(--sunset-orange);
    box-shadow: 0 0 0 2px rgba(255, 123, 37, 0.2);
}

textarea.form-control {
    min-height: 150px;
    resize: vertical;
}

/* Star Rating */
.star-rating {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
    gap: 0.5rem;
}

.star-rating input {
    display: none;
}

.star-rating label {
    font-size: 2rem;
    color: var(--text-muted);
    cursor: pointer;
    transition: all 0.2s ease;
}

.star-rating label:hover,
.star-rating label:hover ~ label,
.star-rating input:checked ~ label {
    color: var(--sunset-orange);
}

.submit-btn {
    background: linear-gradient(to right, var(--sunset-orange), var(--sunset-pink));
    color: white;
    border: none;
    padding: 1rem 2rem;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    width: 100%;
    margin-top: 1rem;
}

.submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255, 77, 109, 0.3);
}

@media (max-width: 768px) {
    .feedback-container {
        padding: 1rem;
    }
    
    .feedback-form {
        padding: 1.5rem;
    }
    
    .star-rating label {
        font-size: 1.5rem;
    }
}
</style>

<div class="feedback-container">
    <div class="feedback-header">
        <h1 class="feedback-title">Your Feedback Matters</h1>
        <p class="feedback-subtitle">Help us improve your shopping experience</p>
    </div>

    <form class="feedback-form" action="process_feedback.php" method="POST">
        <?php if(isset($_SESSION['feedback_success'])): ?>
            <div class="alert alert-success">
                <?php 
                echo $_SESSION['feedback_success'];
                unset($_SESSION['feedback_success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if(isset($_SESSION['feedback_error'])): ?>
            <div class="alert alert-danger">
                <?php 
                echo $_SESSION['feedback_error'];
                unset($_SESSION['feedback_error']);
                ?>
            </div>
        <?php endif; ?>

        <div class="form-group">
            <label class="form-label">Overall Rating</label>
            <div class="star-rating">
                <input type="radio" id="star5" name="rating" value="5" required>
                <label for="star5" title="5 stars">★</label>
                
                <input type="radio" id="star4" name="rating" value="4">
                <label for="star4" title="4 stars">★</label>
                
                <input type="radio" id="star3" name="rating" value="3">
                <label for="star3" title="3 stars">★</label>
                
                <input type="radio" id="star2" name="rating" value="2">
                <label for="star2" title="2 stars">★</label>
                
                <input type="radio" id="star1" name="rating" value="1">
                <label for="star1" title="1 star">★</label>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="subject">Subject</label>
            <input type="text" class="form-control" id="subject" name="subject" required 
                   placeholder="Brief description of your feedback">
        </div>

        <div class="form-group">
            <label class="form-label" for="feedback">Your Feedback</label>
            <textarea class="form-control" id="feedback" name="feedback" required
                      placeholder="Please share your detailed feedback here..."></textarea>
        </div>

        <?php if(!isset($_SESSION['user_id'])): ?>
            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input type="email" class="form-control" id="email" name="email" required
                       placeholder="Enter your email address">
            </div>
        <?php endif; ?>

        <button type="submit" class="submit-btn">Submit Feedback</button>
    </form>
</div>

<?php include 'includes/footer.php'; ?> 