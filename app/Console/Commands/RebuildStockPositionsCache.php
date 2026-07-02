<?php

namespace App\Console\Commands;

use App\Services\Stock\StockPositionService;
use Illuminate\Console\Command;

class RebuildStockPositionsCache extends Command
{
    protected $signature = 'cache:rebuild-stock-positions
                            {--dry-run : Preview saja, tidak menyimpan perubahan}';

    protected $description = 'Rebuild cache stock_positions dari ledger inventory_transactions';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->info('==== DRY RUN: Tidak ada data yang diubah ====');
        } else {
            $this->info('==== REBUILDING: Merekonstruksi stock_positions cache ====');
        }
        $this->newLine();

        if (!$isDryRun) {
            $service = new StockPositionService();
            $service->rebuildCache();
            $this->info('✅ Cache stock_positions berhasil direkonstruksi dari ledger');
        } else {
            $this->warn('Ini adalah DRY RUN. Jalankan tanpa --dry-run untuk menyimpan perubahan.');
        }

        return Command::SUCCESS;
    }
}
