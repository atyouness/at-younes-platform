const { Pool } = require('pg');
require('dotenv').config();

const pool = new Pool({
  host: process.env.DB_HOST || 'localhost',
  port: process.env.DB_PORT || 5432,
  user: process.env.DB_USER || 'postgres',
  password: process.env.DB_PASSWORD || 'password',
  database: process.env.DB_NAME || 'at_younes_db',
  ssl: process.env.NODE_ENV === 'production' ? { rejectUnauthorized: false } : false
});

// دالة لاختبار الاتصال
const connectDB = async () => {
  try {
    await pool.connect();
    console.log('✅ تم الاتصال بقاعدة البيانات بنجاح!');
  } catch (error) {
    console.error('❌ فشل الاتصال بقاعدة البيانات:', error.message);
    // لا نخرج من العملية، فقط نسجل الخطأ
    console.log('⚠️ استمرار التشغيل دون قاعدة بيانات (للاختبار)');
  }
};

module.exports = { pool, connectDB };
