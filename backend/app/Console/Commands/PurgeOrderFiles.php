<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Privacy + storage hygiene: delete uploaded print files a set period after an
 * order reaches a terminal state (default 7 days — the configured retention).
 * Order/line records are kept; only the customer's document is purged.
 */
class PurgeOrderFiles extends Command
{
    protected $signature = 'orders:purge-files {--days=7 : Days after an order is terminal before files are deleted}';

    protected $description = 'Delete uploaded files for orders that completed/cancelled more than N days ago';

    private const TERMINAL = [
        Order::STATUS_COMPLETED,
        Order::STATUS_CANCELLED,
        Order::STATUS_REJECTED,
        Order::STATUS_REFUNDED,
        Order::STATUS_FAILED,
    ];

    public function handle(): int
    {
        $cutoff = now()->subDays((int) $this->option('days'));

        $orderIds = Order::whereIn('status', self::TERMINAL)
            ->where(function ($q) use ($cutoff) {
                $q->where('completed_at', '<=', $cutoff)
                    ->orWhere(function ($q2) use ($cutoff) {
                        $q2->whereNull('completed_at')->where('updated_at', '<=', $cutoff);
                    });
            })
            ->pluck('id');

        if ($orderIds->isEmpty()) {
            $this->info('No orders eligible for file purge.');

            return self::SUCCESS;
        }

        $disk = Storage::disk('private');
        $deleted = 0;

        OrderFile::whereHas('orderItem', fn ($q) => $q->whereIn('order_id', $orderIds))
            ->chunkById(200, function ($files) use ($disk, &$deleted) {
                foreach ($files as $file) {
                    if ($file->storage_path && $disk->exists($file->storage_path)) {
                        $disk->delete($file->storage_path);
                    }
                    if ($file->preview_path && $disk->exists($file->preview_path)) {
                        $disk->delete($file->preview_path);
                    }
                    $file->delete();
                    $deleted++;
                }
            });

        $this->info("Purged {$deleted} file(s) from ".$orderIds->count().' terminal order(s).');

        return self::SUCCESS;
    }
}
