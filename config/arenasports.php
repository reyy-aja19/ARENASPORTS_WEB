<?php
return [
    // Default proposal; override in .env after the platform fee is agreed.
    'venue_commission_percent' => (float) env('ARENASPORTS_VENUE_COMMISSION_PERCENT', 10),
];
