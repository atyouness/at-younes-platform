// استيراد المكتبات
require('dotenv').config();
const express = require('express');
const cors = require('cors');
const helmet = require('helmet');
const rateLimit = require('express-rate-limit');
const { connectDB } = require('./config/database');
const User = require('./models/User');
const path = require('path');

// استيراد المسارات
const authRoutes = require('./routes/auth');
const userRoutes = require('./routes/users');
const propertyRoutes = require('./routes/properties');
const referralRoutes = require('./routes/referrals');
const paymentRoutes = require('./routes/payments');

// إنشاء تطبيق Express
const app = express();
const PORT = process.env.PORT || 3000;

// إعدادات الأمان والأداء
app.use(helmet());
app.use(cors());
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// تحديد عدد الطلبات
const limiter = rateLimit({
  windowMs: 15 * 60 * 1000,
  max: 100
});
app.use('/api', limiter);

// ✅ خدمة الملفات الثابتة
app.use(express.static(path.join(__dirname, 'public')));

// ✅ توصيل قاعدة البيانات وإنشاء الجداول
// connectDB();

// التحقق من وجود الجدول وإنشائه إذا لزم الأمر
// User.ensureTable();

// تعريف المسارات (APIs)
app.use('/api/auth', authRoutes);
app.use('/api/users', userRoutes);
app.use('/api/properties', propertyRoutes);
app.use('/api/referrals', referralRoutes);
app.use('/api/payments', paymentRoutes);

// الصفحة الرئيسية
app.get('/', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'index.html'));
});

// ✅ مسار لوحة التحكم (يتطلب تسجيل الدخول)
app.get('/dashboard', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'dashboard.html'));
});

// بدء الخادم
app.listen(PORT, '0.0.0.0', () => {
  console.log(`✅ الخادم يعمل على المنفذ ${PORT}`);
});
