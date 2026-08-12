const express = require('express');
const router = express.Router();

router.get('/', (req, res) => {
  res.json({ message: '✅ مسار الإحالات يعمل (مؤقت)' });
});

module.exports = router;
