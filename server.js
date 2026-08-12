// استيراد المكتبات
require('dotenv').config();
const express = require('express');
const cors = require('cors');
const helmet = require('helmet');
const rateLimit = require('express-rate-limit');
const { connectDB } = require('./config/database');

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

// تحديد عدد الطلبات (للحماية من الهجمات)
const limiter = rateLimit({
  windowMs: 15 * 60 * 1000, // 15 دقيقة
  max: 100 // 100 طلب لكل IP
});
app.use('/api', limiter);

// توصيل قاعدة البيانات
connectDB();

// تعريف المسارات (APIs)
app.use('/api/auth', authRoutes);
app.use('/api/users', userRoutes);
app.use('/api/properties', propertyRoutes);
app.use('/api/referrals', referralRoutes);
app.use('/api/payments', paymentRoutes);

// صفحة رئيسية مؤقتة
app.get('/', (req, res) => {
  res.send('مرحباً بكم في منصة آت يونس تك! 🚀');
});

// بدء الخادم (مرة واحدة فقط!)
app.listen(PORT, '0.0.0.0', () => {
  console.log(`✅ الخادم يعمل على المنفذ ${PORT}`);
});