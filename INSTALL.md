# ISP Management System - Installation Guide

## Quick Start (Windows)

### Option 1: Using start.bat (Recommended)
1. **Download/Clone** the project to your computer
2. **Double-click** `start.bat` 
3. The system will automatically:
   - Check for Node.js installation
   - Install dependencies if needed
   - Start the server on port 3000
   - Open your browser to http://localhost:3000

### Option 2: Manual Installation

#### Prerequisites
- **Node.js 18+** (Download from: https://nodejs.org/)
- **Git** (Optional, for updates)

#### Installation Steps
1. **Extract/Clone** the project:
   ```bash
   git clone https://github.com/regolet/ISP-Management-System.git
   cd ISP-Management-System
   ```

2. **Install dependencies**:
   ```bash
   npm install
   ```

3. **Start the server**:
   ```bash
   npm start
   ```

4. **Open browser** to: http://localhost:3000

## Default Login
- **Username**: admin
- **Password**: admin123

## Configuration

### Database Setup
The system uses **Neon PostgreSQL** cloud database. Database connection is pre-configured.

To initialize the database:
1. Login to the system
2. Go to **Settings** → **Database Management**
3. Click **"Initialize Database"**

### MikroTik Router Configuration
1. Go to **Settings** → **MikroTik Settings**
2. Configure your router connection:
   - **Host**: Your MikroTik router IP
   - **Username**: API username
   - **Password**: API password
   - **Port**: 8728 (default)

## Features

### Core Management
- ✅ **Client Management** - Add, edit, delete customers
- ✅ **Plan Management** - Service plans with pricing
- ✅ **Billing System** - Monthly billing generation
- ✅ **Payment Tracking** - Payment records and history
- ✅ **Inventory Management** - Equipment tracking

### MikroTik Integration
- ✅ **PPPoE Account Import** - Import clients from MikroTik
- ✅ **PPP Profile Import** - Import service profiles
- ✅ **Real-time Monitoring** - Bandwidth and client status
- ✅ **Network Statistics** - Data collection and charts

### Advanced Features
- ✅ **Auto-Update System** - Update from GitHub repository
- ✅ **Backup Management** - Automatic backups before updates
- ✅ **Scheduler Configuration** - Configurable data collection intervals
- ✅ **Real-time Dashboard** - Network monitoring with charts

## Auto-Update System

### How to Update
1. Go to **Settings** → **System Updates**
2. Click **"Check for Updates"**
3. If updates available, click **"Update with Backup"**
4. System will automatically:
   - Create backup
   - Pull latest changes from GitHub
   - Install new dependencies
   - Restart server

### Manual Update (Alternative)
```bash
git pull origin main
npm install
npm start
```

## Troubleshooting

### Port 3000 Already in Use
```bash
npx kill-port 3000
npm start
```

### Permission Issues (Windows)
- Run Command Prompt as Administrator
- Or use the provided `start.bat` file

### Database Connection Issues
1. Check internet connection
2. Go to Settings → Database Management
3. Click "Test Connection"
4. If fails, click "Initialize Database"

### MikroTik Connection Issues
1. Verify router IP and credentials
2. Ensure API service is enabled on MikroTik
3. Check firewall settings (port 8728)

## File Structure
```
ISP-Management-System/
├── public/                 # Frontend files
│   ├── dashboard.html     # Main dashboard
│   ├── clients.html       # Client management
│   ├── plans.html         # Plan management
│   ├── settings.html      # System settings
│   └── assets/            # CSS, JS, images
├── server/                # Backend files
│   ├── app.js            # Main server file
│   ├── routes/           # API routes
│   ├── config/           # Configuration
│   └── utils/            # Utilities
├── start.bat             # Windows launcher
├── package.json          # Dependencies
└── INSTALL.md           # This file
```

## Support

### Default Credentials
- **Username**: admin
- **Password**: admin123

### Important URLs
- **Dashboard**: http://localhost:3000/dashboard.html
- **Login**: http://localhost:3000/login.html
- **Settings**: http://localhost:3000/settings.html

### Common Commands
```bash
# Start server
npm start

# Kill process on port 3000
npx kill-port 3000

# Install dependencies
npm install

# Update from GitHub
git pull origin main
```

## Production Deployment

### Environment Variables
Create `.env` file for production:
```env
NODE_ENV=production
PORT=3000
JWT_SECRET=your-secret-key
DATABASE_URL=your-neon-database-url
```

### Recommended Setup
1. Use PM2 for process management
2. Set up SSL/HTTPS with reverse proxy
3. Configure firewall rules
4. Regular backups via the auto-update system

---

For technical support or feature requests, please visit the GitHub repository or contact the system administrator.