const requireRoles = (...allowedRoleIds) => (req, res, next) => {
  if (!req.user || !allowedRoleIds.includes(Number(req.user.role_id))) {
    return res.status(403).json({ message: 'ليس لديك صلاحية لهذا الإجراء' });
  }
  return next();
};

module.exports = { requireRoles };
