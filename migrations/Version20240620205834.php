<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240620205834 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Check if the column already exists before adding it
        $this->addSql('
            SET @exists := (SELECT COUNT(*) FROM information_schema.columns WHERE table_name = "menu_item" AND column_name = "category_id");
            SET @query := IF(@exists = 0, "ALTER TABLE menu_item ADD category_id INT NOT NULL", "SELECT 1");
            PREPARE stmt FROM @query;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ');

        // Ensure all existing records have a valid category_id
        $this->addSql('
            UPDATE menu_item 
            SET category_id = (SELECT id FROM category LIMIT 1) 
            WHERE category_id IS NULL OR category_id NOT IN (SELECT id FROM category)
        ');

        // Add foreign key constraint
        $this->addSql('ALTER TABLE menu_item ADD CONSTRAINT FK_D754D55012469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('CREATE INDEX IDX_D754D55012469DE2 ON menu_item (category_id)');
    }

    public function down(Schema $schema): void
    {
        // Remove foreign key constraint
        $this->addSql('ALTER TABLE menu_item DROP FOREIGN KEY FK_D754D55012469DE2');
        $this->addSql('DROP INDEX IDX_D754D55012469DE2 ON menu_item');
        $this->addSql('ALTER TABLE menu_item DROP COLUMN category_id');
    }
}
