// services/emailService.js
const nodemailer = require('nodemailer');
const crypto = require('crypto');

// إعداد Nodemailer
const transporter = nodemailer.createTransport({
  host: 'smtp.hostinger.com',
  port: 465,
  secure: true,
  auth: {
    user: 'info@atyouness.com',
    pass: process.env.EMAIL_PASSWORD
  },
  tls: {
    rejectUnauthorized: false
  }
});

// تخزين مؤقت للتوكنات
const verificationTokens = {};

// دالة إرسال بريد التفعيل
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

module.exports = { sendVerificationEmail, verificationTokens };
