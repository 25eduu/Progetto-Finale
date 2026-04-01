function initGoogleAuth() {
    if (!window.google || !window.GOOGLE_CLIENT_ID) {
      setTimeout(initGoogleAuth, 100);
      return;
    }
  
    const loginBtn = document.getElementById('googleLoginBtn');
    const registerBtn = document.getElementById('googleRegisterBtn');
  
    if (!loginBtn && !registerBtn) return;
  
    google.accounts.id.initialize({
      client_id: window.GOOGLE_CLIENT_ID,
      callback: handleGoogleCredential
    });
  
    if (loginBtn) {
      loginBtn.innerHTML = "";
      google.accounts.id.renderButton(loginBtn, {
        theme: "outline",
        size: "large",
        text: "signin_with",
        shape: "pill",
        width: 320
      });
    }
  
    if (registerBtn) {
      registerBtn.innerHTML = "";
      google.accounts.id.renderButton(registerBtn, {
        theme: "outline",
        size: "large",
        text: "signup_with",
        shape: "pill",
        width: 320
      });
    }
  }
  
  function handleGoogleCredential(response) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `${window.BASE_URL}/index.php?r=auth/googleCallback`;
  
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'credential';
    input.value = response.credential;
  
    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
  }
  
  document.addEventListener('DOMContentLoaded', initGoogleAuth);