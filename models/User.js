const { pool } = require('../config/database');
const bcrypt = require('bcrypt');

class User {
  // إنشاء مستخدم جديد
  static async create({ username, email, password, referralCode, referredBy }) {
    try {
      const hashedPassword = await bcrypt.hash(password, 10);
      const query = `
        INSERT INTO users (username, email, password, referral_code, referred_by)
        VALUES (?, ?, ?, ?, ?)
      `;
      const values = [username, email, hashedPassword, referralCode, referredBy];
      const [result] = await pool.query(query, values);
      return {
        id: result.insertId,
        username,
        email,
        referral_code: referralCode,
        created_at: new Date().toISOString()
      };
    } catch (error) {
      console.error('❌ خطأ في إنشاء المستخدم:', error.message);
      throw error;
    }
  }

  // البحث عن مستخدم بالبريد الإلكتروني
  static async findByEmail(email) {
    try {
      const query = 'SELECT * FROM users WHERE email = ?';
      const [rows] = await pool.query(query, [email]);
      return rows[0];
    } catch (error) {
      console.error('❌ خطأ في البحث عن المستخدم:', error.message);
      return null;
    }
  }

  // البحث عن مستخدم بالمعرف
  static async findById(id) {
    try {
      const query = 'SELECT id, username, email, referral_code, balance, role FROM users WHERE id = ?';
      const [rows] = await pool.query(query, [id]);
      return rows[0];
    } catch (error) {
      console.error('❌ خطأ في البحث عن المستخدم بالمعرف:', error.message);
      return null;
    }
  }

// البحث عن مستخدم بواسطة كود الإحالة
static async findByReferralCode(referralCode) {
  try {
    const query = 'SELECT id, username, email FROM users WHERE referral_code = ?';
    const [rows] = await pool.query(query, [referralCode]);
    return rows[0];
  } catch (error) {
    console.error('❌ خطأ في البحث بكود الإحالة:', error.message);
    return null;
  }
}

// الحصول على عدد الإحالات لمستخدم معين
static async getReferralCount(userId) {
  try {
    const query = 'SELECT COUNT(*) as count FROM users WHERE referred_by = ?';
    const [rows] = await pool.query(query, [userId]);
    return rows[0].count;
  } catch (error) {
    console.error('❌ خطأ في جلب عدد الإحالات:', error.message);
    return 0;
  }
}

// الحصول على قائمة الإحالات لمستخدم معين
static async getReferrals(userId) {
  try {
    const query = 'SELECT id, username, email, created_at FROM users WHERE referred_by = ? ORDER BY created_at DESC';
    const [rows] = await pool.query(query, [userId]);
    return rows;
  } catch (error) {
    console.error('❌ خطأ في جلب قائمة الإحالات:', error.message);
    return [];
  }
}

  // التحقق من كلمة المرور
  static async comparePassword(plainPassword, hashedPassword) {
    return await bcrypt.compare(plainPassword, hashedPassword);
  }

  // التحقق من وجود الجدول وإنشائه
  static async ensureTable() {
    try {
      // التحقق من وجود الجدول
      const checkQuery = `
        SELECT COUNT(*) as count 
        FROM information_schema.tables 
        WHERE table_schema = DATABASE() 
        AND table_name = 'users'
      `;
      const [rows] = await pool.query(checkQuery);
      const tableExists = rows[0].count > 0;

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
      return false;
    }
  }

  // إنشاء جدول المستخدمين
  static async createTable() {
    try {
      const query = `
        CREATE TABLE IF NOT EXISTS users (
          id INT AUTO_INCREMENT PRIMARY KEY,
          username VARCHAR(50) UNIQUE NOT NULL,
          email VARCHAR(100) UNIQUE NOT NULL,
          password VARCHAR(255) NOT NULL,
          referral_code VARCHAR(10) UNIQUE NOT NULL,
          referred_by INT DEFAULT NULL,
          balance DECIMAL(10,2) DEFAULT 0,
          role VARCHAR(20) DEFAULT 'user',
          is_active BOOLEAN DEFAULT TRUE,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
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
