const express = require('express');
const jwt = require('jsonwebtoken');
const User = require('../models/User');
const router = express.Router();

// توليد كود إحالة عشوائي
const generateReferralCode = () => {
  return Math.random().toString(36).substring(2, 8).toUpperCase();
};

// ✅ تسجيل مستخدم جديد (قاعدة بيانات)
router.post('/register', async (req, res) => {
  try {
    const { username, email, password, referralCode } = req.body;

    // التحقق من وجود البريد الإلكتروني
    const existingUser = await User.findByEmail(email);
    if (existingUser) {
      return res.status(400).json({ message: 'البريد الإلكتروني مسجل بالفعل' });
    }

// التحقق من وجود كود الإحالة (إذا تم إدخاله)
let referredBy = null;
if (referralCode) {
  const referrer = await User.findByReferralCode(referralCode);
  if (referrer) {
    referredBy = referrer.id;
    console.log(`✅ مستخدم جديد مسجل عن طريق: ${referrer.username} (ID: ${referrer.id})`);
  } else {
    console.log(`⚠️ كود إحالة غير صالح: ${referralCode}`);
  }
}

// إنشاء المستخدم مع referredBy
const newUser = await User.create({
  username,
  email,
  password,
  referralCode: generateReferralCode(),
  referredBy // <-- إضافة هذا السطر
});

   // إنشاء توكن JWT (إذا أردت)
    const token = jwt.sign(
      { userId: newUser.id, email: newUser.email },
      process.env.JWT_SECRET || 'secret_key',
      { expiresIn: '7d' }
    );

    res.status(201).json({
      message: '✅ تم التسجيل بنجاح',
      token,
      user: {
        id: newUser.id,
        username: newUser.username,
        email: newUser.email,
        referralCode: newUser.referral_code
      }
    });
  } catch (error) {
    console.error('❌ خطأ في التسجيل:', error);
    res.status(500).json({ message: 'حدث خطأ في الخادم', error: error.message });
  }
});

// ✅ تسجيل الدخول (قاعدة بيانات)
router.post('/login', async (req, res) => {
  try {
    const { email, password } = req.body;

    // البحث عن المستخدم
    const user = await User.findByEmail(email);
    if (!user) {
      return res.status(401).json({ message: 'البريد الإلكتروني أو كلمة المرور غير صحيحة' });
    }

    // التحقق من كلمة المرور
    const isPasswordValid = await User.comparePassword(password, user.password);
    if (!isPasswordValid) {
      return res.status(401).json({ message: 'البريد الإلكتروني أو كلمة المرور غير صحيحة' });
    }

    // إنشاء توكن JWT
    const token = jwt.sign(
      { userId: user.id, email: user.email },
      process.env.JWT_SECRET || 'secret_key',
      { expiresIn: '7d' }
    );

    res.json({
      message: '✅ تم تسجيل الدخول بنجاح',
      token,
      user: {
        id: user.id,
        username: user.username,
        email: user.email,
        referralCode: user.referral_code,
        balance: user.balance,
        role: user.role
      }
    });
  } catch (error) {
    console.error('❌ خطأ في تسجيل الدخول:', error);
    res.status(500).json({ message: 'حدث خطأ في الخادم', error: error.message });
  }
});

// ✅ التحقق من صحة التوكن (للحفاظ على الجلسة)
router.get('/verify', async (req, res) => {
  try {
    const token = req.headers.authorization?.split(' ')[1];
    if (!token) {
      return res.status(401).json({ message: 'غير مصرح' });
    }

    const decoded = jwt.verify(token, process.env.JWT_SECRET || 'secret_key');
    const user = await User.findById(decoded.userId);
    if (!user) {
      return res.status(401).json({ message: 'المستخدم غير موجود' });
    }

    res.json({
      message: '✅ تم التحقق من الجلسة',
      user: {
        id: user.id,
        username: user.username,
        email: user.email,
        referralCode: user.referral_code,
        balance: user.balance,
        role: user.role
      }
    });
  } catch (error) {
    console.error('❌ خطأ في التحقق:', error);
    res.status(401).json({ message: 'جلسة غير صالحة' });
  }
});

// ✅ تسجيل الخروج
router.post('/logout', (req, res) => {
  res.json({ message: '✅ تم تسجيل الخروج بنجاح' });
});

// ✅ الحصول على إحصائيات الإحالات
router.get('/referrals/stats', async (req, res) => {
  try {
    const token = req.headers.authorization?.split(' ')[1];
    if (!token) {
      return res.status(401).json({ message: 'غير مصرح' });
    }

    const decoded = jwt.verify(token, process.env.JWT_SECRET || 'secret_key');
    const userId = decoded.userId;

    const count = await User.getReferralCount(userId);
    const referrals = await User.getReferrals(userId);

    res.json({
      count,
      referrals: referrals.map(ref => ({
        id: ref.id,
        username: ref.username,
        email: ref.email,
        joinedAt: ref.created_at
      }))
    });
  } catch (error) {
    console.error('❌ خطأ في جلب إحصائيات الإحالات:', error);
    res.status(500).json({ message: 'حدث خطأ في الخادم' });
  }
});

// ✅ التحقق من صحة كود الإحالة (للواجهة)
router.get('/referrals/validate/:code', async (req, res) => {
  try {
    const { code } = req.params;
    const user = await User.findByReferralCode(code);
    if (user) {
      res.json({ valid: true, username: user.username });
    } else {
      res.json({ valid: false });
    }
  } catch (error) {
    console.error('❌ خطأ في التحقق من كود الإحالة:', error);
    res.status(500).json({ message: 'حدث خطأ في الخادم' });
  }
});

module.exports = router;
