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
        calendar_event_id VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✓ Bookings table created\n";
    
    // Add calendar_event_id column if it doesn't exist (for existing databases)
    try {
        $db->exec("ALTER TABLE sportoase_bookings ADD COLUMN IF NOT EXISTS calendar_event_id VARCHAR(255)");
    } catch (PDOException $e) {
        // Column might already exist, ignore error
    }
    
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
    
    // Fixed offer names table (for renaming fixed offers)
    $db->exec("CREATE TABLE IF NOT EXISTS sportoase_fixed_offer_names (
        offer_key VARCHAR(100) PRIMARY KEY,
        custom_name VARCHAR(255) NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✓ Fixed offer names table created\n";
    
    // Fixed offer placements table (which offer is on which day/period)
    $db->exec("CREATE TABLE IF NOT EXISTS sportoase_fixed_offer_placements (
        id SERIAL PRIMARY KEY,
        weekday INTEGER NOT NULL,
        period INTEGER NOT NULL,
        offer_name VARCHAR(100) NOT NULL,
        UNIQUE(weekday, period)
    )");
    echo "✓ Fixed offer placements table created\n";
    
    // Insert default fixed offer placements
    $db->exec("INSERT INTO sportoase_fixed_offer_placements (weekday, period, offer_name) VALUES
        (1, 1, 'Aktivierung'),
        (1, 3, 'Regulation / Entspannung'),
        (1, 5, 'Konflikt-Reset'),
        (2, 2, 'Turnen / flexibel'),
        (2, 4, 'Wochenstart Warm-Up'),
        (3, 1, 'Aktivierung'),
        (3, 3, 'Regulation / Entspannung'),
        (3, 5, 'Konflikt-Reset'),
        (4, 2, 'Turnen / flexibel'),
        (4, 5, 'Wochenstart Warm-Up'),
        (5, 2, 'Turnen / flexibel'),
        (5, 4, 'Wochenstart Warm-Up'),
        (5, 5, 'Konflikt-Reset')
        ON CONFLICT (weekday, period) DO NOTHING");
    echo "✓ Default fixed offer placements inserted\n";
    
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
