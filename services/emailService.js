const nodemailer = require('nodemailer');

const emailUser = process.env.EMAIL_USER || 'info@atyouness.com';
const requiredEmailSettings = ['EMAIL_PASSWORD'];

const transporter = nodemailer.createTransport({
  host: process.env.EMAIL_HOST || 'smtp.hostinger.com',
  port: Number(process.env.EMAIL_PORT || 465),
  secure: String(process.env.EMAIL_SECURE || 'true') === 'true',
  auth: {
    user: emailUser,
    pass: process.env.EMAIL_PASSWORD
  }
});

async function sendVerificationEmail({ email, name, token }) {
  const missing = requiredEmailSettings.filter((setting) => !process.env[setting]);
  if (missing.length) {
    throw new Error(`إعدادات البريد ناقصة: ${missing.join(', ')}`);
  }

  const appUrl = process.env.APP_URL || 'https://atyouness.com';
  const verificationLink = `${appUrl}/api/auth/verify?token=${encodeURIComponent(token)}`;
  await transporter.sendMail({
    from: process.env.EMAIL_FROM || `آت يونس تك <${emailUser}>`,
    to: email,
    subject: 'مرحبًا بك في آت يونس تك | تفعيل الحساب',
    html: `
      <div dir="rtl" style="font-family:Arial,sans-serif;max-width:620px;margin:auto;padding:28px;background:#f5f7f6;color:#17212b">
        <h2 style="color:#16715b">مرحبًا ${name || 'بك'}،</h2>
        <p>شكرًا لانضمامك إلى منصة <strong>آت يونس تك</strong>.</p>
        <p>لتفعيل حسابك والبدء باستخدام المنصة، اضغط على الزر التالي:</p>
        <p style="text-align:center;margin:28px 0"><a href="${verificationLink}" style="background:#16715b;color:#fff;padding:13px 25px;text-decoration:none;border-radius:6px">تفعيل الحساب</a></p>
        <p style="font-size:13px;color:#66727d">ينتهي رابط التفعيل خلال 24 ساعة. إذا لم تطلب إنشاء الحساب، تجاهل هذه الرسالة.</p>
      </div>`
  });
}

module.exports = { sendVerificationEmail };
