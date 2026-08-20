const express = require('express');
const { register, login, verify, resendActivation } = require('../controllers/authController');
const router = express.Router();

router.post('/login', login);
router.post('/register', register);
router.get('/verify', verify);
router.post('/resend-activation', resendActivation);

module.exports = router;
