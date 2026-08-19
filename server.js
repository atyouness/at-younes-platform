require('dotenv').config();
const express = require('express');
const cors = require('cors');
const helmet = require('helmet');
const rateLimit = require('express-rate-limit');
const path = require('path');
const fs = require('fs');
const { connectDB } = require('./config/database');
const authRoutes = require('./routes/auth');
const pageRoutes = require('./routes/pages');

const app = express();
const PORT = Number(process.env.PORT || 3000);
const publicPath = path.join(__dirname, 'public');

app.use(helmet());
app.use(cors({ origin: process.env.CORS_ORIGIN || true }));
app.use(express.json({ limit: '1mb' }));
app.use(express.urlencoded({ extended: true, limit: '1mb' }));
app.set('view engine', 'ejs');
app.set('views', path.join(__dirname, 'views'));

const limiter = rateLimit({
  windowMs: 15 * 60 * 1000,
  max: 100,
  standardHeaders: true,
  legacyHeaders: false
});
app.use('/api', limiter);
app.use(express.static(publicPath));
app.use('/api/auth', authRoutes);
app.use('/', pageRoutes);

app.get('/', (req, res) => {
  const indexPath = path.join(publicPath, 'index.html');
  if (fs.existsSync(indexPath)) return res.sendFile(indexPath);
  return res.status(404).send('الصفحة الرئيسية غير موجودة');
});

app.get('/api/status', (req, res) => {
  res.json({ status: 'ok', time: new Date().toISOString(), publicExists: fs.existsSync(publicPath) });
});

app.use((err, req, res, next) => {
  console.error('Request error:', err);
  res.status(500).json({ message: 'حدث خطأ في الخادم' });
});

if (require.main === module) {
  app.listen(PORT, '0.0.0.0', async () => {
    console.log(`Server listening on port ${PORT}`);
    await connectDB();
  });
}

module.exports = app;
