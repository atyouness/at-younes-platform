const express = require('express');
const { pool } = require('../config/database');
const authMiddleware = require('../middleware/auth');
const { requireRoles } = require('../middleware/roles');

const router = express.Router();

router.get('/overview', authMiddleware, requireRoles(1, 3), async (req, res, next) => {
  try {
    const [[pendingDeposits], [pendingWithdrawals], [activeProjects]] = await Promise.all([
      pool.query("SELECT COUNT(*) AS count FROM deposits WHERE status = 'pending'"),
      pool.query("SELECT COUNT(*) AS count FROM withdrawals WHERE status = 'pending'"),
      pool.query("SELECT COUNT(*) AS count FROM offers WHERE is_active = 1")
    ]);
    return res.json({
      pendingDeposits: Number(pendingDeposits.count || 0),
      pendingWithdrawals: Number(pendingWithdrawals.count || 0),
      activeProjects: Number(activeProjects.count || 0)
    });
  } catch (error) {
    return next(error);
  }
});

module.exports = router;
