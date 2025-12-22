<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Console\Commands;

use AutoDoc\Config;
use AutoDoc\Laravel\ConfigLoader;
use Closure;
use Illuminate\Console\Command;

/**
 * Process @autodoc debug tags in PHP code
 */
class ProcessAutoDocDebugTags extends Command
{
    protected $signature = 'autodoc:debug {working_directory?} {--depth=}';

    protected $description = 'Find `@autodoc debug` tags in PHP code and dump their resolved types';

    public function handle(): int
    {
        $config = (new ConfigLoader)->load();

        /** @var string */
        $workingDirectory = $this->argument('working_directory') ?? app_path();

        $resolutionDepth = $this->option('depth');
        $resolutionDepth = isset($resolutionDepth) ? intval($resolutionDepth) : null;

        /** @phpstan-ignore isset.property */
        $logInfo = fn ($message) => isset($this->components) ? $this->components->info($message) : $this->info($message);

        /** @phpstan-ignore isset.property */
        $logError = fn ($message) => isset($this->components) ? $this->components->error($message) : $this->error($message);

        $command = new class ($logInfo, $logError, $config, $resolutionDepth) extends \AutoDoc\Commands\ProcessAutoDocDebugTags
        {
            public function __construct(
                private Closure $logInfo,
                private Closure $logError,
                protected Config $config,
                protected ?int $resolutionDepth = null,
            ) {}

            public function info(string $message): void
            {
                $logInfo = $this->logInfo;
                $logInfo($message);
            }

            public function error(string $message): void
            {
                $logError = $this->logError;
                $logError($message);
            }
        };

        $command($workingDirectory);

        return Command::SUCCESS;
    }
}
