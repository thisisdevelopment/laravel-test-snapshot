<?php

namespace ThisIsDevelopment\LaravelTestSnapshot;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Symfony\Component\Finder\SplFileInfo;

class TestSnapshotCommand extends Command
{
    protected $signature = 'test:snapshot {--force} {--remove}';

    protected $description = 'Snapshots the initial database state to speedup testing';

    public function handle()
    {
        if ($this->option('remove')) {
            $baseName = $this->getSnapshotBaseFilename();
            if (file_exists($baseName . '.db')) {
                unlink($baseName . '.db');
            }
            if (file_exists($baseName . '.checksum')) {
                unlink($baseName . '.checksum');
            }
            return;
        }

        if (!app()->environment('testing')) {
            $this->error('This command should only be used for test bootstrapping');
            return;
        }

        if (!$this->isSnapshotUpToDate() || $this->option('force')) {
            $this->initSnapshot();
        } else {
            $this->importSnapshot();
        }
    }

    private function getSnapshotBaseFilename(): string
    {
        return storage_path('app') . '/test.snapshot';
    }

    private function getPaths(): Collection
    {
        //TODO: include relevant seeder paths
        return collect(app()['migrator']->paths())
            ->concat([database_path('migrations')]);
    }

    private function getSnapshotChecksum(): int
    {
        return $this->getPaths()
            ->map(static function ($path) {
                return collect(File::allFiles($path))
                    ->sum(static function (SplFileInfo $file) {
                        return $file->getMTime();
                    });
            })->sum();
    }

    private function isSnapshotUpToDate(): bool
    {
        $baseFilename = $this->getSnapshotBaseFilename();
        $checksum = $this->getSnapshotChecksum();

        return (file_exists($baseFilename . '.db') &&
            file_exists($baseFilename . '.checksum') &&
            json_decode(file_get_contents($baseFilename . '.checksum'), false) === $checksum);
    }

    /** TODO: replace functions below with a database type specific driver, for now this only works with sqlite */

    private function initSnapshot(): void
    {
        Artisan::call('migrate:fresh');

        //TODO: post-deploy support
        //Artisan::call(PostDeployCommand::class);

        $this->exportSnapshot();
    }

    private function getDBFile(): string
    {
        $default = config('database.default');
        $dbConfig = config("database.connections.$default");
        if ($dbConfig['driver'] !== 'sqlite' || strtolower($dbConfig['database']) === ':memory:') {
            throw new \RuntimeException(
                'Unsupported database: ' . $dbConfig['driver'] . ' => ' . $dbConfig['database']
            );
        }

        return $dbConfig['database'];
    }

    private function exportSnapshot(): void
    {
        $snapshotBaseFilename = $this->getSnapshotBaseFilename();
        copy($this->getDBFile(), $snapshotBaseFilename . '.db');
        file_put_contents($snapshotBaseFilename . '.checksum', json_encode($this->getSnapshotChecksum()));
    }

    private function importSnapshot(): void
    {
        copy($this->getSnapshotBaseFilename() . '.db', $this->getDBFile());
    }
}
