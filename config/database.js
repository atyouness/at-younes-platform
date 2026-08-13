const mysql = require('mysql2/promise');
require('dotenv').config();

const pool = mysql.createPool({
  host: process.env.DB_HOST || 'localhost',
  port: process.env.DB_PORT || 3306,
  user: process.env.DB_USER || 'u188242994_atyouness',
  password: process.env.DB_PASSWORD || 'UJN741ik85/*',
  database: process.env.DB_NAME || 'u188242994_slimane',
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0
});

const connectDB = async () => {
  try {
    const connection = await pool.getConnection();
    console.log('✅ تم الاتصال بقاعدة البيانات بنجاح!');
    connection.release();
  } catch (error) {
    console.error('❌ فشل الاتصال بقاعدة البيانات:', error.message);
  }
};

module.exports = { pool, connectDB };
