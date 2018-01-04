<?php declare(strict_types = 1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180104203245 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('CREATE TEMPORARY TABLE __temp__profile AS SELECT id, description, login_key, name, email, confirmed FROM profile');
        $this->addSql('DROP TABLE profile');
        $this->addSql('CREATE TABLE profile (id INTEGER NOT NULL, description CLOB NOT NULL COLLATE BINARY, login_key CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:guid)
        , name VARCHAR(100) NOT NULL COLLATE BINARY, email VARCHAR(100) NOT NULL COLLATE BINARY, confirmed BOOLEAN DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO profile (id, description, login_key, name, email, confirmed) SELECT id, description, login_key, name, email, confirmed FROM __temp__profile');
        $this->addSql('DROP TABLE __temp__profile');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8157AA0FF4C25C93 ON profile (login_key)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8157AA0FE7927C74 ON profile (email)');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('DROP INDEX UNIQ_8157AA0FF4C25C93');
        $this->addSql('DROP INDEX UNIQ_8157AA0FE7927C74');
        $this->addSql('CREATE TEMPORARY TABLE __temp__profile AS SELECT id, login_key, confirmed, name, email, description FROM profile');
        $this->addSql('DROP TABLE profile');
        $this->addSql('CREATE TABLE profile (id INTEGER NOT NULL, login_key CHAR(36) NOT NULL --(DC2Type:guid)
        , confirmed BOOLEAN DEFAULT NULL, name VARCHAR(100) NOT NULL, email VARCHAR(100) NOT NULL, description CLOB NOT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO profile (id, login_key, confirmed, name, email, description) SELECT id, login_key, confirmed, name, email, description FROM __temp__profile');
        $this->addSql('DROP TABLE __temp__profile');
    }
}
