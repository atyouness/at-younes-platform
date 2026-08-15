const express = require('express');
const jwt = require('jsonwebtoken');
const User = require('../models/User');
const router = express.Router();

// توليد كود إحالة عشوائي
const generateReferralCode = () => {
  return Math.random().toString(36).substring(2, 8).toUpperCase();
};

// ✅ تسجيل مستخدم جديد
router.post('/register', async (req, res) => {
  try {
    const { username, email, password, full_name, phone, whatsapp, referralCode } = req.body;

    // التحقق من وجود البريد الإلكتروني
    const existingUser = await User.findByEmail(email);
    if (existingUser) {
      return res.status(400).json({ message: 'البريد الإلكتروني مسجل بالفعل' });
    }

    // التحقق من وجود اسم المستخدم
    const existingUsername = await User.findByUsername(username);
    if (existingUsername) {
      return res.status(400).json({ message: 'اسم المستخدم غير متاح' });
    }

    // التحقق من كود الإحالة
    let referredBy = null;
    if (referralCode) {
      const referrer = await User.findByReferralCode(referralCode);
      if (referrer) {
        referredBy = referrer.id;
     }

    // إنشاء المستخدم
    const newUser = await User.create({
      username,
      email,
      password,
      referralCode: generateReferralCode(),
      referredBy,
      full_name: full_name || null,
      phone: phone || null,
      whatsapp: whatsapp || null
      is_active: false
    });

    let isActivated = false;
    if (autoActivate === true) {
      // تفعيل تلقائي (للمسوقين الرئيسيين)
      await User.activate(newUser.id);
      isActivated = true;
    } else {
      // إرسال بريد التفعيل
      const token = crypto.randomBytes(32).toString('hex');
      verificationTokens[token] = { userId: newUser.id, expires: Date.now() + 3600000 };
      await sendVerificationEmail(email, username, token);
    }
    
    // إنشاء توكن JWT
    const token = jwt.sign(
      { userId: newUser.id, email: newUser.email },
      process.env.JWT_SECRET || 'secret_key',
      { expiresIn: '7d' }
    );

    res.status(201).json({
      message: isActivated
        ? '✅ تم التسجيل وتفعيل الحساب بنجاح!'
        : '✅ تم التسجيل بنجاح! يرجى تفعيل حسابك عبر البريد الإلكتروني.',
      token,
      user: {
        id: newUser.id,
        username: newUser.username,
        email: newUser.email,
        referralCode: newUser.referral_code,
        full_name: newUser.full_name,
        phone: newUser.phone,
        whatsapp: newUser.whatsapp,
        balance: newUser.balance || 0,
        role: newUser.role || 'user',
        is_active: isActivated || false
      }
    });
  } catch (error) {
    console.error('❌ خطأ في التسجيل:', error);
    res.status(500).json({ message: 'حدث خطأ في الخادم', error: error.message });
  }
});

// ✅ تسجيل الدخول
router.post('/login', async (req, res) => {
  try {
    const { email, password } = req.body;

    const user = await User.findByEmail(email);
    if (!user) {
      return res.status(401).json({ message: 'البريد الإلكتروني أو كلمة المرور غير صحيحة' });
    }

    const isPasswordValid = await User.comparePassword(password, user.password);
    if (!isPasswordValid) {
      return res.status(401).json({ message: 'البريد الإلكتروني أو كلمة المرور غير صحيحة' });
    }

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
        full_name: user.full_name,
        phone: user.phone,
        whatsapp: user.whatsapp,
        balance: user.balance,
        role: user.role
      }
    });
  } catch (error) {
    console.error('❌ خطأ في تسجيل الدخول:', error);
    res.status(500).json({ message: 'حدث خطأ في الخادم', error: error.message });
  }
});

// ✅ التحقق من صحة التوكن
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
        full_name: user.full_name,
        phone: user.phone,
        whatsapp: user.whatsapp,
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
        full_name: ref.full_name,
        phone: ref.phone,
        joinedAt: ref.created_at
      }))
    });
  } catch (error) {
    console.error('❌ خطأ في جلب إحصائيات الإحالات:', error);
    res.status(500).json({ message: 'حدث خطأ في الخادم' });
  }
});

// ✅ التحقق من صحة كود الإحالة
router.get('/referrals/validate/:code', async (req, res) => {
  try {
    const { code } = req.params;
    const user = await User.findByReferralCode(code);
    if (user) {
      res.json({ valid: true, username: user.username, full_name: user.full_name });
    } else {
      res.json({ valid: false });
    }
  } catch (error) {
    console.error('❌ خطأ في التحقق من كود الإحالة:', error);
    res.status(500).json({ message: 'حدث خطأ في الخادم' });
  }
});

// ✅ الحصول على شبكة الإحالات
router.get('/referrals/tree', async (req, res) => {
  try {
    const token = req.headers.authorization?.split(' ')[1];
    if (!token) {
      return res.status(401).json({ message: 'غير مصرح' });
    }

    const decoded = jwt.verify(token, process.env.JWT_SECRET || 'secret_key');
    const userId = decoded.userId;

    const currentUser = await User.findById(userId);
    if (!currentUser) {
      return res.status(404).json({ message: 'المستخدم غير موجود' });
    }

    let sponsor = null;
    if (currentUser.referred_by) {
      sponsor = await User.findById(currentUser.referred_by);
    }

    const tree = await User.getReferralTree(userId);

    const totalTeam = tree.level1.length + tree.level2.length + tree.level3.length;
    let userRank = '👤 عضو';
    if (totalTeam >= 20) userRank = '🎖️ قائد';
    else if (totalTeam >= 10) userRank = '🏅 نقيب';
    else if (totalTeam >= 5) userRank = '⭐ عضو مميز';

    res.json({
      totalReferrals: tree.level1.length,
      totalTeam,
      userRank,
      sponsor: sponsor ? {
        id: sponsor.id,
        username: sponsor.username,
        full_name: sponsor.full_name || sponsor.username,
        phone: sponsor.phone || null,
        teamSize: await User.getReferralCount(sponsor.id)
      } : null,
      level1: tree.level1,
      level2: tree.level2,
      level3: tree.level3
    });
  } catch (error) {
    console.error('❌ خطأ في جلب شبكة الإحالات:', error);
    res.status(500).json({ message: 'حدث خطأ في الخادم' });
  }
});

module.exports = router;
