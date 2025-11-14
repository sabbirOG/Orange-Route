# 🚍 OrangeRoute - Mobile-First Shuttle Tracking

A fast, simple, mobile-optimized web app for tracking university shuttles in real-time.

## Features

- 📱 **Mobile-First Design** - Optimized for phones with touch-friendly UI
- 🗺️ **Real-Time Map** - Track shuttles live on an interactive map
- 🚗 **Driver Mode** - Drivers can share their location automatically
- 👥 **Multi-Role Support** - Students, Drivers, and Admin accounts
- ⚡ **Fast & Lightweight** - Vanilla JS, no heavy frameworks
- 🔒 **Secure** - Password hashing, prepared statements, CSRF protection
- 📲 **PWA Ready** - Install as an app on your phone

## Tech Stack

- **Backend**: PHP 8.0+ with PDO (MySQL)
- **Frontend**: Vanilla HTML/CSS/JavaScript
- **Maps**: Leaflet.js + OpenStreetMap
- **Database**: MySQL 8.0+

## Quick Setup

### 1. Install Dependencies
```bash
composer install
```

### 2. Configure Environment
```bash
cp .env.example .env
# Edit .env with your database credentials
```

### 3. Create Database
```sql
CREATE DATABASE orangeroute CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE orangeroute;
SOURCE database/schema_v2.sql;
```

### 4. Start Server
```bash
php -S localhost:8000 -t public
```

### 5. Open in Browser
Visit: http://localhost:8000

**Default Admin Login:**
- Email: `admin@orangeroute.local`
- Password: `Admin@123` ⚠️ Change immediately!

## Project Structure

```
OrangeRoute/
├── public/              # Web root
│   ├── index.php
│   ├── pages/           # All pages (login, map, driver, profile)
│   ├── api/             # API endpoints
│   ├── manifest.json    # PWA manifest
│   └── sw.js            # Service worker
├── src/                 # PHP classes
│   ├── Auth.php
│   ├── Database.php
│   ├── Session.php
│   └── CSRF.php
├── assets/
│   ├── css/mobile.css   # Mobile-first styles
│   └── js/              # JavaScript utilities
├── config/
│   └── bootstrap.php    # App initialization
└── database/
    └── schema_v2.sql    # Database schema
```

## Mobile Features

✅ Touch-friendly buttons (min 44px)  
✅ Bottom navigation for thumb access  
✅ Swipeable bottom sheets  
✅ Geolocation integration  
✅ Real-time location tracking  
✅ Installable as PWA  
✅ Offline support (coming soon)  
✅ Fast loading (<2s)  

## Usage

### For Students
1. Login → View map → See shuttle locations in real-time
2. Tap shuttles for info
3. Use "My Location" button to center map

### For Drivers
1. Login → Go to Driver Mode
2. Tap "Start Tracking" to share location
3. Your shuttle appears on students' maps

### For Admins
1. Create users, assign drivers to shuttles
2. Monitor all activity

## API Endpoints

- `POST /api/locations/update.php` - Update shuttle location (drivers)
- `GET /api/locations/current.php` - Get all shuttle positions
- `GET /api/logout.php` - Logout

## Security

- ✅ Password hashing (bcrypt)
- ✅ SQL injection prevention (prepared statements)
- ✅ CSRF protection
- ✅ XSS protection (output escaping)
- ✅ Secure sessions

## Mobile Testing

Test on real devices or use Chrome DevTools:
1. Open DevTools (F12)
2. Toggle device toolbar (Ctrl+Shift+M)
3. Select mobile device
4. Test touch interactions

## Deployment

For production:
1. Set `APP_DEBUG=false` in `.env`
2. Use HTTPS
3. Set `SESSION_SECURE=true`
4. Configure proper web server (Apache/Nginx)
5. Enable gzip compression
6. Set up database backups

## Performance

- Initial load: ~100KB
- Map loads: Lazy (on demand)
- Location updates: Every 10s
- No jQuery or heavy frameworks
- Optimized for 3G networks

## Browser Support

- Chrome/Edge (mobile & desktop)
- Safari (iOS 12+)
- Firefox
- Samsung Internet

## License

MIT License

## Support

For issues: https://github.com/sabbirOG/Orange-Route/issues

---

**Built for mobile users first** 📱🚍
