// JWT secret (in production, use environment variable)
const JWT_SECRET = process.env.JWT_SECRET || 'your-secret-key';

const PORT = process.env.PORT || 3000;

module.exports = {
  JWT_SECRET,
  PORT
};