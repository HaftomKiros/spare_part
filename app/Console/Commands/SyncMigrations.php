<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncMigrations extends Command
{
    protected $signature   = 'migrate:sync';
    protected $description = 'Mark all pending migrations as run without executing them (safe for pre-existing databases)';

    public function handle(): int
    {
        // Get the current highest batch number
        $maxBatch = (int) DB::table('migrations')->max('batch');
        $batch    = $maxBatch + 1;

        // All migrations that exist on disk
        $files = collect(glob(database_path('migrations/*.php')))
            ->map(fn($f) => pathinfo($f, PATHINFO_FILENAME));

        // Already recorded migrations
        $ran = DB::table('migrations')->pluck('migration')->toArray();

        // Only the ones not yet recorded
        $pending = $files->diff($ran);

        if ($pending->isEmpty()) {
            $this->info('Nothing to sync — all migrations are already recorded.');
            return 0;
        }

        foreach ($pending as $migration) {
            DB::table('migrations')->insert([
                'migration' => $migration,
                'batch'     => $batch,
            ]);
            $this->line("  Marked as run: <info>{$migration}</info>");
        }

        $this->info("\nDone. {$pending->count()} migration(s) marked as run. No data was changed.");
        return 0;
    }
}
