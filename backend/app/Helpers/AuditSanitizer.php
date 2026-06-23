<?php

namespace App\Helpers;

class AuditSanitizer
{
    private const SENSITIVE_FIELDS = [
        'account_number',
        'tin',
        'sss',
        'philhealth',
        'pagibig',
        'security_pin',
        'password',
    ];

    /**
     * Mask sensitive field values in an array before audit logging.
     */
    public static function sanitize(?array $data): ?array
    {
        if (!$data) {
            return $data;
        }

        foreach (self::SENSITIVE_FIELDS as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $val = $data[$field];
                $data[$field] = strlen($val) > 4
                    ? str_repeat('*', strlen($val) - 4) . substr($val, -4)
                    : '[REDACTED]';
            }
        }

        return $data;
    }
}
