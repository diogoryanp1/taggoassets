<?php

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('taggo:demo-setup {--fresh : Recria o banco antes de semear os dados de demonstracao}', function () {
    if (app()->environment('production')) {
        $this->error('Comando bloqueado em producao.');

        return 1;
    }

    if ($this->option('fresh')) {
        $this->call('migrate:fresh', ['--force' => true]);
    } else {
        $this->call('migrate', ['--force' => true]);
    }

    $this->call('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true]);
    $this->call('optimize:clear');
    $this->info('Ambiente demo pronto.');
    $this->line('URL sugerida pelo Artisan: http://127.0.0.1:8000');
    $this->line('E-mail administrativo: '.config('taggo.demo_admin_email', 'admin@taggo.local'));
    $this->line('Senha: defina DEMO_ADMIN_PASSWORD no ambiente ou use a senha temporaria impressa pelo seeder local.');

    return 0;
})->purpose('Prepara dados locais de demonstracao do Taggo Assets');
