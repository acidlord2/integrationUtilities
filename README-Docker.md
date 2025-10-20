# Integration Helper - Docker Setup

This directory contains a complete Docker setup for the Integration Helper PHP application.

## 🚀 Quick Start

### Prerequisites
- Docker Desktop installed
- Docker Compose installed

### Starting the Application

1. **Build and start containers:**
   ```bash
   docker-compose up -d
   ```

2. **Access the application:**
   - **Main App**: http://localhost:8080
   - **Queue Test**: http://localhost:8080/test-queue.php
   - **phpMyAdmin**: http://localhost:8081

3. **View logs:**
   ```bash
   docker-compose logs -f php-app
   docker-compose logs -f mysql
   ```

## 📋 Services

### PHP Application (`php-app`)
- **Port**: 8080
- **Base Image**: php:7.4-apache
- **Extensions**: mysqli, pdo_mysql
- **Document Root**: `/var/www/html`

### MySQL Database (`mysql`)
- **Port**: 3306 (external)
- **Database**: `integration_db`
- **Username**: `integration_user`
- **Password**: `integration_pass`
- **Root Password**: `root_password`

### phpMyAdmin (`phpmyadmin`)
- **Port**: 8081
- **Access**: http://localhost:8081
- **Login**: Use database credentials above

## 🗄️ Database

The MySQL container automatically creates:
- Database: `integration_db`
- Queue table with proper schema
- All necessary indexes

## 📁 Volume Mounts

- `./logs` → Container logs directory
- `./uploads` → Container uploads directory
- `mysql_data` → MySQL data persistence

## 🧪 Testing

### Queue Class Test
Visit: http://localhost:8080/test-queue.php

This page will:
- ✅ Test database connectivity
- ✅ Create queue items
- ✅ Read, update, delete operations
- ✅ Transaction ID functionality
- ✅ Statistics and cleanup

### CLI Testing
```bash
docker-compose exec php-app php /var/www/html/classes/Queue/example.php
```

## 🛠️ Development

### Rebuilding after changes:
```bash
docker-compose up -d --build
```

### Accessing container shell:
```bash
docker-compose exec php-app bash
docker-compose exec mysql mysql -u integration_user -p integration_db
```

### Stopping services:
```bash
docker-compose down
```

### Complete cleanup (removes data):
```bash
docker-compose down -v
docker system prune
```

## 🔧 Configuration

### Environment Variables
Database configuration is handled via environment variables in `docker-compose.yml`:
- `DB_HOSTNAME=mysql`
- `DB_USERNAME=integration_user`  
- `DB_PASSWORD=integration_pass`
- `DB_DATABASE=integration_db`

### Custom Configuration
- Main config: `config.php`
- Docker config: `docker-config.php` (auto-detected)

## 📊 Monitoring

### Application Logs
```bash
# Real-time logs
docker-compose logs -f php-app

# Apache access logs
docker-compose exec php-app tail -f /var/log/apache2/access.log

# Apache error logs  
docker-compose exec php-app tail -f /var/log/apache2/error.log
```

### Database Logs
```bash
docker-compose logs -f mysql
```

## 🚨 Troubleshooting

### Container won't start
```bash
docker-compose logs php-app
docker-compose logs mysql
```

### Permission issues
```bash
docker-compose exec php-app chown -R www-data:www-data /var/www/html
docker-compose exec php-app chmod -R 755 /var/www/html
```

### Database connection issues
1. Check MySQL is running: `docker-compose ps`
2. Verify credentials in `docker-compose.yml`
3. Check database logs: `docker-compose logs mysql`

### Reset everything
```bash
docker-compose down -v
docker system prune -f
docker-compose up -d
```

## 📚 File Structure

```
/
├── Dockerfile              # PHP app container
├── docker-compose.yml      # Service orchestration
├── .dockerignore           # Files to exclude from build
├── docker-config.php       # Docker-specific config
├── test-queue.php          # Web-based testing interface
└── docker/
    └── mysql/
        └── init/
            └── 01-init.sql  # Database initialization
```

## 🔐 Security Notes

- Default passwords are for development only
- Change all passwords for production use
- Database is exposed on port 3306 for development
- Consider using secrets for production deployment