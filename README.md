# Portfolio Analytics Platform

A comprehensive portfolio analytics platform built with Laravel and React for financial institutions to manage and analyze their loan portfolios.

## 🚀 Quick Start

### Prerequisites
- PHP 8.1+
- Composer
- Node.js 18+
- SQLite (included)

### Installation
1. Clone the repository
2. Run `composer install`
3. Run `npm install`
4. Run `php artisan migrate:fresh --seed`
5. Run `npm run dev`
6. Visit `http://localhost:8000`

### Login Credentials
- **Admin**: admin@gralix.co / password
- **Analyst**: analyst@gralix.co / password  
- **Viewer**: viewer@gralix.co / password

## 📊 Features

- **Customer Management**: Complete customer lifecycle management
- **Product Configuration**: Flexible product setup with custom fields
- **Formula Engine**: Advanced calculation engine for metrics
- **Dashboard Builder**: Interactive dashboards with multiple widget types
- **Data Import/Export**: CSV import with validation and Excel export
- **Role-Based Access**: Admin, Analyst, and Viewer roles
- **Audit Trail**: Complete activity logging

## 📋 Documentation

For detailed usage instructions, see [PORTFOLIO_ANALYTICS_USER_GUIDE.md](PORTFOLIO_ANALYTICS_USER_GUIDE.md)

## 🏗️ System Architecture

- **Backend**: Laravel 10 with PHP 8.1+
- **Frontend**: React with Inertia.js
- **Database**: SQLite with comprehensive migrations
- **Authentication**: Laravel Sanctum
- **Permissions**: Spatie Laravel Permission

## 📈 Widget Types

- **KPI Widgets**: Single metric displays
- **Table Widgets**: Detailed data listings
- **Chart Widgets**: Pie, Bar, Line, and Heatmap charts
- **Interactive Features**: Filtering, sorting, and export

## 🔧 Development

### Running Tests
```bash
php artisan test
```

### Building Assets
```bash
npm run build
```

### Database Seeding
```bash
php artisan migrate:fresh --seed
```

## 📞 Support

For support and questions, refer to the comprehensive user guide included in the project.
