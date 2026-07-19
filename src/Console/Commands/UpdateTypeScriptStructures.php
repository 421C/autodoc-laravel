<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Console\Commands;

use AutoDoc\Laravel\ConfigLoader;
use Illuminate\Console\Command;
use Throwable;

class UpdateTypeScriptStructures extends Command
{
    protected $signature = 'autodoc:ts {working_directory?}';

    protected $description = 'Read `@autodoc` tags in typescript code and update typescript types from PHP structures.';

    public function handle(): int
    {
        $config = (new ConfigLoader)->load();

        /** @var ?string */
        $workingDirectory = $this->argument('working_directory');

        $commandOutput = (new \AutoDoc\Commands\UpdateTypeScriptStructures($config))->run($workingDirectory);

        $hasErrors = false;

        foreach ($commandOutput as $message) {
            if (isset($message['error'])) {
                $hasErrors = true;

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

            } else if (isset($message['processedTags'])) {
                $this->updatedLine($message['filePath'], $this->pluralize($message['processedTags'], 'tag'));

            } else {
                $this->updatedLine(
                    $message['filePath'],
                    $this->pluralize($message['exportedRequests'], 'request') . ', '
                        . $this->pluralize($message['exportedResponses'], 'response'),
                );
            }
        }

        return $hasErrors ? Command::FAILURE : Command::SUCCESS;
    }

    private function updatedLine(string $filePath, string $detail): void
    {
        $this->line('Updated <fg=bright-white>' . $this->formatFilePath($filePath) . '</> <fg=gray>(' . $detail . ')</>');
    }

    private function pluralize(int $count, string $noun): string
    {
        return $count . ' ' . $noun . ($count === 1 ? '' : 's');
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
