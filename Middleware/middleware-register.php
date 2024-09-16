<?php
return [
    'global'=>[
        new \Middleware\SessionsSetupMiddleware(),
        new \Middleware\MiddlewareA(),
        new \Middleware\MiddlewareB(),
        new \Middleware\MiddlewareC(),
        \Middleware\CSRFMiddleware::class,
    ],
    'aliases'=>[
        'auth'=>\Middleware\AuthenticatedMiddleware::class,
        'guest'=>\Middleware\GuestMiddleware::class,
        'signature'=>\Middleware\SignatureValidationMiddleware::class,
    ]
];
