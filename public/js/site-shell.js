(() => {
  const stylesheet = document.createElement('link');
  stylesheet.rel = 'stylesheet';
  stylesheet.href = '/css/site-shell.css';
  document.head.append(stylesheet);

  const path = window.location.pathname;
  const page = path.split('/').pop() || 'index.html';
  const currentUser = JSON.parse(localStorage.getItem('user') || 'null');
  const isAuthenticated = Boolean(localStorage.getItem('token') && currentUser);
  const isAdmin = currentUser && [1, 3].includes(Number(currentUser.role_id));
  const displayName = currentUser ? `${currentUser.first_name || ''} ${currentUser.last_name || ''}`.trim() || currentUser.username : '';
  const links = [
    ['/', 'الرئيسية'],
    ['/products.html', 'العروض'],
    ['/investments.html', 'الاستثمارات'],
    ['/projects.html', 'المشاريع'],
    ['/tasks.html', 'المهام'],
    ['/dashboard.html', 'لوحة التحكم']
  ];

  const header = document.createElement('header');
  header.className = 'site-header';
  header.innerHTML = `
    <div class="site-header__inner">
      <a class="site-brand" href="/">آت يونس <span>تك</span></a>
      <nav class="site-nav" aria-label="التنقل الرئيسي">
        ${links.map(([href, label]) => `<a href="${href}"${path === href || (href === '/' && page === 'index.html') ? ' aria-current="page"' : ''}>${label}</a>`).join('')}
        ${isAuthenticated ? `<span class="site-nav__welcome">مرحباً، ${displayName} 👋</span><a href="/profile.html">👤 حسابي</a><button class="site-nav__logout" type="button" data-logout>تسجيل الخروج</button>${isAdmin ? '<a href="/admin.html">لوحة الإدارة</a>' : ''}` : '<a href="/login.html">دخول</a><a class="site-nav__action" href="/register.html">انضم الآن</a>'}
      </nav>
    </div>`;

  const footer = document.createElement('footer');
  footer.className = 'site-footer';
  footer.innerHTML = `
    <div class="site-footer__inner">
      <div><h2>آت يونس تك</h2><p>منصة جزائرية تجمع الترويج المسؤول، فرص المشاركة، ومتابعة المشاريع والعروض في تجربة رقمية واضحة.</p></div>
      <div class="site-footer__links"><a href="/">الرئيسية</a><a href="/products.html">العروض</a><a href="/finance.html">الإيداع والسحب</a>${isAuthenticated ? `<a href="/profile.html">👤 حسابي</a><button class="site-footer__logout" type="button" data-logout>تسجيل الخروج</button>${isAdmin ? '<a href="/admin.html">لوحة الإدارة</a>' : ''}` : '<a href="/login.html">تسجيل الدخول</a><a href="/register.html">إنشاء حساب</a>'}</div>
    </div>
    <div class="site-footer__bottom">© 2026 آت يونس تك. جميع الحقوق محفوظة.</div>`;

  document.body.classList.add('site-shell-ready');
  document.body.prepend(header);
  document.body.append(footer);
  document.querySelectorAll('[data-logout]').forEach((button) => button.addEventListener('click', () => {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    window.location.href = '/login.html';
  }));
})();
