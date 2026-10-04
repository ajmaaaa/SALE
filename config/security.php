<?php

return [
    // Staging can opt into report-only while production remains enforcing.
    'csp_report_only' => (bool) env('CSP_REPORT_ONLY', false),
];
