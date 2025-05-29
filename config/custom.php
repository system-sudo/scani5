<?php

return [
    'risk_ranges' => env('RISK_RANGES', '{}'),
    'captcha_on_off' => env('CAPTCHA_ON_OFF'),
    'frontend_url' => env('FRONT_END_URL', 'http://localhost:3000'),
    'support_email' => env('SUPPORT_EMAIL'),
    'max_paginate_limit' => env('MAX_PAGINATION_LIMIT', 30),
    'admin_organization' => env('ADMIN_ORGANIZATION', 'SQ1security'),
];
