<?php

namespace App\Jobs;

use App\Models\Annonce;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class UpdateVipAnnoncesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        DB::statement("
            UPDATE annonces
            JOIN payments ON annonces.id = payments.annonce_id
            SET annonces.type = 'normal'
            WHERE annonces.type = 'vip'
            AND DATE_ADD(payments.created_at, INTERVAL payments.annonce_duration DAY) <= NOW()
        ");

        \Log::info('Expired VIP annonces have been updated to normal.');
    }
}
