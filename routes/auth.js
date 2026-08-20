const express = require('express');
const { register, login, verify, resendActivation } = require('../controllers/authController');
const authMiddleware = require('../middleware/auth');
const User = require('../models/User');
const router = express.Router();

router.post('/login', login);
router.post('/register', register);
router.get('/verify', verify);
router.post('/resend-activation', resendActivation);
router.get('/referrals/tree', authMiddleware, async (req, res, next) => {
	try {
		return res.json(await User.getReferralTree(req.user.id));
	} catch (error) {
		return next(error);
	}
});

module.exports = router;
