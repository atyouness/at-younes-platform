const express = require('express');
const router = express.Router();

router.post('/login', (req, res) => {
  res.json({ message: '✅ تم تسجيل الدخول بنجاح (مؤقت)' });
});

router.post('/register', (req, res) => {
  res.json({ message: '✅ تم التسجيل بنجاح (مؤقت)' });
});

module.exports = router;
