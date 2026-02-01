<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ImportDatabase extends Command
{
    protected $signature = 'db:import {file}';
    protected $description = 'Import a SQL file into the database';

    public function handle()
    {
        $file = $this->argument('file');

        if (!File::exists($file)) {
            $this->error("File not found: $file");
            return 1;
        }

        $this->info("Reading SQL file...");
        $sql = File::get($file);

        // Remove comments to avoid issues
        $lines = explode("\n", $sql);
        $commands = '';
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line && !str_starts_with($line, '--') && !str_starts_with($line, '#')) {
                $commands .= $line . "\n";
            }
        }

        // Split by semicolon
        $statements = array_filter(array_map('trim', explode(';', $commands)));

        $bar = $this->output->createProgressBar(count($statements));
        $bar->start();

        DB::beginTransaction();
        try {
            // Disable foreign key checks for import
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    DB::statement($statement);
                }
                $bar->advance();
            }

            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            DB::commit();
            
            $bar->finish();
            $this->newLine();
            $this->info("Database imported successfully!");
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error importing database: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
