# SportOase IServ Module - Database Setup Guide

## Database Migration

The module uses Doctrine Migrations to create and manage the database schema.

### Prerequisites

- PostgreSQL database (provided by IServ)
- PHP 8.0+ with PDO PostgreSQL extension
- Composer installed

### Running Migrations on IServ Production

#### Step 1: Configure Database Connection

The module will automatically use IServ's database configuration. No manual setup needed.

#### Step 2: Run Migrations

Execute the migration to create all required tables:

```bash
# Navigate to module directory
cd /usr/share/iserv/modules/sportoase

# Run the migration
php bin/console doctrine:migrations:migrate --no-interaction
```

This will create the following tables:
- `sportoase_users` - User accounts and profiles
- `sportoase_bookings` - Booking records with student data
- `sportoase_slot_names` - Custom slot labels per weekday/period
- `sportoase_blocked_slots` - Manually blocked time slots
- `sportoase_notifications` - In-app notification system

### Database Schema

#### Users Table
```sql
CREATE TABLE sportoase_users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(255) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE,
    full_name VARCHAR(255),
    role VARCHAR(50) NOT NULL DEFAULT 'teacher',
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### Bookings Table
```sql
CREATE TABLE sportoase_bookings (
    id SERIAL PRIMARY KEY,
    date DATE NOT NULL,
    period INTEGER NOT NULL,
    weekday VARCHAR(20) NOT NULL,
    teacher_id INTEGER NOT NULL REFERENCES sportoase_users(id) ON DELETE CASCADE,
    teacher_name VARCHAR(255) NOT NULL,
    teacher_class VARCHAR(255) NOT NULL,
    students_json JSONB NOT NULL DEFAULT '[]',
    offer_type VARCHAR(100) NOT NULL,
    offer_label VARCHAR(255) NOT NULL,
    calendar_event_id VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(date, period)
);
```

#### Slot Names Table
```sql
CREATE TABLE sportoase_slot_names (
    id SERIAL PRIMARY KEY,
    weekday VARCHAR(20) NOT NULL,
    period INTEGER NOT NULL,
    label VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(weekday, period)
);
```

#### Blocked Slots Table
```sql
CREATE TABLE sportoase_blocked_slots (
    id SERIAL PRIMARY KEY,
    date DATE NOT NULL,
    period INTEGER NOT NULL,
    weekday VARCHAR(20) NOT NULL,
    reason VARCHAR(255) DEFAULT 'Beratung',
    blocked_by_id INTEGER NOT NULL REFERENCES sportoase_users(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(date, period)
);
```

#### Notifications Table
```sql
CREATE TABLE sportoase_notifications (
    id SERIAL PRIMARY KEY,
    booking_id INTEGER NOT NULL REFERENCES sportoase_bookings(id) ON DELETE CASCADE,
    recipient_role VARCHAR(50) NOT NULL DEFAULT 'admin',
    notification_type VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    metadata_json JSONB,
    is_read BOOLEAN DEFAULT false,
    read_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Indexes

The migration automatically creates these performance indexes:

```sql
CREATE INDEX idx_sportoase_bookings_date_period ON sportoase_bookings(date, period);
CREATE INDEX idx_sportoase_bookings_date ON sportoase_bookings(date);
CREATE INDEX idx_sportoase_blocked_slots_date ON sportoase_blocked_slots(date);
CREATE INDEX idx_sportoase_notifications_created_at ON sportoase_notifications(created_at DESC);
CREATE INDEX idx_sportoase_notifications_is_read ON sportoase_notifications(is_read);
```

### Rollback (if needed)

To rollback the migration:

```bash
php bin/console doctrine:migrations:migrate prev --no-interaction
```

This will safely drop all SportOase tables in the correct order (respecting foreign key constraints).

### Verification

Check if all tables were created successfully:

```bash
php bin/console dbal:run-sql "SELECT table_name FROM information_schema.tables WHERE table_name LIKE 'sportoase_%'"
```

Expected output:
```
sportoase_users
sportoase_bookings
sportoase_slot_names
sportoase_blocked_slots
sportoase_notifications
```

## Troubleshooting

### Migration Already Executed

If you see "No migrations to execute", the database is already up to date.

### Permission Errors

Ensure the database user has CREATE TABLE permissions:
```sql
GRANT CREATE ON SCHEMA public TO iserv_sportoase_user;
```

### Foreign Key Errors

The migration creates tables in the correct order to avoid foreign key errors:
1. Users (no dependencies)
2. Bookings (depends on Users)
3. SlotNames (no dependencies)
4. BlockedSlots (depends on Users)
5. Notifications (depends on Bookings)

If you encounter issues, ensure you're running the up() migration, not down().

## Support

For database issues, contact: sportoase.kg@gmail.com
