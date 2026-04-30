<?php

return [
    'default' => D\spatie\browsershot\Drivers\Puppeteer::class,
    
    'drivers' => [
        D\spatie\browsershot\Drivers\Puppeteer::class => [
            'browser' => 'chrome-headless-shell',
            'node_binary' => '/usr/bin/node',  // Ajustar según tu servidor
            'npm_binary' => '/usr/bin/npm',      // Ajustar según tu servidor
            'puppeteer_binary' => null,
        ],
    ],
];
