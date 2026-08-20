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
            `SELECT id, first_name, last_name, username, email, phone, whatsapp,
              show_phone_to_level_2, show_phone_to_level_3, role_id,
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

  static async getReferralTree(userId) {
    const fields = `
      SELECT id, first_name, last_name,
             CONCAT(first_name, ' ', last_name) AS full_name,
             username, email, phone, whatsapp, show_phone_to_level_2, show_phone_to_level_3, referral_code,
             parent_user_id, status, is_active, created_at
      FROM users
      WHERE parent_user_id = ?
      ORDER BY created_at DESC`;

    const [level1] = await pool.query(fields, [userId]);
    const level2 = [];
    const level3 = [];

    for (const member of level1) {
      const [children] = await pool.query(fields, [member.id]);
      level2.push(...children);
      for (const child of children) {
        const [grandchildren] = await pool.query(fields, [child.id]);
        level3.push(...grandchildren);
      }
    }

    const [sponsorRows] = await pool.query(
      `SELECT id, first_name, last_name,
              CONCAT(first_name, ' ', last_name) AS full_name,
              username, email, phone, whatsapp, referral_code, parent_user_id
       FROM users WHERE id = (SELECT parent_user_id FROM users WHERE id = ?)`,
      [userId]
    );

    level2.forEach((member) => {
      if (!member.show_phone_to_level_2) member.phone = null;
    });
    level3.forEach((member) => {
      if (!member.show_phone_to_level_3) member.phone = null;
    });

    const allMembers = [...level1, ...level2, ...level3];
    for (const member of allMembers) {
      const [directRows] = await pool.query('SELECT COUNT(*) AS count FROM users WHERE parent_user_id = ?', [member.id]);
      const [teamRows] = await pool.query(
        `WITH RECURSIVE team AS (
           SELECT id FROM users WHERE parent_user_id = ?
           UNION ALL
           SELECT child.id FROM users child INNER JOIN team ON child.parent_user_id = team.id
         ) SELECT COUNT(*) AS count FROM team`,
        [member.id]
      );
      member.referralCount = Number(directRows[0].count || 0);
      member.teamSize = Number(teamRows[0].count || 0);
    }

    return {
      sponsor: sponsorRows[0] || null,
      level1,
      level2,
      level3,
      totalReferrals: level1.length,
      totalTeam: level1.length + level2.length + level3.length,
      userRank: level1.length >= 20 ? '🎖️ قائد' : level1.length >= 10 ? '🏅 نقيب' : level1.length >= 5 ? '⭐ عضو مميز' : '👤 عضو'
    };
  }

  static async getUpline(userId, maxLevels = 3) {
    const uplines = [];
    let currentId = userId;
    for (let level = 1; level <= maxLevels; level += 1) {
      const [rows] = await pool.query(
        `SELECT id, first_name, last_name, email, parent_user_id
         FROM users WHERE id = (SELECT parent_user_id FROM users WHERE id = ?)`,
        [currentId]
      );
      if (!rows[0]) break;
      uplines.push({ ...rows[0], level });
      currentId = rows[0].id;
    }
    return uplines;
  }

  static async saveVerificationToken(userId, tokenHash, expiresAt) {
    await pool.query(
      `UPDATE users
       SET verification_token_hash = ?, verification_expires_at = ?
       WHERE id = ?`,
      [tokenHash, expiresAt, userId]
    );
  }

  static async updatePhoneVisibility(userId, { level2, level3 }) {
    await pool.query(
      `UPDATE users
       SET show_phone_to_level_2 = ?, show_phone_to_level_3 = ?
       WHERE id = ?`,
      [level2 ? 1 : 0, level3 ? 1 : 0, userId]
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
