const express = require('express');
const jwt = require('jsonwebtoken');
const User = require('../models/User');
const router = express.Router();
const crypto = require('crypto');
// ✅ استيراد دوال البريد من الملف الجديد (بدون تبعية دائرية)
const { sendVerificationEmail, verificationTokens } = require('../services/emailService');

// توليد كود إحالة عشوائي
const generateReferralCode = () => {
  return Math.random().toString(36).substring(2, 8).toUpperCase();
};

// ============================================================
//  ✅ تسجيل مستخدم جديد (مع تفعيل تلقائي أو عبر البريد)
// ============================================================
router.post('/register', async (req, res) => {
  try {
    const { username, email, password, full_name, phone, whatsapp, referralCode, autoActivate } = req.body;

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
        console.log(`✅ مستخدم جديد مسجل عن طريق: ${referrer.username}`);
      } else {
        console.log(`⚠️ كود إحالة غير صالح: ${referralCode}`);
      }
    }

    // إنشاء المستخدم (غير مفعل)
    const newUser = await User.create({
      username,
      email,
      password,
      referralCode: generateReferralCode(),
      referredBy,
      full_name: full_name || null,
      phone: phone || null,
      whatsapp: whatsapp || null
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

// ============================================================
//  ✅ تسجيل الدخول
// ============================================================
router.post('/login', async (req, res) => {
  try {
    const { email, password } = req.body;

    const user = await User.findByEmail(email);
    if (!user) {
      return res.status(401).json({ message: 'البريد الإلكتروني أو كلمة المرور غير صحيحة' });
    }

    // التحقق من كلمة المرور
    const isPasswordValid = await User.comparePassword(password, user.password);
    if (!isPasswordValid) {
      return res.status(401).json({ message: 'البريد الإلكتروني أو كلمة المرور غير صحيحة' });
    }

    // التحقق من أن الحساب مفعل
    if (!user.is_active) {
      return res.status(401).json({ message: 'الحساب غير مفعل. يرجى تفعيله عبر البريد الإلكتروني.' });
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
        full_name: user.full_name,
        phone: user.phone,
        whatsapp: user.whatsapp,
        balance: user.balance,
        role: user.role,
        is_active: user.is_active
      }
    });
  } catch (error) {
    console.error('❌ خطأ في تسجيل الدخول:', error);
    res.status(500).json({ message: 'حدث خطأ في الخادم', error: error.message });
  }
});

// ============================================================
//  ✅ تفعيل الحساب عبر الرابط
// ============================================================
router.get('/verify', async (req, res) => {
  try {
    const { token } = req.query;
    if (!token) {
      return res.status(400).json({ message: 'رمز التفعيل مطلوب' });
    }

    const data = verificationTokens[token];
    if (!data) {
      return res.status(400).json({ message: 'رمز التفعيل غير صالح أو منتهي الصلاحية' });
    }

    if (Date.now() > data.expires) {
      delete verificationTokens[token];
      return res.status(400).json({ message: 'انتهت صلاحية رمز التفعيل' });
    }

    await User.activate(data.userId);
    delete verificationTokens[token];

    // صفحة نجاح التفعيل
    res.send(`
      <!DOCTYPE html>
      <html>
      <head><meta charset="UTF-8"><title>تفعيل الحساب</title></head>
      <body style="font-family: 'Tajawal', sans-serif; text-align: center; padding: 50px; direction: rtl;">
        <div style="max-width: 400px; margin: 0 auto; background: #f8f9fa; padding: 30px; border-radius: 12px;">
          <h2 style="color: #2563eb;">✅ تم تفعيل حسابك بنجاح!</h2>
          <p style="color: #2c3e50;">يمكنك الآن تسجيل الدخول إلى المنصة.</p>
          <a href="/login.html" style="display: inline-block; background: #2563eb; color: white; padding: 12px 30px; border-radius: 30px; text-decoration: none; font-weight: 600; margin-top: 10px;">تسجيل الدخول</a>
        </div>
      </body>
      </html>
    `);
  } catch (error) {
    console.error('❌ خطأ في التفعيل:', error);
    res.status(500).json({ message: 'حدث خطأ في الخادم' });
  }
});

// ============================================================
//  ✅ التحقق من صحة التوكن (الجلسة)
// ============================================================
router.get('/verify-session', async (req, res) => {
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
        role: user.role,
        is_active: user.is_active
      }
    });
  } catch (error) {
    console.error('❌ خطأ في التحقق:', error);
    res.status(401).json({ message: 'جلسة غير صالحة' });
  }
});

// ============================================================
//  ✅ تسجيل الخروج
// ============================================================
router.post('/logout', (req, res) => {
  res.json({ message: '✅ تم تسجيل الخروج بنجاح' });
});

// ============================================================
//  ✅ الحصول على إحصائيات الإحالات
// ============================================================
const query = 'SELECT id, username, email, referral_code, referred_by, full_name, phone, whatsapp, balance, role, is_active, profile_updated_at, ... FROM users WHERE id = ?';

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

// ============================================================
//  ✅ التحقق من صحة كود الإحالة
// ============================================================
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

// ============================================================
//  ✅ الحصول على شبكة الإحالات (المستويات 1، 2، 3)
// ============================================================
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

    // جلب الراعي (إذا وجد)
    let sponsor = null;
    if (currentUser.referred_by) {
      sponsor = await User.findById(currentUser.referred_by);
    }

    // جلب شبكة الإحالات
    const tree = await User.getReferralTree(userId);

    // حساب الرتبة
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
        teamSize: await User.getTeamSize(sponsor.id)
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

// ✅ إعادة إرسال رابط التفعيل
router.post('/resend-activation', async (req, res) => {
  try {
    const { email } = req.body;

    if (!email) {
      return res.status(400).json({ message: 'البريد الإلكتروني مطلوب' });
    }

    const user = await User.findByEmail(email);
    if (!user) {
      return res.status(404).json({ message: 'المستخدم غير موجود' });
    }

    if (user.is_active) {
      return res.status(400).json({ message: 'الحساب مفعل بالفعل' });
    }

    // إنشاء توكن جديد
    const token = crypto.randomBytes(32).toString('hex');
    verificationTokens[token] = { userId: user.id, expires: Date.now() + 3600000 };

    await sendVerificationEmail(email, user.username, token);

    res.json({ message: '✅ تم إعادة إرسال رابط التفعيل' });
  } catch (error) {
    console.error('❌ خطأ في إعادة إرسال التفعيل:', error);
    res.status(500).json({ message: 'حدث خطأ في الخادم' });
  }
});

// ✅ استعادة كلمة المرور (نسيت كلمة المرور)
router.post('/forgot-password', async (req, res) => {
  try {
    const { email } = req.body;

    if (!email) {
      return res.status(400).json({ message: 'البريد الإلكتروني مطلوب' });
    }

    const user = await User.findByEmail(email);
    if (!user) {
      return res.status(404).json({ message: 'المستخدم غير موجود' });
    }

    // إنشاء توكن استعادة
    const token = crypto.randomBytes(32).toString('hex');
    // يمكنك تخزين التوكن في قاعدة بيانات أو في memory
    // هذا مثال بسيط:
    resetTokens[token] = { userId: user.id, expires: Date.now() + 3600000 };

    // إرسال بريد استعادة كلمة المرور
    const resetLink = `https://atyouness.com/reset-password?token=${token}`;
    const mailOptions = {
      from: '"آت يونس تك" <info@atyouness.com>',
      to: email,
      subject: '🔑 استعادة كلمة المرور - آت يونس تك',
      html: `
        <div dir="rtl" style="font-family: 'Tajawal', sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
          <h2 style="color: #2563eb;">مرحباً ${user.username}،</h2>
          <p>لقد تلقينا طلباً لاستعادة كلمة المرور الخاصة بك.</p>
          <p>لإعادة تعيين كلمة المرور، يرجى النقر على الرابط التالي:</p>
          <div style="text-align: center; margin: 25px 0;">
            <a href="${resetLink}" style="background: #2563eb; color: white; padding: 12px 30px; border-radius: 30px; text-decoration: none; font-weight: 600;">استعادة كلمة المرور</a>
          </div>
          <p style="color: #6c757d;">إذا لم تطلب استعادة كلمة المرور، يرجى تجاهل هذا البريد.</p>
        </div>
      `
    };

    await transporter.sendMail(mailOptions);

    res.json({ message: '✅ تم إرسال رابط استعادة كلمة المرور' });
  } catch (error) {
    console.error('❌ خطأ في استعادة كلمة المرور:', error);
    res.status(500).json({ message: 'حدث خطأ في الخادم' });
  }
});

// ✅ الحصول على بيانات الملف الشخصي
router.get('/profile', async (req, res) => {
  try {
    const token = req.headers.authorization?.split(' ')[1];
    if (!token) {
      return res.status(401).json({ message: 'غير مصرح' });
    }

    const decoded = jwt.verify(token, process.env.JWT_SECRET || 'secret_key');
    const user = await User.findById(decoded.userId);
    if (!user) {
      return res.status(404).json({ message: 'المستخدم غير موجود' });
    }

    res.json(user);
  } catch (error) {
    console.error('❌ خطأ في جلب الملف الشخصي:', error);
    res.status(500).json({ message: 'حدث خطأ في الخادم' });
  }
});

// ✅ تحديث بيانات الملف الشخصي
router.put('/profile', async (req, res) => {
  try {
    const token = req.headers.authorization?.split(' ')[1];
    if (!token) {
      return res.status(401).json({ message: 'غير مصرح' });
    }

    const decoded = jwt.verify(token, process.env.JWT_SECRET || 'secret_key');
    const userId = decoded.userId;

    const {
      full_name, profession, birth_date, birth_place, address,
      phone, whatsapp, national_id, national_id_issue_date, national_id_issue_place,
      guardian_name, guardian_relation, guardian_birth_date, guardian_birth_place,
      guardian_national_id, guardian_national_id_issue_date, guardian_national_id_issue_place,
      heir_name, heir_relation, heir_national_id, heir_phone
    } = req.body;

    const query = `
      UPDATE users SET
        full_name = ?, profession = ?, birth_date = ?, birth_place = ?, address = ?,
        phone = ?, whatsapp = ?, national_id = ?, national_id_issue_date = ?, national_id_issue_place = ?,
        guardian_name = ?, guardian_relation = ?, guardian_birth_date = ?, guardian_birth_place = ?,
        guardian_national_id = ?, guardian_national_id_issue_date = ?, guardian_national_id_issue_place = ?,
        heir_name = ?, heir_relation = ?, heir_national_id = ?, heir_phone = ?
      WHERE id = ?
    `;

    const values = [
      full_name, profession, birth_date, birth_place, address,
      phone, whatsapp, national_id, national_id_issue_date, national_id_issue_place,
      guardian_name, guardian_relation, guardian_birth_date, guardian_birth_place,
      guardian_national_id, guardian_national_id_issue_date, guardian_national_id_issue_place,
      heir_name, heir_relation, heir_national_id, heir_phone,
      userId
    ];

    await pool.query(query, values);

    res.json({ message: '✅ تم تحديث البيانات بنجاح' });
  } catch (error) {
    console.error('❌ خطأ في تحديث الملف الشخصي:', error);
    res.status(500).json({ message: 'حدث خطأ في الخادم' });
  }
});

module.exports = router;
