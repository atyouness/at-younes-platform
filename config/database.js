const { Pool } = require('pg');
require('dotenv').config();

const pool = new Pool({
  host: process.env.DB_HOST || '46.202.156.218',
  port: process.env.DB_PORT || 65002,
  user: process.env.DB_USER || 'u188242994',
  password: process.env.DB_PASSWORD || 'UJN741ik85/*',
  database: process.env.DB_NAME || 'at_younes_db',
  ssl: process.env.NODE_ENV === 'production' ? { rejectUnauthorized: false } : false
});

const connectDB = async () => {
  try {
    await pool.connect();
    console.log('✅ تم الاتصال بقاعدة البيانات بنجاح!');
  } catch (error) {
    console.error('❌ فشل الاتصال بقاعدة البيانات:', error.message);
  }
};

module.exports = { pool, connectDB };
ملاحظة: هذا الكود يستخدم المتغيرات البيئية (process.env) لتجنب الأخطاء، ويعمل مع ssl: false تلقائياً إذا كانت NODE_ENV ليست production.

🛠️ الآن، تأكد من server.js
1️⃣ في مستودع GitHub، افتح server.js.
2️⃣ تأكد من أن السطرين التاليين معلقان (مع // في البداية):
javascript
// connectDB();
// User.createTable();
إذا لم يكونا معلقين، قم بتعليقهما الآن.

🚀 أعد النشر (Redeploy) على Hostinger
اذهب إلى Websites → atyouness.com → Deployments.

اضغط Redeploy.

انتظر حتى يكتمل النشر (1-2 دقيقة).

📌 إذا ظهرت رسالة 503 بعد إعادة النشر
إذا استمرت المشكلة، تحقق من stderr.log مرة أخرى. إذا كان الخطأ لا يزال متعلقاً بـ database.js، فاستخدم هذا الإصدار المبسط (بدون ssl وبدون dotenv):

javascript
const { Pool } = require('pg');

const pool = new Pool({
  host: 'localhost',
  port: 5432,
  user: 'your_username',
  password: 'your_password',
  database: 'at_younes_db'
});

const connectDB = async () => {
  try {
    await pool.connect();
    console.log('✅ تم الاتصال بقاعدة البيانات بنجاح!');
  } catch (error) {
    console.error('❌ فشل الاتصال بقاعدة البيانات:', error.message);
  }
};

module.exports = { pool, connectDB };
