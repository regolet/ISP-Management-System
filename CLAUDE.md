# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Development Commands

### Server Management
```bash
npm start                    # Start Node.js backend server on port 3000
npm run dev                  # Same as npm start
npm install                  # Install dependencies

# Windows batch scripts for server management
server-manager.bat           # Interactive menu for server management
start.bat                   # Quick server start
restart-server.bat          # Restart server
check-server.bat            # Check server status
```

### Database Operations
```bash
# Database initialization (creates all tables and default admin user)
curl -X POST http://localhost:3000/api/init-database

# Health check
curl http://localhost:3000/api/health

# Database status and management available through Settings UI
```

## Architecture

### Backend Structure
- **Entry point**: `server/app.js` - Express server with modular routing
- **Database**: Dual support for SQLite (local) and Supabase (cloud backup)
  - Primary: SQLite database at `data/offline.db`
  - Backup: Supabase integration for data synchronization
- **Routes**: Modular route files in `server/routes/` for each feature
- **Utils**: Database managers, schedulers, and sync utilities in `server/utils/`
- **Config**: Database pool wrapper in `server/config/database.js`

### API Structure
All API endpoints under `/api/*` with these main modules:
- `/api/clients`, `/api/plans`, `/api/billings`, `/api/payments` - Core business entities
- `/api/monitoring` - Network monitoring with groups and categories
- `/api/mikrotik`, `/api/ppp` - MikroTik RouterOS integration
- `/api/tickets`, `/api/inventory`, `/api/assets` - Support systems
- `/api/supabase`, `/api/supabase-init` - Cloud backup functionality
- `/api/system`, `/api/scheduler` - System management

### Frontend Pages
Static HTML with vanilla JavaScript:
- `dashboard.html` - Main overview with statistics
- `clients.html` - Customer management with MikroTik import
- `plans.html` - Service plans with PPP profile import
- `billings.html`, `payments.html` - Financial management
- `monitoring.html` - Network monitoring dashboard
- `tickets.html`, `inventory.html`, `assets.html` - Support tools
- `settings.html` - System configuration and database management

## Key Implementation Details

### Database Management
- **SQLite Manager**: `server/utils/sqlite-database-manager.js` handles all SQLite operations
- **Supabase Sync**: `server/utils/supabase-sync.js` for cloud backup
- **Migration Support**: Automatic table creation and schema updates
- **JSONB Emulation**: SQLite stores JSON as TEXT, parsed on retrieval

### Authentication
- Default credentials: `admin/admin123`
- JWT tokens for API authentication
- Token required in header: `Authorization: Bearer <token>`

### MikroTik Integration
- RouterOS API connection via `routeros-api` package
- Import PPPoE accounts as clients
- Import PPP profiles as service plans
- Real-time connection monitoring
- Bandwidth tracking and statistics

### Monitoring System
- **Groups**: Collections of PPPoE accounts with online/offline tracking
- **Categories**: Hierarchical organization with subcategories
- **Real-time Updates**: Periodic refresh of connection status
- **Network Totals**: Aggregate bandwidth and connection statistics

### Billing System
- Monthly billing cycles with anchor day support
- Automatic next-month billing generation on payment
- Client-plan relationships for multiple services per client
- Payment tracking with status management

## Critical Files and Locations

### Backend Core Files
- `server/app.js` - Main application entry
- `server/config/database.js` - Database pool wrapper
- `server/utils/sqlite-database-manager.js` - SQLite operations
- `server/utils/database.js` - Table initialization
- `server/utils/scheduler.js` - Cron job management

### Frontend Assets
- `public/assets/css/styles.css` - Tailwind CSS styling
- `public/assets/js/sidebar-nav.js` - Navigation component

### Data Storage
- `data/offline.db` - SQLite database file
- `data/backup/` - Database backup directory
- `server.log` - Application logs

## Development Workflow

### Adding New Features
1. Create route file in `server/routes/`
2. Add route registration in `server/app.js`
3. Create corresponding HTML page in `public/`
4. Update sidebar navigation if needed

### Database Changes
1. Modify schema in `server/utils/database.js`
2. Run database initialization endpoint
3. Test with existing data migration

### Testing API Endpoints
```bash
# Get all clients
curl http://localhost:3000/api/clients

# Create new client (requires auth token)
curl -X POST http://localhost:3000/api/clients \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{"name":"Test Client","email":"test@example.com"}'
```

## System Requirements
- Node.js 18+ with npm
- Windows OS (for .bat scripts) or Unix-based systems
- SQLite3 support (included via npm)
- Network access for MikroTik integration
- Optional: Supabase account for cloud backup