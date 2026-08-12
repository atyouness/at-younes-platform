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

// خدمة الملفات الثابتة من مجلد public
app.use(express.static('public'));

// الصفحة الرئيسية (تعرض ملف index.html من مجلد public)
app.get('/', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'index.html'));
});

// مسارات API مؤقتة (لتجنب أخطاء "Cannot find module")
app.get('/api', (req, res) => {
  res.json({ message: '✅ منصة آت يونس تك API تعمل بنجاح!' });
});

app.post('/api/auth/login', (req, res) => {
  res.json({ message: '✅ تم تسجيل الدخول بنجاح (مؤقت)' });
});

app.post('/api/auth/register', (req, res) => {
  res.json({ message: '✅ تم التسجيل بنجاح (مؤقت)' });
});

app.get('/api/users', (req, res) => {
  res.json({ message: '✅ مسار المستخدمين يعمل (مؤقت)' });
});

app.get('/api/properties', (req, res) => {
  res.json({ message: '✅ مسار العقارات يعمل (مؤقت)' });
});

app.get('/api/referrals', (req, res) => {
  res.json({ message: '✅ مسار الإحالات يعمل (مؤقت)' });
});

app.get('/api/payments', (req, res) => {
  res.json({ message: '✅ مسار المدفوعات يعمل (مؤقت)' });
});

// معالج الأخطاء (لتجنب تعطل التطبيق)
app.use((err, req, res, next) => {
  console.error(err.stack);
  res.status(500).json({ message: 'حدث خطأ في الخادم', error: err.message });
});

// بدء الخادم
app.listen(PORT, '0.0.0.0', () => {
  console.log(`✅ الخادم يعمل على المنفذ ${PORT}`);
});
