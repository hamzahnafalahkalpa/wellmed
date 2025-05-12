<?php

namespace Projects\Klinik\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\ConsoleOutput;

class MigrateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'klinik:migrate {--seed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run migrations for the Klinik project';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $migrationsPath = realpath(__DIR__ . '/../Database/Migrations');

        if (!is_dir($migrationsPath)) {
            $this->error("The migrations path does not exist: {$migrationsPath}");
            return Command::FAILURE;
        }
        
        // Potong base_path untuk mendapatkan path relatif
        $relativePath = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $migrationsPath);
        
        // Ambil daftar file migrasi
        $migrationFiles = scandir($migrationsPath);
        $migrationFiles = array_filter($migrationFiles, fn($file) => str_ends_with($file, '.php'));

        if (empty($migrationFiles)) {
            $this->info("No migration files found in: $migrationsPath");
            return Command::SUCCESS;
        }

        $this->info("Running migrations from: $relativePath");

        // Gunakan ConsoleOutput agar output bisa langsung tampil tanpa buffer
        $output = new ConsoleOutput();

        foreach ($migrationFiles as $file) {
            // Jalankan Artisan migrate satu per satu
            Artisan::call('migrate', [
                '--path' => $relativePath,
                '--force' => true, // Hindari konfirmasi prompt
            ]);

            // Ambil output dari Artisan dan tampilkan langsung
            $outputString = Artisan::output();
            if (strpos($outputString, 'Nothing to migrate') === false) {
                $this->info("Running migration: $file");
                $output->write($outputString);
            }
        }

        $this->info("Migrations for Klinik project have been successfully executed.");

        if ($this->option('seed')){
            $this->info("Run seeding");
            Artisan::call('klinik:seed');
            $this->info("Seeded");
        }
        return Command::SUCCESS;
    }
}
