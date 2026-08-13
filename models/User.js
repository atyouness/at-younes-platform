const { pool } = require('../config/database');
const bcrypt = require('bcrypt');

class User {
  // إنشاء مستخدم جديد
  static async create({ username, email, password, referralCode }) {
    try {
      const hashedPassword = await bcrypt.hash(password, 10);
      const query = `
        INSERT INTO users (username, email, password, referral_code, referred_by)
        VALUES ($1, $2, $3, $4, $5)
        RETURNING id, username, email, referral_code, created_at
      `;
      const values = [username, email, hashedPassword, referralCode, null];
      const result = await pool.query(query, values);
      return result.rows[0];
    } catch (error) {
      console.error('❌ خطأ في إنشاء المستخدم:', error.message);
      throw error;
    }
  }

  // البحث عن مستخدم بالبريد الإلكتروني
  static async findByEmail(email) {
    try {
      const query = 'SELECT * FROM users WHERE email = $1';
      const result = await pool.query(query, [email]);
      return result.rows[0];
    } catch (error) {
      console.error('❌ خطأ في البحث عن المستخدم:', error.message);
      return null;
    }
  }

  // البحث عن مستخدم بالمعرف
  static async findById(id) {
    try {
      const query = 'SELECT id, username, email, referral_code, created_at, balance, role FROM users WHERE id = $1';
      const result = await pool.query(query, [id]);
      return result.rows[0];
    } catch (error) {
      console.error('❌ خطأ في البحث عن المستخدم بالمعرف:', error.message);
      return null;
    }
  }

  // التحقق من كلمة المرور
  static async comparePassword(plainPassword, hashedPassword) {
    return await bcrypt.compare(plainPassword, hashedPassword);
  }

  // التحقق من وجود الجدول وإنشائه إذا لم يكن موجوداً (مع تجاهل الأخطاء)
  static async ensureTable() {
    try {
      // التحقق من وجود الجدول
      const checkQuery = `
        SELECT EXISTS (
          SELECT FROM information_schema.tables 
          WHERE table_name = 'users'
        );
      `;
      const result = await pool.query(checkQuery);
      const tableExists = result.rows[0].exists;

      if (!tableExists) {
        console.log('⚠️ جدول users غير موجود، سيتم إنشاؤه...');
        await this.createTable();
        console.log('✅ تم إنشاء جدول users بنجاح');
      } else {
        console.log('✅ جدول users موجود بالفعل');
      }
      return true;
    } catch (error) {
      console.error('❌ فشل في التحقق من الجدول:', error.message);
      console.log('⚠️ سيتم الاستمرار دون إنشاء الجدول (سيتم التعامل معه لاحقًا)');
      return false;
    }
  }

  // إنشاء جدول المستخدمين (إذا لم يكن موجوداً)
  static async createTable() {
    try {
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
    } catch (error) {
      console.error('❌ فشل في إنشاء جدول المستخدمين:', error.message);
      throw error;
    }
  }
}

module.exports = User;
