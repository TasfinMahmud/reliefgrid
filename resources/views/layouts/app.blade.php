<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($title) ? $title.' · ' : '' }}{{ config('app.name', 'ReliefGrid') }}</title>

        @vite('resources/js/app.js')
    </head>
    <body>
        @include('layouts.navigation')

        <main class="rg-main py-4">
            <div class="container-xxl">
                @isset($header)
                    <div class="page-header">
                        {{ $header }}
                    </div>
                @endisset

                <x-flash />

                {{ $slot }}
            </div>
        </main>

        <footer class="text-center text-muted small py-3 border-top bg-white no-print">
            ReliefGrid · Humanitarian Aid Distribution and Settlement Resilience Platform · CSE470 Sprint 1
        </footer>
    </body>
</html>
