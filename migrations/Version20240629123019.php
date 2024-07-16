<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Exception\IrreversibleMigration;

final class Version20240629123019 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription() : string
    {
        return 'Add api user - jooblo';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        $this->addSql("
            INSERT INTO `user` (`id`, `username`, `roles`, `password`, `avatar`, `displayed_username`) VALUES
            (2,	'jooblo',	'[\"ROLE_SUPER_ADMIN\"]',	'\$2y\$13\$ZzB.ZQeHkSlAZi927yZMves5GxwyySrr1SFqnvauqeLOa4VBZqYh6',	NULL,	NULL);
        ");

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        throw new IrreversibleMigration();
    }
}
