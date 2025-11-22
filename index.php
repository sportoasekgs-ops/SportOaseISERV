<?php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SportOase - IServ Module</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px; }
        h2 { color: #666; margin-top: 30px; }
        .info-box { background: #e7f3ff; padding: 15px; border-left: 4px solid #007bff; margin: 20px 0; }
        .warning-box { background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0; }
        pre { background: #f4f4f4; padding: 15px; border-radius: 4px; overflow-x: auto; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
        ul { line-height: 1.8; }
        .file-structure { font-family: monospace; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏫 SportOase - IServ Module</h1>
        
        <div class="warning-box">
            <strong>⚠️ Important:</strong> This is an IServ module built with PHP/Symfony, not a standalone Replit application. 
            It must be packaged as a Debian package and deployed to an IServ server.
        </div>
        
        <div class="info-box">
            <strong>ℹ️ Module Information:</strong>
            <ul>
                <li><strong>Version:</strong> 1.0.0</li>
                <li><strong>Framework:</strong> Symfony 6.4+</li>
                <li><strong>Database:</strong> PostgreSQL (Doctrine ORM)</li>
                <li><strong>IServ Compatibility:</strong> 3.0+</li>
            </ul>
        </div>
        
        <h2>📦 Module Structure</h2>
        <div class="file-structure">
            <pre>sportoase/
├── composer.json              # PHP dependencies
├── manifest.xml               # IServ module manifest
├── src/
│   ├── SportOaseBundle.php    # Main bundle class
│   ├── Controller/            # Symfony controllers
│   │   ├── DashboardController.php
│   │   ├── BookingController.php
│   │   └── AdminController.php
│   ├── Entity/                # Doctrine entities
│   │   ├── User.php
│   │   ├── Booking.php
│   │   ├── SlotName.php
│   │   ├── BlockedSlot.php
│   │   └── Notification.php
│   └── Service/               # Business logic
│       ├── BookingService.php
│       └── EmailService.php
├── migrations/                # Doctrine migrations
├── templates/                 # Twig templates
├── config/                    # Symfony configuration
└── README.md</pre>
        </div>
        
        <h2>🚀 Deployment to IServ</h2>
        <ol>
            <li>
                <strong>Package as Debian Package:</strong>
                <pre>dpkg-buildpackage -us -uc</pre>
            </li>
            <li>
                <strong>Install on IServ Server:</strong>
                <pre>aptitude install iserv3-sportoase</pre>
            </li>
            <li>
                <strong>Run Database Migrations:</strong>
                <pre>php bin/console doctrine:migrations:migrate</pre>
            </li>
            <li>
                <strong>Enable Module:</strong> Via IServ Admin Panel → System → Modules
            </li>
        </ol>
        
        <h2>✨ Features</h2>
        <ul>
            <li>IServ SSO Integration</li>
            <li>Role-based Access Control (Teachers & Admins)</li>
            <li>Weekly Schedule Management</li>
            <li>Capacity Management (max 5 students per slot)</li>
            <li>Double-booking Prevention</li>
            <li>Email Notifications</li>
            <li>Admin Panel for Booking Management</li>
        </ul>
        
        <h2>📚 Documentation</h2>
        <p>See <code>README.md</code> for complete documentation.</p>
        
        <h2>🔧 Development</h2>
        <p>For local development outside IServ:</p>
        <pre>composer install
php bin/console doctrine:migrations:migrate
symfony serve</pre>
        
        <div class="info-box">
            <strong>📧 Support:</strong> sportoase.kg@gmail.com
        </div>
    </div>
</body>
</html>
