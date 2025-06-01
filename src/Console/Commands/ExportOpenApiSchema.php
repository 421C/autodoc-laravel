<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Console\Commands;

use AutoDoc\Commands\ExportOpenApiSchema as ExportCommand;
use AutoDoc\Config;
use AutoDoc\Laravel\ConfigLoader;
use Illuminate\Console\Command;

/**
 * @phpstan-import-type ConfigArray from Config
 */
class ExportOpenApiSchema extends Command
{
    protected $signature = 'autodoc:export {workspace?}';

    protected $description = 'Export OpenApi 3.1 schema JSON file(s)';

    public function handle(): int
    {
        $config = (new ConfigLoader)->load();

        /** @var ?string */
        $workspaceKey = $this->argument('workspace');

        (new ExportCommand)($config, $workspaceKey);

        return Command::SUCCESS;
    }
}
