<?php

declare(strict_types=1);

namespace LPWork\Console\FileCreators\Definitions;

use LPWork\Console\FileCreators\FileCreatorDefinition;
use LPWork\Console\FileCreators\ProviderRegistration;

/**
 * Represents the database file creator definitions framework component.
 */
final readonly class DatabaseFileCreatorDefinitions implements FileCreatorDefinitionGroup
{
    /**
     * Returns all registered values for this component.
     */
    public function all(): array
    {
        return [
            new FileCreatorDefinition(
                type: 'migration',
                description: 'Create a database migration.',
                defaultDirectory: 'App/Database/Migrations',
                suffix: '',
                template: <<<'PHP'
                    <?php

                    declare(strict_types=1);

                    namespace {{ namespace }};

                    use LPWork\Database\Contracts\Connection;
                    use LPWork\Database\Migrations\Contracts\Migration;
                    use LPWork\Database\Schema\Schema;
                    use LPWork\Database\Schema\Table;

                    final readonly class {{ class }} implements Migration
                    {
                        private const TABLE = '{{ table_name }}';

                        public function up(Connection $db): void
                        {
                            new Schema($db)->create(self::TABLE, static function (Table $table): void {
                                $table
                                    ->primaryString('id')
                                    ->integer('created_at');
                            });
                        }

                        public function down(Connection $db): void
                        {
                            new Schema($db)->drop(self::TABLE);
                        }
                    }
                    PHP,
                registration: ProviderRegistration::grouped('App/Database/Migrations/MigrationsProvider.php', 'migrations', 'connection', 'default'),
            ),
            new FileCreatorDefinition(
                type: 'seeder',
                description: 'Create a database seeder.',
                defaultDirectory: 'App/Database/Seeders',
                suffix: 'Seeder',
                template: <<<'PHP'
                    <?php

                    declare(strict_types=1);

                    namespace {{ namespace }};

                    use LPWork\Database\Contracts\Connection;
                    use LPWork\Database\Seeders\Contracts\Seeder;

                    final readonly class {{ class }} implements Seeder
                    {
                        public function run(Connection $db): void
                        {
                        }
                    }
                    PHP,
                registration: ProviderRegistration::grouped('App/Database/Seeders/SeedersProvider.php', 'seeders', 'connection', 'default'),
            ),
        ];
    }
}
