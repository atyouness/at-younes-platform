// ============================================================
//  استيراد المكتبات والإعدادات
// ============================================================
require('dotenv').config();
const express = require('express');
const cors = require('cors');
const helmet = require('helmet');
const rateLimit = require('express-rate-limit');
const path = require('path');
const { connectDB } = require('./config/database');
const User = require('./models/User');
const path = require('path');

// استيراد المسارات
const authRoutes = require('./routes/auth');
const userRoutes = require('./routes/users');
const propertyRoutes = require('./routes/properties');
const referralRoutes = require('./routes/referrals');
const paymentRoutes = require('./routes/payments');

// ============================================================
//  إعداد تطبيق Express
// ============================================================
const app = express();
app.set('trust proxy', 1);
const PORT = process.env.PORT || 3000;

// إعداد محرك القوالب EJS
app.set('view engine', 'ejs');
app.set('views', path.join(__dirname, 'views'));

// ============================================================
//  إعدادات الأمان والأداء
// ============================================================
app.use(helmet());
app.use(cors());
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

const limiter = rateLimit({
  windowMs: 15 * 60 * 1000,
  max: 100
});
app.use('/api', limiter);

// خدمة الملفات الثابتة
app.use(express.static(path.join(__dirname, 'public')));

// ============================================================
//  توصيل قاعدة البيانات
// ============================================================
connectDB();
User.ensureTable();

// ============================================================
//  المسارات (API)
// ============================================================
app.use('/api/auth', authRoutes);
app.use('/api/users', userRoutes);
app.use('/api/properties', propertyRoutes);
app.use('/api/referrals', referralRoutes);
app.use('/api/payments', paymentRoutes);

// ============================================================
//  الصفحات (EJS)
// ============================================================
app.get('/', (req, res) => res.render('index', { title: 'الرئيسية' }));
app.get('/dashboard', (req, res) => res.render('dashboard', { title: 'لوحة التحكم' }));
app.get('/login', (req, res) => res.render('login', { title: 'تسجيل الدخول' }));
app.get('/register', (req, res) => res.render('register', { title: 'التسجيل' }));
app.get('/referrals', (req, res) => res.render('referrals', { title: 'شبكة الإحالات' }));
app.get('/profile', (req, res) => res.render('profile', { title: 'حسابي' }));

// ============================================================
//  معالج الأخطاء (يجب أن يكون آخر middleware)
// ============================================================
const errorHandler = require('./middleware/errorHandler');
app.use(errorHandler);

// ============================================================
//  بدء الخادم
// ============================================================
app.listen(PORT, '0.0.0.0', () => {
  console.log(`✅ الخادم يعمل على المنفذ ${PORT}`);
});
