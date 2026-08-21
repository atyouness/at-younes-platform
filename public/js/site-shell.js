(() => {
  const stylesheet = document.createElement('link');
  stylesheet.rel = 'stylesheet';
  stylesheet.href = '/css/site-shell.css';
  document.head.append(stylesheet);

  const path = window.location.pathname;
  const page = path.split('/').pop() || 'index.html';
  let currentUser = null;
  try { currentUser = JSON.parse(localStorage.getItem('user') || 'null'); } catch (error) { localStorage.removeItem('user'); }
  const isAuthenticated = Boolean(localStorage.getItem('token') && currentUser);
  const isActive = isAuthenticated && currentUser.is_active !== false && currentUser.status !== 'pending';
  const isAdmin = isActive && [1, 3].includes(Number(currentUser.role_id));
  const displayName = currentUser ? `${currentUser.first_name || ''} ${currentUser.last_name || ''}`.trim() || currentUser.username || 'المستخدم' : '';
  const links = [
    ['/', 'الرئيسية'],
    ['/products.html', 'العروض'],
    ['/investments.html', 'الاستثمارات'],
    ['/projects.html', 'المشاريع'],
    ['/tasks.html', 'المهام'],
    ['/dashboard.html', 'لوحة التحكم']
  ];

  const accountTarget = isActive ? '#' : '/register.html';
  const accountMenu = isActive ? `
    <div class="account-menu" id="accountMenu" hidden>
      <div class="account-menu__identity"><strong>${displayName}</strong><span>${currentUser.username || ''}</span><span>${currentUser.email || ''}</span></div>
      <hr>
      <a href="/profile.html#basic">البيانات الأساسية للحساب</a>
      <a href="/profile.html#identity">بيانات الهوية الرسمية</a>
      <a href="/profile.html#guardian">👨‍👦 الولي الشرعي</a>
      <a href="/profile.html#heir">👨‍👩‍👧 الوريث / المستفيد المسمّى</a>
      <a href="/finance.html">الفواتير</a>
      <a href="/profile.html#security">أمان</a>
      <a href="/profile.html#activity">أنشطة الحساب</a>
      <a href="/profile.html#settings">إعدادات الحساب</a>
      <label class="account-menu__language">اللغة<select data-language><option value="ar">العربية</option><option value="en">English</option><option value="fr">Francais</option></select></label>
      <button type="button" class="account-menu__logout" data-logout>تسجيل الخروج</button>
    </div>` : '';

  const header = document.createElement('header');
  header.className = 'site-header';
  header.innerHTML = `
    <div class="site-header__inner">
      <div class="site-header__main"><a class="site-brand" href="/">آت يونس <span>تك</span></a><nav class="site-nav" aria-label="التنقل الرئيسي">${links.map(([href, label]) => `<a href="${href}"${path === href || (href === '/' && page === 'index.html') ? ' aria-current="page"' : ''}>${label}</a>`).join('')}</nav></div>
      <div class="site-header__tools">
        <div class="account-control"><a class="icon-control" href="${accountTarget}"${isActive ? ' id="accountToggle" aria-expanded="false" aria-controls="accountMenu"' : ''} title="${isActive ? 'فتح قائمة الحساب' : 'إنشاء حساب'}" aria-label="${isActive ? 'قائمة الحساب' : 'إنشاء حساب'}">👤</a>${accountMenu}</div>
        <label class="language-control" title="اختيار اللغة">🌐<select data-language aria-label="اختيار اللغة"><option value="ar">العربية</option><option value="en">English</option><option value="fr">Francais</option></select></label>
        ${isAdmin ? '<a class="admin-link" href="/admin.html">الإدارة</a>' : ''}
      </div>
    </div>`;

  const footer = document.createElement('footer');
  footer.className = 'site-footer';
  footer.innerHTML = `<div class="site-footer__inner"><div><h2>آت يونس تك</h2><p>منصة جزائرية تجمع الترويج المسؤول، متابعة المشاريع، والعروض المحلية في تجربة واضحة.</p></div><div class="site-footer__links"><a href="/">الرئيسية</a><a href="/products.html">العروض</a><a href="/finance.html">الإيداع والسحب</a>${isActive ? '<a href="/profile.html">👤 حسابي</a><button class="site-footer__logout" type="button" data-logout>تسجيل الخروج</button>' : '<a href="/login.html">تسجيل الدخول</a><a href="/register.html">إنشاء حساب</a>'}</div></div><div class="site-footer__bottom">© 2026 آت يونس تك. جميع الحقوق محفوظة.</div>`;

  document.body.classList.add('site-shell-ready');
  document.body.prepend(header);
  document.body.append(footer);

  const toggle = document.getElementById('accountToggle');
  const menu = document.getElementById('accountMenu');
  if (toggle && menu) {
    toggle.addEventListener('click', (event) => { event.preventDefault(); menu.hidden = !menu.hidden; toggle.setAttribute('aria-expanded', String(!menu.hidden)); });
    document.addEventListener('click', (event) => { if (!event.target.closest('.account-control')) { menu.hidden = true; toggle.setAttribute('aria-expanded', 'false'); } });
  }
  document.querySelectorAll('[data-logout]').forEach((button) => button.addEventListener('click', () => { localStorage.removeItem('token'); localStorage.removeItem('user'); window.location.href = '/login.html'; }));
  document.querySelectorAll('[data-language]').forEach((select) => select.addEventListener('change', () => { localStorage.setItem('preferredLanguage', select.value); }));
})();
