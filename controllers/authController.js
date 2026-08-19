const jwt = require('jsonwebtoken');
const crypto = require('crypto');
const User = require('../models/User');

const createToken = (user) => jwt.sign(
  { userId: user.id, roleId: user.role_id },
  process.env.JWT_SECRET,
  { expiresIn: process.env.JWT_EXPIRES_IN || '1d' }
);

const register = async (req, res, next) => {
  try {
    const firstName = req.body.firstName || req.body.first_name;
    const lastName = req.body.lastName || req.body.last_name;
    const { username, email, phone, whatsapp, password } = req.body;
    const referralCode = req.body.referralCode || req.body.referral_code;
    if (!firstName || !lastName || !email || !password) {
      return res.status(400).json({ message: 'الاسم الأول واللقب والبريد وكلمة المرور مطلوبة' });
    }
    if (password.length < 8) {
      return res.status(400).json({ message: 'يجب أن تتكون كلمة المرور من 8 أحرف على الأقل' });
    }
    if (await User.findByEmail(email)) {
      return res.status(409).json({ message: 'البريد الإلكتروني مستخدم بالفعل' });
    }
    if (username && await User.findByUsername(username)) {
      return res.status(409).json({ message: 'اسم المستخدم مستخدم بالفعل' });
    }

    const parent = referralCode ? await User.findByReferralCode(referralCode) : null;
    const user = await User.create({
      firstName,
      lastName,
      username: username || null,
      email,
      phone: phone || null,
      whatsapp: whatsapp || null,
      password,
      referralCode: crypto.randomBytes(5).toString('hex').toUpperCase(),
      parentUserId: parent ? parent.id : null
    });

    return res.status(201).json({ message: 'تم إنشاء الحساب بنجاح', user, token: createToken(user) });
  } catch (error) {
    return next(error);
  }
};

const login = async (req, res, next) => {
  try {
    const { email, password } = req.body;
    if (!email || !password) {
      return res.status(400).json({ message: 'البريد الإلكتروني وكلمة المرور مطلوبان' });
    }
    const user = await User.findByEmail(email);
    if (!user || !(await User.comparePassword(password, user.password_hash))) {
      return res.status(401).json({ message: 'بيانات الدخول غير صحيحة' });
    }
    if (user.status !== 'active' || !user.is_active) {
      return res.status(403).json({ message: 'الحساب غير مفعل أو موقوف' });
    }

    return res.json({ message: 'تم تسجيل الدخول بنجاح', user: User.publicFields(user), token: createToken(user) });
  } catch (error) {
    return next(error);
  }
};

module.exports = { register, login };
