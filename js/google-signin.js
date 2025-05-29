function handleCredentialResponse(response) {
    if (!response.credential) {
        console.error('No credential received');
        return;
    }

    // Store the credential for later revocation
    localStorage.setItem('google_access_token', response.credential);

    // Send the credential to our backend
    fetch('google_signin_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            credential: response.credential
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            window.location.href = 'index.php';
        } else {
            console.error('Login failed:', data.message);
            alert('Login failed: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred during login. Please try again.');
    });
}

function initializeGoogleSignIn() {
    google.accounts.id.initialize({
        client_id: googleClientId, // This will be defined in the HTML
        callback: handleCredentialResponse,
        auto_select: false,
        cancel_on_tap_outside: true
    });
    
    google.accounts.id.renderButton(
        document.getElementById('g_id_signin'),
        {
            type: 'standard',
            theme: 'outline',
            size: 'large',
            text: 'continue_with',
            shape: 'rectangular',
            logo_alignment: 'left',
            width: document.getElementById('g_id_signin').offsetWidth
        }
    );
    
    // Also display the One Tap dialog
    google.accounts.id.prompt();
} 