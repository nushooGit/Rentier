<?php

return [
    'admin_url' => env('RENTIER_ADMIN_URL', 'http://admin.localhost'),

    'platform_admin_emails' => array_values(array_filter(array_map(
        static fn (string $email): string => strtolower(trim($email)),
        explode(',', (string) env('RENTIER_PLATFORM_ADMIN_EMAILS', '')),
    ))),
];
