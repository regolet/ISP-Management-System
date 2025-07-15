# Netlify Dev Guide for ISP Management System

## 🚀 What is Netlify Dev?

Netlify Dev is a local development environment that simulates Netlify's production environment, including:
- **Redirects and rewrites** from `netlify.toml`
- **Environment variables** from Netlify
- **Functions** (if you add them later)
- **Edge functions** (if you add them later)
- **Form handling** (if you add them later)

## 🎯 Benefits for This Project

1. **Production-like environment** locally
2. **Automatic proxy** to PHP backend
3. **Environment variables** management
4. **Redirect rules** testing
5. **Deployment preview** before going live

## 🛠️ Setup Instructions

### Prerequisites
```bash
# Install Netlify CLI globally
npm install -g netlify-cli

# Login to Netlify (optional but recommended)
netlify login
```

### Quick Start

#### Option 1: Using npm scripts
```bash
# Start both PHP backend and Netlify Dev
npm run netlify:dev
```

#### Option 2: Using provided scripts
```bash
# Windows
start-netlify-dev.bat

# Unix/Linux/Mac
chmod +x start-netlify-dev.sh
./start-netlify-dev.sh
```

#### Option 3: Manual start
```bash
# Terminal 1: Start PHP backend
php -S localhost:8000

# Terminal 2: Start Netlify Dev
netlify dev
```

## 🌐 Access Points

When running with Netlify Dev:

- **Frontend**: `http://localhost:8888` (or the port shown in terminal)
- **API Proxy**: `/api/*` → `http://localhost:8000/api/*`
- **Auth Proxy**: `/auth/*` → `http://localhost:8000/auth/*`

## ⚙️ Configuration

### netlify.toml
```toml
[dev]
  command = "npm run dev"
  port = 3000
  publish = "dist"
  targetPort = 3000

# Proxy API calls to PHP backend
[[dev.proxy]]
  from = "/api/*"
  to = "http://localhost:8000/api/:splat"

[[dev.proxy]]
  from = "/auth/*"
  to = "http://localhost:8000/auth/:splat"
```

### Environment Variables
Create a `.env` file for local development:
```env
REACT_APP_API_URL=http://localhost:8000
NODE_ENV=development
```

## 🔧 Development Workflow

### 1. Start Development
```bash
npm run netlify:dev
```

### 2. Access the Application
- Open browser to the URL shown in terminal (usually `http://localhost:8888`)
- Login with: `admin` / `password`

### 3. Development Features
- **Hot reloading** for React changes
- **API proxy** to PHP backend
- **Redirect testing** from netlify.toml
- **Environment variables** from Netlify

### 4. Testing Production Build
```bash
# Build for production
npm run build

# Preview production build
npm run preview
```

## 🚀 Deployment

### 1. Connect to Netlify
```bash
# Link to Netlify site
netlify link

# Or create a new site
netlify sites:create --name your-isp-management
```

### 2. Deploy
```bash
# Deploy to production
netlify deploy --prod

# Or deploy preview
netlify deploy
```

### 3. Environment Variables
Set in Netlify dashboard:
- `REACT_APP_API_URL` = Your backend URL
- `NODE_ENV` = production

## 🔍 Troubleshooting

### Common Issues

1. **Port conflicts**
   ```bash
   # Use different port
   netlify dev --port 8889
   ```

2. **PHP backend not accessible**
   ```bash
   # Check if PHP server is running
   curl http://localhost:8000
   ```

3. **Proxy not working**
   - Check `netlify.toml` configuration
   - Verify PHP server is on port 8000
   - Check browser console for errors

4. **Environment variables not loading**
   ```bash
   # Check Netlify environment
   netlify env:list
   ```

### Debug Mode
```bash
# Run with debug information
netlify dev --debug
```

## 📁 File Structure for Netlify Dev

```
ISP-Management-System/
├── netlify.toml          # Netlify configuration
├── netlify-dev.json      # Dev-specific config
├── start-netlify-dev.bat # Windows start script
├── start-netlify-dev.sh  # Unix start script
├── src/                  # React frontend
├── public/               # PHP backend
└── package.json          # npm scripts
```

## 🎯 Best Practices

1. **Always use Netlify Dev** for local development
2. **Test redirects** before deploying
3. **Use environment variables** for configuration
4. **Check proxy settings** if API calls fail
5. **Test production build** locally before deploying

## 🔗 Useful Commands

```bash
# Start Netlify Dev
netlify dev

# Build for production
netlify build

# Deploy to production
netlify deploy --prod

# Check status
netlify status

# View logs
netlify logs

# Link to existing site
netlify link

# Create new site
netlify sites:create
```

## 🎉 Success!

Your ISP Management System is now running with Netlify Dev, providing a production-like environment for local development with automatic API proxying to your PHP backend! 