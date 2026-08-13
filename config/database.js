const { Pool } = require('pg');
require('dotenv').config();

const pool = new Pool({
  host: process.env.DB_HOST || '46.202.156.218',
  port: process.env.DB_PORT || 65002,
  user: process.env.DB_USER || 'u188242994',
  password: process.env.DB_PASSWORD || 'UJN741ik85/*',
  database: process.env.DB_NAME || 'at_younes_db',
  ssl: false  // ✅ هذا يحل مشكلة SSL
  connectionTimeoutMillis: 5000,   // ينتظر 5 ثوانٍ فقط لتأسيس الاتصال
  idleTimeoutMillis: 30000,        // يغلق الاتصال الخامل بعد 30 ثانية
  keepAlive: true,                 // يبقي الاتصال نشطاً
  keepAliveInitialDelayMillis: 10000 // يبدأ إشارات البقاء بعد 10 ثوانٍ
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
