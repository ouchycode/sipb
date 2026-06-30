<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use App\Services\FoundItemLifecycleService;

Artisan::command('sipb:expire-items', function (FoundItemLifecycleService $service) {
    $result = $service->syncExpiredItems();
    $this->info($result['expired'].' barang otomatis dipindahkan ke history (kadaluarsa).');
})->purpose('Memindahkan barang tersedia yang lewat 30 hari ke history.');

Schedule::command('sipb:expire-items')->dailyAt('00:10');
