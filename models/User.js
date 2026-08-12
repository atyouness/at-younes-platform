const { pool } = require('../config/database');
const bcrypt = require('bcrypt');

class User {
  // إنشاء مستخدم جديد
  static async create({ username, email, password, referralCode }) {
    const hashedPassword = await bcrypt.hash(password, 10);
    const query = `
      INSERT INTO users (username, email, password, referral_code, referred_by)
      VALUES ($1, $2, $3, $4, $5)
      RETURNING id, username, email, referral_code, created_at
    `;
    const values = [username, email, hashedPassword, referralCode, null];
    const result = await pool.query(query, values);
    return result.rows[0];
  }

  // البحث عن مستخدم بالبريد الإلكتروني
  static async findByEmail(email) {
    const query = 'SELECT * FROM users WHERE email = $1';
    const result = await pool.query(query, [email]);
    return result.rows[0];
  }

  // البحث عن مستخدم بالمعرف
  static async findById(id) {
    const query = 'SELECT id, username, email, referral_code, created_at FROM users WHERE id = $1';
    const result = await pool.query(query, [id]);
    return result.rows[0];
  }

  // التحقق من كلمة المرور
  static async comparePassword(plainPassword, hashedPassword) {
    return await bcrypt.compare(plainPassword, hashedPassword);
  }

  // إنشاء جدول المستخدمين (إذا لم يكن موجوداً)
  static async createTable() {
    const query = `
      CREATE TABLE IF NOT EXISTS users (
        id SERIAL PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        referral_code VARCHAR(10) UNIQUE NOT NULL,
        referred_by INTEGER REFERENCES users(id),
        balance DECIMAL(10,2) DEFAULT 0,
        role VARCHAR(20) DEFAULT 'user',
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `;
    await pool.query(query);
    console.log('✅ جدول المستخدمين جاهز');
  }
}

module.exports = User;
