<?php

declare(strict_types=1);

namespace SportOase\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version003AddFixedOffers extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add fixed offer names and placements tables';
    }

    public function up(Schema $schema): void
    {
        // Create fixed_offer_names table
        $this->addSql('CREATE TABLE IF NOT EXISTS sportoase_fixed_offer_names (
            id SERIAL PRIMARY KEY,
            offer_key VARCHAR(100) UNIQUE NOT NULL,
            default_name VARCHAR(255) NOT NULL,
            custom_name VARCHAR(255) NOT NULL,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )');

        // Create fixed_offer_placements table
        $this->addSql('CREATE TABLE IF NOT EXISTS sportoase_fixed_offer_placements (
            id SERIAL PRIMARY KEY,
            weekday INTEGER NOT NULL CHECK (weekday >= 1 AND weekday <= 5),
            period INTEGER NOT NULL CHECK (period >= 1 AND period <= 6),
            offer_name VARCHAR(100) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE (weekday, period)
        )');

        // Insert default fixed offer names
        $defaultOffers = [
            ['key' => 'Aktivierung', 'name' => 'Aktivierung'],
            ['key' => 'Regulation/Entspannung', 'name' => 'Regulation/Entspannung'],
            ['key' => 'Konflikt-Reset', 'name' => 'Konflikt-Reset'],
            ['key' => 'Turnen/flexibel', 'name' => 'Turnen/flexibel'],
            ['key' => 'Wochenstart Warm-Up', 'name' => 'Wochenstart Warm-Up'],
        ];

        foreach ($defaultOffers as $offer) {
            $this->addSql("INSERT INTO sportoase_fixed_offer_names (offer_key, default_name, custom_name) 
                VALUES ('{$offer['key']}', '{$offer['name']}', '{$offer['name']}')
                ON CONFLICT (offer_key) DO NOTHING");
        }

        // Insert default fixed offer placements
        // Monday (1): Periods 1, 3, 5
        $this->addSql("INSERT INTO sportoase_fixed_offer_placements (weekday, period, offer_name) VALUES (1, 1, 'Wochenstart Warm-Up') ON CONFLICT DO NOTHING");
        $this->addSql("INSERT INTO sportoase_fixed_offer_placements (weekday, period, offer_name) VALUES (1, 3, 'Aktivierung') ON CONFLICT DO NOTHING");
        $this->addSql("INSERT INTO sportoase_fixed_offer_placements (weekday, period, offer_name) VALUES (1, 5, 'Regulation/Entspannung') ON CONFLICT DO NOTHING");

        // Tuesday (2): Periods 2, 4
        $this->addSql("INSERT INTO sportoase_fixed_offer_placements (weekday, period, offer_name) VALUES (2, 2, 'Konflikt-Reset') ON CONFLICT DO NOTHING");
        $this->addSql("INSERT INTO sportoase_fixed_offer_placements (weekday, period, offer_name) VALUES (2, 4, 'Turnen/flexibel') ON CONFLICT DO NOTHING");

        // Wednesday (3): Periods 1, 3, 5
        $this->addSql("INSERT INTO sportoase_fixed_offer_placements (weekday, period, offer_name) VALUES (3, 1, 'Aktivierung') ON CONFLICT DO NOTHING");
        $this->addSql("INSERT INTO sportoase_fixed_offer_placements (weekday, period, offer_name) VALUES (3, 3, 'Regulation/Entspannung') ON CONFLICT DO NOTHING");
        $this->addSql("INSERT INTO sportoase_fixed_offer_placements (weekday, period, offer_name) VALUES (3, 5, 'Konflikt-Reset') ON CONFLICT DO NOTHING");

        // Thursday (4): Periods 2, 5
        $this->addSql("INSERT INTO sportoase_fixed_offer_placements (weekday, period, offer_name) VALUES (4, 2, 'Turnen/flexibel') ON CONFLICT DO NOTHING");
        $this->addSql("INSERT INTO sportoase_fixed_offer_placements (weekday, period, offer_name) VALUES (4, 5, 'Aktivierung') ON CONFLICT DO NOTHING");

        // Friday (5): Periods 2, 4, 5
        $this->addSql("INSERT INTO sportoase_fixed_offer_placements (weekday, period, offer_name) VALUES (5, 2, 'Regulation/Entspannung') ON CONFLICT DO NOTHING");
        $this->addSql("INSERT INTO sportoase_fixed_offer_placements (weekday, period, offer_name) VALUES (5, 4, 'Konflikt-Reset') ON CONFLICT DO NOTHING");
        $this->addSql("INSERT INTO sportoase_fixed_offer_placements (weekday, period, offer_name) VALUES (5, 5, 'Turnen/flexibel') ON CONFLICT DO NOTHING");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS sportoase_fixed_offer_placements');
        $this->addSql('DROP TABLE IF EXISTS sportoase_fixed_offer_names');
    }
}
