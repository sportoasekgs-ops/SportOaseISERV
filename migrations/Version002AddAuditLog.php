<?php

declare(strict_types=1);

namespace SportOase\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version002AddAuditLog extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add audit log table for tracking changes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE sportoase_audit_logs (
            id SERIAL PRIMARY KEY,
            entity_type VARCHAR(100) NOT NULL,
            entity_id INTEGER NOT NULL,
            action VARCHAR(50) NOT NULL,
            user_id INTEGER NOT NULL,
            username VARCHAR(255) NOT NULL,
            changes JSON,
            description TEXT,
            ip_address VARCHAR(45),
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES sportoase_users(id) ON DELETE CASCADE
        )');
        
        $this->addSql('CREATE INDEX idx_entity ON sportoase_audit_logs(entity_type, entity_id)');
        $this->addSql('CREATE INDEX idx_created ON sportoase_audit_logs(created_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS sportoase_audit_logs');
    }
}
