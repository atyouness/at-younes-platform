const express = require('express');
const router = express.Router();

// مسار تسجيل الدخول المؤقت
router.post('/login', (req, res) => {
  res.json({ message: '✅ تم تسجيل الدخول بنجاح (مؤقت)' });
});

// مسار التسجيل المؤقت
router.post('/register', (req, res) => {
  res.json({ message: '✅ تم التسجيل بنجاح (مؤقت)' });
});

module.exports = router;
