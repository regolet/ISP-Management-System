# ISP Management System - React + Neon PostgreSQL

A modern React-based ISP Management System with Neon PostgreSQL database.

## 🚀 Features

- **React 18** with TypeScript
- **Neon PostgreSQL** database
- **Modern UI** with responsive design
- **Netlify Dev** for local development
- **Authentication** with JWT tokens
- **Real-time** notifications

## 🏗️ Architecture

### Frontend
- **React 18** with TypeScript
- **Vite** for fast development
- **React Router** for navigation
- **Context API** for state management
- **Axios** for API calls
- **Modern CSS** with responsive design

### Backend (Future)
- **PostgreSQL** via Neon
- **RESTful API** endpoints
- **JWT Authentication**
- **Real-time** features

## 🗄️ Database

### Neon PostgreSQL
The application is configured to use Neon PostgreSQL:
```
postgresql://neondb_owner:npg_4ZPlK1gJEbeo@ep-dark-brook-ae1ictl5-pooler.c-2.us-east-2.aws.neon.tech/neondb?sslmode=require&channel_binding=require
```

## 🚀 Quick Start

### Prerequisites
- Node.js 18+
- npm or yarn

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/regolet/ISP-Management-System.git
   cd ISP-Management-System
   ```

2. **Install dependencies**
   ```bash
   npm install
   ```

3. **Start development server**
   ```bash
   npm run dev
   ```

4. **Access the application**
   - Open: http://localhost:3000
   - Login: admin / password

## 🔧 Development

### Available Scripts
```bash
npm run dev          # Start development server
npm run build        # Build for production
npm run preview      # Preview production build
npm run netlify:dev  # Start with Netlify Dev
```

### Netlify Dev
For production-like local development:
```bash
npm run netlify:dev
```

## 📁 Project Structure

```
ISP-Management-System/
├── src/                    # React application
│   ├── components/         # React components
│   ├── contexts/          # React contexts
│   └── main.tsx          # Entry point
├── public/                # Static assets
├── dist/                  # Built application
├── netlify.toml          # Netlify configuration
├── package.json           # Dependencies
└── README.md             # This file
```

## 🌐 Deployment

### Netlify (Recommended)
1. Connect to Netlify
2. Build command: `npm run build`
3. Publish directory: `dist`

### Environment Variables
- `REACT_APP_API_URL` - Backend API URL
- `NODE_ENV` - Environment (development/production)

## 🔒 Authentication

### Default Login
- **Username**: `admin`
- **Password**: `password`

### Features
- JWT token authentication
- Session management
- Protected routes
- Login notifications

## 🎨 UI Features

- **Responsive design** for all devices
- **Modern UI** with smooth animations
- **Loading states** and error handling
- **Success/Error notifications**
- **Accessibility** compliant

## 📊 Planned Features

- **Client Management**: Add, edit, delete clients
- **Subscription Management**: Manage client subscriptions
- **Billing & Invoices**: Generate and track invoices
- **Payment Processing**: Track payments
- **User Management**: Role-based access control
- **Dashboard**: Overview of system metrics

## 🛠️ Troubleshooting

### Common Issues

1. **Port conflicts**
   ```bash
   # Use different port
   npm run dev -- --port 3001
   ```

2. **Build errors**
   ```bash
   # Clear cache and reinstall
   rm -rf node_modules package-lock.json
   npm install
   ```

3. **Database connection**
   - Check Neon PostgreSQL connection string
   - Verify environment variables
   - Test database connectivity

## 📝 License

This project is licensed under the MIT License.

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

---

**Note**: This is a modernized React application. The backend API will be implemented separately.