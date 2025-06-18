<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Console\Commands;

use AutoDoc\Laravel\ConfigLoader;
use Illuminate\Console\Command;

class UpdateTypeScriptStructures extends Command
{
    protected $signature = 'autodoc:ts-sync {working_directory?}';

    protected $description = 'Read `@autodoc` tags in typescript code and update typescript types from PHP structures.';

    public function handle(): int
    {
        $config = (new ConfigLoader)->load();

        /** @var ?string */
        $workingDirectory = $this->argument('working_directory');

        $updatedFiles = (new \AutoDoc\Commands\UpdateTypeScriptStructures($config))->run($workingDirectory);

        foreach ($updatedFiles as $file) {
            $this->line('Updated ' . $file['filePath'] . ' (' . $file['processedTags'] . ' tags)');
        }

        return Command::SUCCESS;
    }
}
