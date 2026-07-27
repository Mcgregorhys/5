<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260701120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Dodaje tabelę api_key do autoryzacji REST API';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE api_key (id INT AUTO_INCREMENT NOT NULL, owner_id INT NOT NULL, name VARCHAR(100) NOT NULL, token_hash VARCHAR(64) NOT NULL, token_prefix VARCHAR(12) NOT NULL, permissions JSON NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', last_used_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', is_active TINYINT(1) NOT NULL, INDEX IDX_C912ED9D7E3C61F9 (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE api_key ADD CONSTRAINT FK_C912ED9D7E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE api_key DROP FOREIGN KEY FK_C912ED9D7E3C61F9');
        $this->addSql('DROP TABLE api_key');
    }
}
