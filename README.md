# ISP Management System

A comprehensive system for managing Internet Service Provider operations, including client management, subscriptions, billing, and network infrastructure.

## Features

- Subscription Management
- Billing and Payments
- Network Infrastructure (OLT and LCP devices)
- User Management with Role-based Access Control

## SQLite Database Setup

This application uses SQLite as its database, making it portable and easy to set up without requiring a separate database server.

### MySQL to SQLite Compatibility

The application was originally designed for MySQL but has been adapted to work with SQLite. The following compatibility features have been implemented:

- Automatic conversion of MySQL-specific SQL syntax to SQLite
- Support for date functions (YEAR, MONTH, DAY) using SQLite's strftime
- Table structure adaptations for SQLite compatibility

### Database Location

The SQLite database file is stored at:
```
/database/isp-management.sqlite
```

### Automatic Initialization

The database is automatically initialized when the application starts. The schema is defined in:
```
/database/sqlite_schema.sql
```

### Resetting the Database

If you need to reset the database to its initial state, you can run:
```
php reset_database.php
```
This will recreate all tables and reset the data.

### Default Admin User

A default admin user is created during initialization:
- Username: admin
- Password: password
- Email: admin@example.com

## Installation

1. Clone the repository
2. Make sure PHP 7.4+ is installed with PDO SQLite extension
3. Navigate to the project directory
4. Start a PHP server:
   ```
   php -S localhost:8000
   ```
5. Access the application at http://localhost:8000

## Requirements

- PHP 7.4 or higher
- PDO SQLite extension
- Modern web browser

## License

This project is licensed under the MIT License - see the LICENSE file for details.