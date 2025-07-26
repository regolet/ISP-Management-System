# ISP Management System - Setup Guide

## Overview
This ISP Management System now uses a Node.js backend with PostgreSQL database (Neon) instead of PHP.

## Prerequisites
- Node.js 18+ 
- npm or yarn
- Neon PostgreSQL database (already configured)

## Quick Start

### 1. Install Dependencies
```bash
npm install
```

### 2. Start the Development Environment
```bash
npm run dev:full
```

This will start:
- Node.js backend server on port 8000
- Netlify Dev server on port 8888
- Vite frontend on port 3003

### 3. Initialize Database
The database will be automatically initialized when you first access the application, or you can manually initialize it:

```bash
# Test database connection
curl http://localhost:8000/api/health

# Initialize database with tables and sample data
curl -X POST http://localhost:8000/api/init-database
```

### 4. Access the Application
- Frontend: http://localhost:8888
- Backend API: http://localhost:8000/api
- Health Check: http://localhost:8000/api/health

## Default Login Credentials
- Username: `admin`
- Password: `admin123`

## API Endpoints

### Authentication
- `POST /api/auth/login` - Login
- `GET /api/auth/me` - Get current user (requires auth)

### Data Endpoints (all require authentication)
- `GET /api/clients` - Get all clients
- `GET /api/plans` - Get all plans
- `GET /api/subscriptions` - Get all subscriptions
- `GET /api/invoices` - Get all invoices

### System
- `GET /api/health` - Health check
- `POST /api/init-database` - Initialize database

## Troubleshooting

### Database Connection Issues
1. Check if the Neon PostgreSQL connection string is correct
2. Verify the database is accessible from your network
3. Check the server logs for connection errors

### API 500 Errors
1. Ensure the Node.js server is running on port 8000
2. Check that the database is properly initialized
3. Verify JWT token is being sent in Authorization header

### Frontend Issues
1. Make sure Netlify Dev is running
2. Check browser console for CORS errors
3. Verify API endpoints are being proxied correctly

## Development

### Backend Development
- **Main server**: `server/app.js` - Modular architecture with separate route files
- **Legacy server**: `server/index.js` - Original monolithic server (preserved as backup)
- Database queries use parameterized statements for security
- JWT tokens for authentication
- CORS enabled for frontend communication
- Modular structure: routes/, middleware/, config/, utils/ folders

### Frontend Development
- React with TypeScript
- Vite for build tooling
- Axios for API communication
- React Router for navigation

## Production Deployment
1. Set environment variables for production
2. Use proper JWT secret
3. Configure CORS for production domain
4. Set up proper database connection pooling 