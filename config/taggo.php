<?php

return [
    'private_document_max_size_kb' => (int) env('PRIVATE_DOCUMENT_MAX_SIZE_KB', 10240),
    'security_headers' => [
        'csp' => env('SECURITY_CSP', "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data:; connect-src 'self'"),
    ],
    'roles' => ['super_admin', 'tenant_admin', 'manager', 'member', 'auditor', 'viewer'],
    'features' => ['assets', 'inventory', 'maintenance', 'depreciation', 'write_offs', 'mobile', 'api', 'electronic_signature'],
    'seed_demo_data' => (bool) env('SEED_DEMO_DATA', false),
    'demo_admin_email' => env('DEMO_ADMIN_EMAIL', 'admin@taggo.local'),
    'demo_admin_password' => env('DEMO_ADMIN_PASSWORD'),
    'asset_return_reminder_days' => (int) env('ASSET_RETURN_REMINDER_DAYS', 3),
];
