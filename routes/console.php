<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('logistica:actualizar-status')->hourly();
Schedule::command('rh:generar-recordatorios')->daily();
Schedule::command('proyectos:recordatorios')->dailyAt('08:00');
Schedule::command('it:notificar-proximo-mantenimiento')->dailyAt('08:00');
