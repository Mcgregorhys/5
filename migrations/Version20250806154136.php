<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250806154136 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        // $this->addSql(<<<'SQL'
        //     CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, name_of_cat VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        // SQL);
        // $this->addSql(<<<'SQL'
        //     ALTER TABLE product ADD category_id INT NOT NULL
        // SQL);
        // $this->addSql(<<<'SQL'
        //     ALTER TABLE product ADD CONSTRAINT FK_D34A04AD12469DE2 FOREIGN KEY (category_id) REFERENCES category (id)
        // SQL);
        // $this->addSql(<<<'SQL'
        //     CREATE INDEX IDX_D34A04AD12469DE2 ON product (category_id)
        // SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD12469DE2
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE category
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_D34A04AD12469DE2 ON product
        SQL);
        // $this->addSql(<<<'SQL'
        //     ALTER TABLE product DROP category_id
        // SQL);
    }
}
