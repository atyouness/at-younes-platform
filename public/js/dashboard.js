// dashboard.js - إدارة الإحالات في لوحة التحكم

// تحميل إحصائيات الإحالات
async function loadReferralStats() {
  try {
    const token = localStorage.getItem('token');
    if (!token) {
      console.warn('⚠️ لا يوجد توكن، توجيه إلى تسجيل الدخول...');
      window.location.href = '/login.html';
      return;
    }

    const response = await fetch('/api/auth/referrals/stats', {
      headers: { 'Authorization': `Bearer ${token}` }
    });

    if (!response.ok) {
      if (response.status === 401) {
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        window.location.href = '/login.html';
        return;
      }
      throw new Error('فشل في تحميل البيانات');
    }

    const data = await response.json();

    // تحديث عرض كود الإحالة
    const user = JSON.parse(localStorage.getItem('user'));
    if (user && user.referralCode) {
      document.getElementById('referralCodeDisplay').textContent = user.referralCode;
    }

    // تحديث عدد الإحالات
    document.getElementById('referralCountDisplay').textContent = data.count || 0;

    // تحديث قائمة الإحالات
    const listContainer = document.getElementById('referralsList');
    if (data.referrals && data.referrals.length > 0) {
      listContainer.innerHTML = data.referrals.map(ref => `
        <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid #f0f0f0;">
          <span>👤 ${ref.username}</span>
          <span style="color:#6c757d;font-size:13px;">${new Date(ref.joinedAt).toLocaleDateString('ar-EG')}</span>
        </div>
      `).join('');
    } else {
      listContainer.innerHTML = '<p style="color:#6c757d;">لا توجد إحالات حتى الآن. شارك رابطك لدعوة الآخرين!</p>';
    }
  } catch (error) {
    console.error('❌ خطأ في تحميل إحصائيات الإحالات:', error);
    document.getElementById('referralsList').innerHTML = '<p style="color:#dc3545;">حدث خطأ في تحميل البيانات</p>';
  }
}

// نسخ رابط الإحالة
function setupCopyReferral() {
  const copyBtn = document.getElementById('copyReferralBtn');
  const messageDiv = document.getElementById('referralMessage');

  copyBtn.addEventListener('click', async function() {
    const user = JSON.parse(localStorage.getItem('user'));
    if (!user || !user.referralCode) {
      showMessage('⚠️ لا يوجد كود إحالة متاح', 'error');
      return;
    }

    const referralLink = `${window.location.origin}/register.html?ref=${user.referralCode}`;
    
    try {
      await navigator.clipboard.writeText(referralLink);
      showMessage('✅ تم نسخ رابط الإحالة بنجاح! شاركه الآن لدعوة الآخرين.', 'success');
    } catch (err) {
      // طريقة بديلة للنسخ إذا فشل Clipboard API
      const textArea = document.createElement('textarea');
      textArea.value = referralLink;
      document.body.appendChild(textArea);
      textArea.select();
      document.execCommand('copy');
      document.body.removeChild(textArea);
      showMessage('✅ تم نسخ رابط الإحالة بنجاح!', 'success');
    }
  });
}

// عرض الرسائل في لوحة التحكم
function showMessage(message, type) {
  const msgDiv = document.getElementById('referralMessage');
  msgDiv.textContent = message;
  msgDiv.className = `message ${type}`;
  setTimeout(() => { msgDiv.className = 'message'; }, 5000);
}

// تهيئة الصفحة
document.addEventListener('DOMContentLoaded', function() {
  loadReferralStats();
  setupCopyReferral();
});
