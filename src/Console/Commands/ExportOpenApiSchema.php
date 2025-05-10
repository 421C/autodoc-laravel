<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Console\Commands;

use AutoDoc\Commands\ExportOpenApiSchema as ExportCommand;
use AutoDoc\Config;
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
        /** @var ConfigArray */
        $configArray = config('autodoc');

        $config = new Config($configArray);

        /** @var ?string */
        $workspaceKey = $this->argument('workspace');

        (new ExportCommand)($config, $workspaceKey);

        return Command::SUCCESS;
    }
}
