// التعامل مع نموذج التسجيل
document.getElementById('registerForm')?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const username = document.getElementById('username').value;
  const email = document.getElementById('email').value;
  const password = document.getElementById('password').value;
  const referralCode = document.getElementById('referralCode').value;

  try {
    const response = await fetch('/api/auth/register', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ username, email, password, referralCode })
    });
    const data = await response.json();
    showMessage(data.message, response.ok ? 'success' : 'error');
    if (response.ok) {
      localStorage.setItem('token', data.token);
      localStorage.setItem('user', JSON.stringify(data.user));
      setTimeout(() => window.location.href = '/dashboard.html', 2000);
    }
  } catch (error) {
    showMessage('حدث خطأ في الاتصال بالخادم', 'error');
  }
});

// التعامل مع نموذج تسجيل الدخول
document.getElementById('loginForm')?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const email = document.getElementById('email').value;
  const password = document.getElementById('password').value;

  try {
    const response = await fetch('/api/auth/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, password })
    });
    const data = await response.json();
    showMessage(data.message, response.ok ? 'success' : 'error');
    if (response.ok) {
      localStorage.setItem('token', data.token);
      localStorage.setItem('user', JSON.stringify(data.user));
      setTimeout(() => window.location.href = '/dashboard.html', 2000);
    }
  } catch (error) {
    showMessage('حدث خطأ في الاتصال بالخادم', 'error');
  }
});

// عرض الرسائل
function showMessage(message, type) {
  const msgDiv = document.getElementById('message');
  msgDiv.textContent = message;
  msgDiv.className = type;
  setTimeout(() => { msgDiv.textContent = ''; msgDiv.className = ''; }, 5000);
}
