<?php declare(strict_types = 1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180102182942 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('CREATE TEMPORARY TABLE __temp__profile AS SELECT id, name, email, description, login_key FROM profile');
        $this->addSql('DROP TABLE profile');
        $this->addSql('CREATE TABLE profile (id INTEGER NOT NULL, description CLOB NOT NULL COLLATE BINARY, login_key CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:guid)
        , name VARCHAR(100) NOT NULL, email VARCHAR(100) NOT NULL, confirmed BOOLEAN DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO profile (id, name, email, description, login_key) SELECT id, name, email, description, login_key FROM __temp__profile');
        $this->addSql('DROP TABLE __temp__profile');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('CREATE TEMPORARY TABLE __temp__profile AS SELECT id, login_key, name, email, description FROM profile');
        $this->addSql('DROP TABLE profile');
        $this->addSql('CREATE TABLE profile (id INTEGER NOT NULL, login_key CHAR(36) NOT NULL --(DC2Type:guid)
        , description CLOB NOT NULL, name CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:guid)
        , email CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:guid)
        , PRIMARY KEY(id))');
        $this->addSql('INSERT INTO profile (id, login_key, name, email, description) SELECT id, login_key, name, email, description FROM __temp__profile');
        $this->addSql('DROP TABLE __temp__profile');
    }
}
