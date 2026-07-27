<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260712120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Dodaje prestashop_id do product dla integracji z PrestaShop';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product ADD prestashop_id INT DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D34A04AD3F1F2F24 ON product (prestashop_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_D34A04AD3F1F2F24 ON product');
        $this->addSql('ALTER TABLE product DROP prestashop_id');
    }
}
