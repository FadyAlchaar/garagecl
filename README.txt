========================================
GARAGE INSPECTION SYSTEM v2.0
========================================

SETUP STEPS:
1. Extract this folder to: C:\xampp\htdocs\garage_cl\
2. Start XAMPP (Apache + MySQL)
3. Open phpMyAdmin: http://localhost/phpmyadmin
4. Create database: garage_cl
5. Import file: database/schema.sql
6. Run in command prompt:
      cd C:\xampp\htdocs\garage_cl
      composer require mpdf/mpdf
7. Open: http://localhost/garage_cl
8. Login: admin / Admin@1234  (CHANGE THIS!)

IF YOUR FOLDER NAME IS DIFFERENT:
   Edit config/app.php line:
   define('APP_URL', 'http://localhost/YOUR_FOLDER_NAME');

FILES STRUCTURE:
  config/         - Database, app config, auth, language
  modules/        - All pages (dashboard, reports, search, etc.)
  api/            - Save/export/upload endpoints
  api/v1/         - REST API (token-based)
  pdf/            - PDF generator
  assets/         - CSS, JS, fonts, uploads
  database/       - SQL schema
  includes/       - Header/footer

DEFAULT LOGIN:
  Username: admin
  Password: Admin@1234

PAGES:
  /modules/login.php           - Login
  /modules/dashboard.php       - Main dashboard
  /modules/report-new.php      - Create/edit report
  /modules/report-view.php     - View report
  /modules/search.php          - Advanced search
  /modules/vehicle-history.php - Vehicle inspection history
  /modules/statistics.php      - Charts & stats
  /modules/users.php           - User management (admin only)
  /modules/settings.php        - Shop settings & logo
  /pdf/generate.php?id=X       - Print report as PDF

REST API:
  Add token to users page, then:
  GET /api/v1/?path=stats&token=YOUR_TOKEN
  GET /api/v1/?path=reports&token=YOUR_TOKEN
  GET /api/v1/?path=vehicles&plate=ABC123&token=YOUR_TOKEN
========================================
