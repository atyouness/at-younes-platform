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
router.get('/profile-visibility', authMiddleware, (req, res) => {
	res.json({
		show_phone_to_level_2: Boolean(req.user.show_phone_to_level_2),
		show_phone_to_level_3: Boolean(req.user.show_phone_to_level_3)
	});
});
router.put('/profile-visibility', authMiddleware, async (req, res, next) => {
	try {
		await User.updatePhoneVisibility(req.user.id, {
			level2: req.body.show_phone_to_level_2,
			level3: req.body.show_phone_to_level_3
		});
		return res.json({ message: 'تم حفظ إعدادات الخصوصية' });
	} catch (error) {
		return next(error);
	}
});

module.exports = router;
