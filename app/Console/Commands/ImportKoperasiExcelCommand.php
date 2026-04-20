<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ImportKoperasiExcelCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'koperasi:import-excel {--class=KoperasiExcelSeeder : Seeder class untuk import}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import data koperasi dari file Excel melalui seeder';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $class = (string) $this->option('class');

        if ($class === '') {
            $class = 'KoperasiExcelSeeder';
        }

        if (!class_exists($class)) {
            $this->error('Seeder class tidak ditemukan: ' . $class);
            return 1;
        }

        $this->info('Menjalankan import Excel via seeder: ' . $class);

        $exitCode = $this->call('db:seed', [
            '--class' => $class,
            '--force' => true,
        ]);

        if ((int) $exitCode !== 0) {
            $this->error('Import gagal dijalankan.');
            return (int) $exitCode;
        }

        $this->info('Import selesai.');
        return 0;
    }
}
