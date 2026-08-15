const { pool } = require('../config/database');
const bcrypt = require('bcrypt');

class User {
  // إنشاء مستخدم جديد
  static async create({ username, email, password, referralCode, referredBy, full_name, phone, whatsapp }) {
    try {
      const hashedPassword = await bcrypt.hash(password, 10);
      const query = `
        INSERT INTO users (username, email, password, referral_code, referred_by, full_name, phone, whatsapp)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
      `;
      const values = [username, email, hashedPassword, referralCode, referredBy, full_name, phone, whatsapp];
      const [result] = await pool.query(query, values);
      return {
        id: result.insertId,
        username,
        email,
        referral_code: referralCode,
        full_name,
        phone,
        whatsapp,
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
      const query = 'SELECT id, username, email, referral_code, referred_by, full_name, phone, whatsapp, balance, role FROM users WHERE id = ?';
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
      const query = 'SELECT id, username, email, full_name, phone FROM users WHERE referral_code = ?';
      const [rows] = await pool.query(query, [referralCode]);
      return rows[0];
    } catch (error) {
      console.error('❌ خطأ في البحث بكود الإحالة:', error.message);
      return null;
    }
  }

  // البحث عن مستخدم بواسطة اسم المستخدم
  static async findByUsername(username) {
    try {
      const query = 'SELECT id, username, email FROM users WHERE username = ?';
      const [rows] = await pool.query(query, [username]);
      return rows[0];
    } catch (error) {
      console.error('❌ خطأ في البحث باسم المستخدم:', error.message);
      return null;
    }
  }

  // الحصول على عدد الإحالات المباشرة
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

  // الحصول على حجم الفريق بالكامل
  static async getTeamSize(userId) {
    try {
      const query = `
        WITH RECURSIVE team AS (
          SELECT id FROM users WHERE referred_by = ?
          UNION ALL
          SELECT u.id FROM users u
          INNER JOIN team t ON u.referred_by = t.id
        )
        SELECT COUNT(*) as count FROM team
      `;
      const [rows] = await pool.query(query, [userId]);
      return rows[0].count || 0;
    } catch (error) {
      console.error('❌ خطأ في حساب حجم الفريق:', error.message);
      return 0;
    }
  }

  // الحصول على قائمة الإحالات المباشرة
  static async getReferrals(userId) {
    try {
      const query = 'SELECT id, username, email, full_name, phone, created_at FROM users WHERE referred_by = ? ORDER BY created_at DESC';
      const [rows] = await pool.query(query, [userId]);
      return rows;
    } catch (error) {
      console.error('❌ خطأ في جلب قائمة الإحالات:', error.message);
      return [];
    }
  }

  // الحصول على شبكة الإحالات (المستويات 1، 2، 3)
  static async getReferralTree(userId) {
    try {
      const level1Query = `
        SELECT id, username, email, phone, full_name,
               (SELECT COUNT(*) FROM users WHERE referred_by = u.id) as referralCount
        FROM users u
        WHERE referred_by = ?
        ORDER BY created_at DESC
      `;
      const [level1Rows] = await pool.query(level1Query, [userId]);

      const level1 = [];
      const level2 = [];
      const level3 = [];

      for (const user of level1Rows) {
        user.teamSize = await this.getTeamSize(user.id);
        level1.push(user);

        const level2Query = `
          SELECT id, username, email, phone, full_name,
                 (SELECT COUNT(*) FROM users WHERE referred_by = u.id) as referralCount
          FROM users u
          WHERE referred_by = ?
          ORDER BY created_at DESC
        `;
        const [level2Rows] = await pool.query(level2Query, [user.id]);

        for (const u2 of level2Rows) {
          u2.teamSize = await this.getTeamSize(u2.id);
          level2.push(u2);

          const level3Query = `
            SELECT id, username, email, phone, full_name,
                   (SELECT COUNT(*) FROM users WHERE referred_by = u.id) as referralCount
            FROM users u
            WHERE referred_by = ?
            ORDER BY created_at DESC
          `;
          const [level3Rows] = await pool.query(level3Query, [u2.id]);

          for (const u3 of level3Rows) {
            u3.teamSize = await this.getTeamSize(u3.id);
            level3.push(u3);
          }
        }
      }

      return { level1, level2, level3 };
    } catch (error) {
      console.error('❌ خطأ في جلب شبكة الإحالات:', error);
      return { level1: [], level2: [], level3: [] };
    }
  }

  // التحقق من كلمة المرور
  static async comparePassword(plainPassword, hashedPassword) {
    return await bcrypt.compare(plainPassword, hashedPassword);
  }

  // تفعيل حساب المستخدم
  static async activate(userId) {
    try {
      const query = 'UPDATE users SET is_active = TRUE WHERE id = ?';
      await pool.query(query, [userId]);
      console.log(`✅ تم تفعيل المستخدم ID: ${userId}`);
      return true;
    } catch (error) {
      console.error('❌ فشل في تفعيل المستخدم:', error);
      return false;
    }
  }

  // التحقق من وجود الجدول وإنشائه
  static async ensureTable() {
    try {
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
          full_name VARCHAR(100) DEFAULT NULL,
          phone VARCHAR(20) DEFAULT NULL,
          whatsapp VARCHAR(20) DEFAULT NULL,
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
