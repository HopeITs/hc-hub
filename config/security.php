<?php

return [
    // Temporary deployment escape hatch only. Production reads should fail
    // closed once security:encrypt-pii has completed and been verified.
    'allow_plaintext_pii_reads' => env('PII_MIGRATION_ALLOW_PLAINTEXT_READS', false),
];
