<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version001CreateInitialSchema extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates initial SportOase database schema with all tables and indexes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE sportoase_users (
            id SERIAL PRIMARY KEY,
            username VARCHAR(255) UNIQUE NOT NULL,
            email VARCHAR(255) UNIQUE,
            full_name VARCHAR(255),
            role VARCHAR(50) NOT NULL DEFAULT \'teacher\',
            is_active BOOLEAN DEFAULT true,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )');

        $this->addSql('CREATE TABLE sportoase_bookings (
            id SERIAL PRIMARY KEY,
            date DATE NOT NULL,
            period INTEGER NOT NULL,
            weekday VARCHAR(20) NOT NULL,
            teacher_id INTEGER NOT NULL REFERENCES sportoase_users(id) ON DELETE CASCADE,
            teacher_name VARCHAR(255) NOT NULL,
            teacher_class VARCHAR(255) NOT NULL,
            students_json JSONB NOT NULL DEFAULT \'[]\',
            offer_type VARCHAR(100) NOT NULL,
            offer_label VARCHAR(255) NOT NULL,
            calendar_event_id VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(date, period)
        )');

        $this->addSql('CREATE TABLE sportoase_slot_names (
            id SERIAL PRIMARY KEY,
            weekday VARCHAR(20) NOT NULL,
            period INTEGER NOT NULL,
            label VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(weekday, period)
        )');

        $this->addSql('CREATE TABLE sportoase_blocked_slots (
            id SERIAL PRIMARY KEY,
            date DATE NOT NULL,
            period INTEGER NOT NULL,
            weekday VARCHAR(20) NOT NULL,
            reason VARCHAR(255) DEFAULT \'Beratung\',
            blocked_by_id INTEGER NOT NULL REFERENCES sportoase_users(id) ON DELETE CASCADE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(date, period)
        )');

        $this->addSql('CREATE TABLE sportoase_notifications (
            id SERIAL PRIMARY KEY,
            booking_id INTEGER NOT NULL REFERENCES sportoase_bookings(id) ON DELETE CASCADE,
            recipient_role VARCHAR(50) NOT NULL DEFAULT \'admin\',
            notification_type VARCHAR(100) NOT NULL,
            message TEXT NOT NULL,
            metadata_json JSONB,
            is_read BOOLEAN DEFAULT false,
            read_at TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )');

        $this->addSql('CREATE INDEX idx_sportoase_bookings_date_period ON sportoase_bookings(date, period)');
        $this->addSql('CREATE INDEX idx_sportoase_bookings_date ON sportoase_bookings(date)');
        $this->addSql('CREATE INDEX idx_sportoase_blocked_slots_date ON sportoase_blocked_slots(date)');
        $this->addSql('CREATE INDEX idx_sportoase_notifications_created_at ON sportoase_notifications(created_at DESC)');
        $this->addSql('CREATE INDEX idx_sportoase_notifications_is_read ON sportoase_notifications(is_read)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS sportoase_notifications CASCADE');
        $this->addSql('DROP TABLE IF EXISTS sportoase_blocked_slots CASCADE');
        $this->addSql('DROP TABLE IF EXISTS sportoase_slot_names CASCADE');
        $this->addSql('DROP TABLE IF EXISTS sportoase_bookings CASCADE');
        $this->addSql('DROP TABLE IF EXISTS sportoase_users CASCADE');
    }
}
