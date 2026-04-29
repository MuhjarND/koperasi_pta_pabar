<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RefreshKoperasiRekapCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'koperasi:refresh-rekap
                            {--class=KoperasiRekapSeeder : Seeder class untuk import rekap}
                            {--keep-balance : Jangan kosongkan tabel koperasi_balances}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bersihkan data transaksi rekap koperasi lalu import ulang dari workbook sumber';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $class = (string) $this->option('class');
        if ($class === '') {
            $class = 'KoperasiRekapSeeder';
        }

        if (!class_exists($class)) {
            $this->error('Seeder class tidak ditemukan: ' . $class);
            return 1;
        }

        $tables = [
            'loan_installment_payments',
            'loans',
            'deduction_logs',
            'savings_transactions',
            'cash_entries',
        ];

        if (!$this->option('keep-balance')) {
            $tables[] = 'koperasi_balances';
        }

        $deleted = [];

        DB::beginTransaction();

        try {
            foreach ($tables as $table) {
                $deleted[$table] = DB::table($table)->count();
                DB::table($table)->delete();
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Gagal membersihkan data transaksi: ' . $e->getMessage());
            return 1;
        }

        $this->info('Data transaksi rekap dibersihkan.');
        foreach ($deleted as $table => $count) {
            $this->line(sprintf('- %s: %d baris', $table, $count));
        }

        $this->info('Menjalankan import ulang via seeder: ' . $class);

        $exitCode = $this->call('db:seed', [
            '--class' => $class,
            '--force' => true,
        ]);

        if ((int) $exitCode !== 0) {
            $this->error('Import ulang gagal dijalankan.');
            return (int) $exitCode;
        }

        $this->info('Refresh rekap selesai.');

        return 0;
    }
}
