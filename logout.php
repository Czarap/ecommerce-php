<?php
session_start();

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Clear any Google Sign-In cookies
setcookie('g_state', '', time() - 3600, '/');

// Add JavaScript to revoke Google access and clear sign-in state
?>
<!DOCTYPE html>
<html>
<head>
    <script src="https://accounts.google.com/gsi/client" async></script>
</head>
<body>
    <script>
        // Clear Google Sign-In state
        window.onload = function() {
            // Revoke Google access
            fetch('https://oauth2.googleapis.com/revoke?token=' + localStorage.getItem('google_access_token'), {
                method: 'POST'
            }).finally(() => {
                // Clear local storage
                localStorage.removeItem('google_access_token');
                
                // Clear Google's sign-in state
                if (window.google && window.google.accounts && window.google.accounts.id) {
                    google.accounts.id.disableAutoSelect();
                    google.accounts.id.revoke();
                }
                
                // Redirect to login page
                window.location.href = 'login.php';
            });
        };
    </script>
</body>
</html>