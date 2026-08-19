const express = require('express');
const router = express.Router();

router.get('/login', (req, res) => res.render('login', { title: 'تسجيل الدخول' }));
router.get('/register', (req, res) => res.render('register', { title: 'إنشاء حساب' }));

module.exports = router;
