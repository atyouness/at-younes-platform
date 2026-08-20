const { pool } = require('../config/database');
const bcrypt = require('bcrypt');

class User {
  static publicFields(user) {
    const { password_hash, ...safeUser } = user;
    return safeUser;
  }

  static async create({ firstName, lastName, username, email, phone, whatsapp, password, referralCode, parentUserId }) {
    const passwordHash = await bcrypt.hash(password, 12);
    const [result] = await pool.query(
      `INSERT INTO users
        (first_name, last_name, username, email, phone, whatsapp, password_hash, role_id,
         referral_code, parent_user_id, status, is_active)
       VALUES (?, ?, ?, ?, ?, ?, ?, 2, ?, ?, 'pending', 0)`,
      [firstName, lastName, username, email, phone, whatsapp, passwordHash, referralCode, parentUserId]
    );
    return User.findById(result.insertId);
  }

  static async findByEmail(email) {
    const [rows] = await pool.query('SELECT * FROM users WHERE email = ? LIMIT 1', [email]);
    return rows[0] || null;
  }

  static async findById(id) {
    const [rows] = await pool.query(
      `SELECT id, first_name, last_name, username, email, phone, whatsapp, role_id,
              referral_code, parent_user_id, status, is_active, balance,
              total_earnings, total_referrals, created_at, updated_at
       FROM users WHERE id = ? LIMIT 1`,
      [id]
    );
    return rows[0] || null;
  }

  static async findByUsername(username) {
    const [rows] = await pool.query('SELECT id, username FROM users WHERE username = ? LIMIT 1', [username]);
    return rows[0] || null;
  }

  static async findByReferralCode(referralCode) {
    const [rows] = await pool.query('SELECT id, referral_code FROM users WHERE referral_code = ? LIMIT 1', [referralCode]);
    return rows[0] || null;
  }

  static async saveVerificationToken(userId, tokenHash, expiresAt) {
    await pool.query(
      `UPDATE users
       SET verification_token_hash = ?, verification_expires_at = ?
       WHERE id = ?`,
      [tokenHash, expiresAt, userId]
    );
  }

  static async clearVerificationToken(userId) {
    await pool.query(
      `UPDATE users
       SET verification_token_hash = NULL, verification_expires_at = NULL
       WHERE id = ?`,
      [userId]
    );
  }

  static async verifyEmailToken(tokenHash) {
    const [result] = await pool.query(
      `UPDATE users
       SET status = 'active', is_active = 1, email_verified_at = CURRENT_TIMESTAMP,
           verification_token_hash = NULL, verification_expires_at = NULL
       WHERE verification_token_hash = ?
         AND verification_expires_at > CURRENT_TIMESTAMP
         AND status = 'pending'`,
      [tokenHash]
    );
    return result.affectedRows === 1;
  }

  static async comparePassword(password, passwordHash) {
    return bcrypt.compare(password, passwordHash);
  }
}

module.exports = User;
