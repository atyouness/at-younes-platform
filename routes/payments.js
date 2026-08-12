const express = require('express');
const router = express.Router();

router.get('/', (req, res) => {
  res.json({ message: '✅ مسار المدفوعات يعمل (مؤقت)' });
});

module.exports = router;
