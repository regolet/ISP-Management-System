# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Development Commands

### Server & Development
```bash
npm start                    # Start Node.js backend on port 3000
npm run dev                  # Start Node.js backend (same as start)
npm install                  # Install dependencies
```

### Database Management
```bash
# Health check and database connectivity
curl http://localhost:3000/api/health

# Initialize database tables and sample data
curl -X POST http://localhost:3000/api/init-database
```

### Netlify Development
```bash
# Start with Netlify Dev for production-like environment
netlify dev                  # Runs on port 8888 with proxy to backend

# Alternative using npm scripts
npm run netlify:dev         # If configured in package.json
```

## Architecture Overview

### Backend (Node.js/Express)
- **Main server**: `server/index.js` - Express server with PostgreSQL integration
- **Database**: Neon PostgreSQL cloud database with parameterized queries
- **Authentication**: JWT tokens with bcrypt password hashing
- **API Base**: `/api/*` endpoints requiring authentication (except `/api/health` and `/api/init-database`)

### Frontend (Static HTML/CSS/JS)
- **Entry point**: `public/index.html` - redirects to login
- **Pages**: Login, Dashboard, Clients, Plans, Billings, Payments, Settings
- **Assets**: `public/assets/css/styles.css` for styling
- **Forms**: Modular form components in `public/forms/` directory

### Database Schema
Key tables managed by the backend:
- `users` - Authentication with admin/admin123 default
- `clients` - Customer management
- `plans` - Service plans with pricing
- `client_plans` - Plan assignments with anchor_day for billing cycles
- `billings` - Monthly billing records
- `payments` - Payment tracking with automatic next-month billing generation
- `mikrotik_settings` - RouterOS API configuration
- `company_info` - Business information

## Authentication Flow
1. Login via `/api/auth/login` with username/password
2. Returns JWT token for subsequent API calls
3. Token required in Authorization header: `Bearer <token>`
4. Protected routes use `authenticateToken` middleware

## Key API Patterns
- **CRUD operations**: GET, POST, PUT, DELETE for main entities
- **Relationships**: Clients can have multiple plans, billings linked to client+plan
- **Payment processing**: Creates next month's billing automatically
- **MikroTik integration**: Fetch PPPoE accounts and profiles via RouterOS API

## Development Setup Requirements
1. Node.js 18+ with npm
2. Neon PostgreSQL connection (configured in server/index.js:21)
3. Default login: admin/admin123
4. Server runs on port 3000, Netlify Dev on 8888

## Important Implementation Notes
- Database connection uses SSL with `rejectUnauthorized: false`
- JWT secret should be environment variable in production
- API responses follow `{success: true, data}` pattern
- Error handling includes detailed logging
- CORS enabled for frontend communication
- Static files served from `public/` directory
- 404 redirects to login page for non-API routes