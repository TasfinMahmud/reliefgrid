<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'ReliefGrid') }}</title>

       @vite('resources/js/app.js')
    </head>
    <body>
        <div class="auth-wrapper d-flex flex-column align-items-center justify-content-center p-3">
            <a href="/" class="text-decoration-none text-white mb-4 text-center">
                <div class="fs-2 fw-bold">ReliefGrid</div>
                <div class="small opacity-75">Humanitarian Aid Distribution &amp; Settlement Resilience</div>
            </a>

            <div class="card auth-card shadow-lg border-0">
                <div class="card-body p-4">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
