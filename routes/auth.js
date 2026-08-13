const express = require('express');
const router = express.Router();

// تخزين مؤقت للمستخدمين (في الذاكرة)
const users = [];

// توليد كود إحالة عشوائي
const generateReferralCode = () => {
  return Math.random().toString(36).substring(2, 8).toUpperCase();
};

// ✅ تسجيل مستخدم جديد (تجريبي)
router.post('/register', (req, res) => {
  try {
    const { username, email, password, referralCode } = req.body;

    // التحقق من وجود البريد الإلكتروني
    const existingUser = users.find(u => u.email === email);
    if (existingUser) {
      return res.status(400).json({ message: 'البريد الإلكتروني مسجل بالفعل' });
    }

    // إنشاء مستخدم جديد
    const newUser = {
      id: users.length + 1,
      username,
      email,
      password, // بدون تشفير للتجربة
      referralCode: generateReferralCode(),
      balance: 0,
      role: 'user',
      createdAt: new Date().toISOString()
    };
    users.push(newUser);

    res.status(201).json({
      message: '✅ تم التسجيل بنجاح (تجريبي)',
      user: {
        id: newUser.id,
        username: newUser.username,
        email: newUser.email,
        referralCode: newUser.referralCode
      }
    });
  } catch (error) {
    console.error('❌ خطأ في التسجيل:', error);
    res.status(500).json({ message: 'حدث خطأ في الخادم' });
  }
});

// ✅ تسجيل الدخول (تجريبي)
router.post('/login', (req, res) => {
  try {
    const { email, password } = req.body;

    const user = users.find(u => u.email === email && u.password === password);
    if (!user) {
      return res.status(401).json({ message: 'البريد الإلكتروني أو كلمة المرور غير صحيحة' });
    }

    res.json({
      message: '✅ تم تسجيل الدخول بنجاح (تجريبي)',
      user: {
        id: user.id,
        username: user.username,
        email: user.email,
        referralCode: user.referralCode,
        balance: user.balance,
        role: user.role
      }
    });
  } catch (error) {
    console.error('❌ خطأ في تسجيل الدخول:', error);
    res.status(500).json({ message: 'حدث خطأ في الخادم' });
  }
});

// ✅ التحقق من الجلسة (تجريبي)
router.get('/verify', (req, res) => {
  res.json({ message: '✅ الجلسة صالحة (تجريبي)' });
});

// ✅ تسجيل الخروج
router.post('/logout', (req, res) => {
  res.json({ message: '✅ تم تسجيل الخروج بنجاح' });
});

module.exports = router;
