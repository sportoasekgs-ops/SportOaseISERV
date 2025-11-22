<?php
require_once __DIR__ . '/config.php';

echo "Setting up SportOase test database...\n\n";

$db = getDb();

try {
    // Create tables
    echo "Creating tables...\n";
    
    // Users table
    $db->exec("CREATE TABLE IF NOT EXISTS sportoase_users (
        id SERIAL PRIMARY KEY,
        username VARCHAR(255) UNIQUE NOT NULL,
        email VARCHAR(255) NOT NULL,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(50) NOT NULL DEFAULT 'teacher',
        active BOOLEAN DEFAULT true,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✓ Users table created\n";
    
    // Bookings table
    $db->exec("CREATE TABLE IF NOT EXISTS sportoase_bookings (
        id SERIAL PRIMARY KEY,
        user_id INTEGER REFERENCES sportoase_users(id) ON DELETE CASCADE,
        booking_date DATE NOT NULL,
        period INTEGER NOT NULL,
        teacher_name VARCHAR(255) NOT NULL,
        students_json TEXT NOT NULL,
        offer_details TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✓ Bookings table created\n";
    
    // Slot names table
    $db->exec("CREATE TABLE IF NOT EXISTS sportoase_slot_names (
        id SERIAL PRIMARY KEY,
        slot_date DATE NOT NULL,
        period INTEGER NOT NULL,
        custom_name VARCHAR(255),
        UNIQUE(slot_date, period)
    )");
    echo "✓ Slot names table created\n";
    
    // Blocked slots table
    $db->exec("CREATE TABLE IF NOT EXISTS sportoase_blocked_slots (
        id SERIAL PRIMARY KEY,
        slot_date DATE NOT NULL,
        period INTEGER NOT NULL,
        reason VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(slot_date, period)
    )");
    echo "✓ Blocked slots table created\n";
    
    // Notifications table
    $db->exec("CREATE TABLE IF NOT EXISTS sportoase_notifications (
        id SERIAL PRIMARY KEY,
        booking_id INTEGER REFERENCES sportoase_bookings(id) ON DELETE CASCADE,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✓ Notifications table created\n";
    
    // Create test users
    echo "\nCreating test users...\n";
    
    $password = password_hash('test123', PASSWORD_DEFAULT);
    
    // Admin user
    $db->exec("INSERT INTO sportoase_users (username, email, password, role) 
               VALUES ('admin', 'admin@test.de', '$password', 'admin')
               ON CONFLICT (username) DO NOTHING");
    echo "✓ Admin user: admin / test123\n";
    
    // Teacher 1
    $db->exec("INSERT INTO sportoase_users (username, email, password, role) 
               VALUES ('lehrer1', 'lehrer1@test.de', '$password', 'teacher')
               ON CONFLICT (username) DO NOTHING");
    echo "✓ Teacher 1: lehrer1 / test123\n";
    
    // Teacher 2
    $db->exec("INSERT INTO sportoase_users (username, email, password, role) 
               VALUES ('lehrer2', 'lehrer2@test.de', '$password', 'teacher')
               ON CONFLICT (username) DO NOTHING");
    echo "✓ Teacher 2: lehrer2 / test123\n";
    
    echo "\n✅ Database setup complete!\n";
    echo "\nYou can now login with:\n";
    echo "  - admin / test123 (Administrator)\n";
    echo "  - lehrer1 / test123 (Teacher)\n";
    echo "  - lehrer2 / test123 (Teacher)\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
