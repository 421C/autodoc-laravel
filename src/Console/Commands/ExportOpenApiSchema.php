<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Console\Commands;

use AutoDoc\Laravel\ConfigLoader;
use Illuminate\Console\Command;

class ExportOpenApiSchema extends Command
{
    protected $signature = 'autodoc:export {workspace?}';

    protected $description = 'Export OpenApi 3.1 schema JSON file(s)';

    public function handle(): int
    {
        $config = (new ConfigLoader)->load();

        /** @var ?string */
        $workspaceKey = $this->argument('workspace');

        (new \AutoDoc\Commands\ExportOpenApiSchema)($config, $workspaceKey);

        return Command::SUCCESS;
    }
}
