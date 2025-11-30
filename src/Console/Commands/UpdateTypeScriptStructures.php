<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Console\Commands;

use AutoDoc\Laravel\ConfigLoader;
use Illuminate\Console\Command;
use Throwable;

class UpdateTypeScriptStructures extends Command
{
    protected $signature = 'autodoc:ts-sync {working_directory?}';

    protected $description = 'Read `@autodoc` tags in typescript code and update typescript types from PHP structures.';

    public function handle(): int
    {
        $config = (new ConfigLoader)->load();

        /** @var ?string */
        $workingDirectory = $this->argument('working_directory');

        $commandOutput = (new \AutoDoc\Commands\UpdateTypeScriptStructures($config))->run($workingDirectory);

        foreach ($commandOutput as $message) {
            if (isset($message['filePath'])) {
                $tagsText = $message['processedTags'] . ' tag' . ($message['processedTags'] === 1 ? '' : 's');

                $this->line('Updated <fg=bright-white>' . $this->formatFilePath($message['filePath']) . '</> <fg=gray>(' . $tagsText . ')</>');

            } else if (isset($message['error'])) {
                if ($message['error'] instanceof Throwable) {
                    $errorText = $message['error']->getMessage() . ' [' . $message['error']->getFile() . ':' . $message['error']->getLine() . ']';

                } else {
                    $errorText = $message['error'];
                }

                /** @phpstan-ignore isset.property */
                if (isset($this->components)) {
                    $this->components->error($errorText);

                } else {
                    $this->error($errorText);
                }
            }
        }

        return Command::SUCCESS;
    }

    private function formatFilePath(string $path): string
    {
        $basePath = base_path();

        if (str_starts_with($path, $basePath)) {
            return ltrim(substr($path, strlen($basePath)), '/');
        }

        return $path;
    }
}
