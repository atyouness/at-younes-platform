const express = require('express');
const router = express.Router();

router.get('/', (req, res) => {
  res.json({ message: '✅ مسار المستخدمين يعمل (مؤقت)' });
});

module.exports = router;
