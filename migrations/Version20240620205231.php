<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240620205231 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs

        // Ensure all existing records have valid foreign keys
        $this->addSql('UPDATE related_table SET category_id = (SELECT id FROM category LIMIT 1) WHERE category_id IS NULL');

        // Check if the column already exists before adding it
        $this->addSql('ALTER TABLE related_table ADD IF NOT EXISTS category_id INT');
        
        // Add foreign key constraint
        $this->addSql('ALTER TABLE related_table ADD CONSTRAINT IF NOT EXISTS FK_D754D55012469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

        // Remove foreign key constraint
        $this->addSql('ALTER TABLE related_table DROP FOREIGN KEY IF EXISTS FK_D754D55012469DE2');
        
        // Remove the column if it exists
        $this->addSql('ALTER TABLE related_table DROP COLUMN IF EXISTS category_id');
    }
}
