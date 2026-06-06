<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment('Stay precise.');
})->purpose('Display a pragmatic message');

Schedule::command('invoices:lock-expired')->hourly();
