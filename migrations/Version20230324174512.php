<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230324174512 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE address CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE `order` CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE order_data CHANGE associated_order_id associated_order_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE product CHANGE category_id category_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE address CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE order_data CHANGE associated_order_id associated_order_id INT NOT NULL');
        $this->addSql('ALTER TABLE `order` CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE product CHANGE category_id category_id INT NOT NULL');
    }
}
