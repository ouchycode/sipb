<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\FoundItem;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use App\Services\FoundItemLifecycleService;

Artisan::command('sipb:expire-items', function (FoundItemLifecycleService $service) {
    $result = $service->syncExpiredItems();
    $this->info($result['expired'].' barang otomatis ditandai kadaluarsa.');
})->purpose('Menandai barang tersedia yang lewat 30 hari sebagai kadaluarsa.');

Schedule::command('sipb:expire-items')->dailyAt('00:10');
