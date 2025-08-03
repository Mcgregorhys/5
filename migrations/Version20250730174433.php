<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250730174433 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE product ADD domestic_shipping_name VARCHAR(50) NOT NULL, ADD domestic_shipping_price NUMERIC(10, 2) NOT NULL, ADD domestic_shipping_delivery_time_days INT NOT NULL, ADD international_shipping_name VARCHAR(50) NOT NULL, ADD international_shipping_price NUMERIC(10, 2) NOT NULL, ADD international_shipping_delivery_time_days INT NOT NULL, DROP domestic_shipping, DROP international_shipping
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE product ADD domestic_shipping LONGTEXT NOT NULL COMMENT '(DC2Type:object)', ADD international_shipping LONGTEXT NOT NULL COMMENT '(DC2Type:object)', DROP domestic_shipping_name, DROP domestic_shipping_price, DROP domestic_shipping_delivery_time_days, DROP international_shipping_name, DROP international_shipping_price, DROP international_shipping_delivery_time_days
        SQL);
    }
}
