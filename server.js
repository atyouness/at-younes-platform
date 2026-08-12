// استيراد المكتبات
require('dotenv').config();
const express = require('express');
const cors = require('cors');
const helmet = require('helmet');
const rateLimit = require('express-rate-limit');
const path = require('path');

// إنشاء تطبيق Express
const app = express();
const PORT = process.env.PORT || 3000;

// إعدادات الأمان والأداء
app.use(helmet());
app.use(cors());
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// تحديد عدد الطلبات (للحماية من الهجمات)
const limiter = rateLimit({
  windowMs: 15 * 60 * 1000, // 15 دقيقة
  max: 100 // 100 طلب لكل IP
});
app.use('/api', limiter);

// ✅ خدمة الملفات الثابتة من مجلد public
app.use(express.static(path.join(__dirname, 'public')));

// ✅ تأكد من وجود مجلد public
const fs = require('fs');
const publicPath = path.join(__dirname, 'public');
if (!fs.existsSync(publicPath)) {
  console.log('⚠️ مجلد public غير موجود، سيتم إنشاؤه...');
  fs.mkdirSync(publicPath, { recursive: true });
}

// ✅ الصفحة الرئيسية (تعرض index.html من مجلد public)
app.get('/', (req, res) => {
  const indexPath = path.join(__dirname, 'public', 'index.html');
  if (fs.existsSync(indexPath)) {
    res.sendFile(indexPath);
  } else {
    // إذا كان index.html غير موجود، عرض رسالة ترحيب
    res.send(`
      <h1>مرحباً بكم في منصة آت يونس تك! 🚀</h1>
      <p>تم تشغيل الخادم بنجاح، لكن ملف index.html غير موجود في مجلد public.</p>
      <p>يرجى إضافة ملف index.html إلى مجلد public.</p>
    `);
  }
});

// ✅ مسارات API مؤقتة (للتأكد من أن الخادم يعمل)
app.get('/api/status', (req, res) => {
  res.json({ 
    status: '✅ الخادم يعمل بنجاح!',
    time: new Date().toISOString(),
    publicExists: fs.existsSync(publicPath)
  });
});

// ✅ معالج الأخطاء (لتجنب تعطل التطبيق)
app.use((err, req, res, next) => {
  console.error('❌ خطأ:', err.stack);
  res.status(500).json({ 
    message: 'حدث خطأ في الخادم', 
    error: err.message 
  });
});

// ✅ بدء الخادم
app.listen(PORT, '0.0.0.0', () => {
  console.log(`✅ الخادم يعمل على المنفذ ${PORT}`);
  console.log(`📁 مجلد public: ${publicPath}`);
  console.log(`📄 ملف index.html موجود: ${fs.existsSync(path.join(publicPath, 'index.html'))}`);
});
