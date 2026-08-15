// استيراد المكتبات
require('dotenv').config();
const express = require('express');
const cors = require('cors');
const helmet = require('helmet');
const rateLimit = require('express-rate-limit');
const { connectDB } = require('./config/database');
const User = require('./models/User');
const path = require('path');
const nodemailer = require('nodemailer'); // ✅ إضافة Nodemailer
const crypto = require('crypto'); // ✅ لتوليد توكنات التفعيل

// استيراد المسارات
const authRoutes = require('./routes/auth');
const userRoutes = require('./routes/users');
const propertyRoutes = require('./routes/properties');
const referralRoutes = require('./routes/referrals');
const paymentRoutes = require('./routes/payments');

// إنشاء تطبيق Express
const app = express();
app.set('trust proxy', 1); // ✅ تفعيل الثقة بالبروكسي
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

// ============================================================
//  ✅ إعداد Nodemailer (لإرسال البريد الإلكتروني)
// ============================================================
const transporter = nodemailer.createTransport({
  host: 'smtp.hostinger.com',
  port: 465,
  secure: true, // يستخدم SSL
  auth: {
    user: 'info@atyouness.com', // أو slimanepro@atyouness.com
    pass: process.env.EMAIL_PASSWORD || 'UJN741ik85/*i' // استخدم متغير بيئي
  },
  tls: {
    rejectUnauthorized: false
  }
});

// ✅ دالة إرسال بريد التفعيل
async function sendVerificationEmail(email, username, verificationToken) {
  const verificationLink = `https://atyouness.com/api/auth/verify?token=${verificationToken}`;
  
  const mailOptions = {
    from: '"آت يونس تك" <info@atyouness.com>',
    to: email,
    subject: '🔐 تفعيل حسابك في آت يونس تك',
    html: `
      <div dir="rtl" style="font-family: 'Tajawal', sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background: #f8f9fa; border-radius: 12px;">
        <h2 style="color: #2563eb;">مرحباً ${username}،</h2>
        <p style="font-size: 16px; color: #2c3e50;">شكراً لتسجيلك في منصة <strong>آت يونس تك</strong>.</p>
        <p style="font-size: 16px; color: #2c3e50;">لتفعيل حسابك، يرجى النقر على الرابط التالي:</p>
        <div style="text-align: center; margin: 25px 0;">
          <a href="${verificationLink}" style="background: #2563eb; color: white; padding: 12px 30px; border-radius: 30px; text-decoration: none; font-weight: 600; display: inline-block;">تفعيل الحساب</a>
        </div>
        <p style="font-size: 14px; color: #6c757d;">إذا لم تقم بالتسجيل، يرجى تجاهل هذا البريد.</p>
        <hr style="border: 1px solid #e9ecef;">
        <p style="font-size: 12px; color: #6c757d; text-align: center;">© 2026 آت يونس تك - جميع الحقوق محفوظة</p>
      </div>
    `
  };

  try {
    await transporter.sendMail(mailOptions);
    console.log(`✅ تم إرسال بريد التفعيل إلى ${email}`);
    return true;
  } catch (error) {
    console.error('❌ فشل في إرسال بريد التفعيل:', error);
    return false;
  }
}

// تخزين مؤقت للتوكنات (في الإنتاج، استخدم قاعدة بيانات)
const verificationTokens = {};

// ============================================================
//  ✅ توصيل قاعدة البيانات وإنشاء الجداول
// ============================================================
connectDB();
User.ensureTable();

// ============================================================
//  ✅ تعريف المسارات (APIs)
// ============================================================
app.use('/api/auth', authRoutes);
app.use('/api/users', userRoutes);
app.use('/api/properties', propertyRoutes);
app.use('/api/referrals', referralRoutes);
app.use('/api/payments', paymentRoutes);

// ============================================================
//  ✅ الصفحات الثابتة
// ============================================================
// الصفحة الرئيسية
app.get('/', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'index.html'));
});

// لوحة التحكم
app.get('/dashboard', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'dashboard.html'));
});

// صفحة التسجيل
app.get('/register', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'register.html'));
});

// صفحة تسجيل الدخول
app.get('/login', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'login.html'));
});

// صفحة شبكة الإحالات
app.get('/referrals', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'referrals.html'));
});

// ============================================================
//  ✅ تصدير المتغيرات للاستخدام في ملفات أخرى
// ============================================================
module.exports = { app, sendVerificationEmail, verificationTokens };

// ============================================================
//  ✅ بدء الخادم
// ============================================================
app.listen(PORT, '0.0.0.0', () => {
  console.log(`✅ الخادم يعمل على المنفذ ${PORT}`);
});
