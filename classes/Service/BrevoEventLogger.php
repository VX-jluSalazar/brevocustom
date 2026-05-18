<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class BrevoEventLogger
{
    public function hasSuccessfulLog(string $eventName, string $objectType, string $objectId): bool
    {
        $sql = 'SELECT COUNT(*)
            FROM `' . _DB_PREFIX_ . 'brevocustom_event_log`
            WHERE event_name = \'' . pSQL($eventName) . '\'
                AND object_type = \'' . pSQL($objectType) . '\'
                AND object_id = \'' . pSQL($objectId) . '\'
                AND status = \'sent\'';

        return (int) Db::getInstance()->getValue($sql) > 0;
    }

    public function hasSentOrPendingLog(string $eventName, string $objectType, string $objectId): bool
    {
        $sql = 'SELECT COUNT(*)
            FROM `' . _DB_PREFIX_ . 'brevocustom_event_log`
            WHERE event_name = \'' . pSQL($eventName) . '\'
                AND object_type = \'' . pSQL($objectType) . '\'
                AND object_id = \'' . pSQL($objectId) . '\'
                AND status IN (\'sent\', \'retry_pending\')';

        return (int) Db::getInstance()->getValue($sql) > 0;
    }

    public function logDuplicateSkip(string $eventName, string $objectType, string $objectId, ?string $email = null): bool
    {
        return $this->log(
            $eventName,
            $objectType,
            $objectId,
            $email,
            [
                'event_name' => $eventName,
                'object_type' => $objectType,
                'object_id' => $objectId,
            ],
            'skipped_duplicate',
            null,
            '',
            'Duplicate event skipped because a sent or retry-pending log already exists.'
        );
    }

    public function log(
        string $eventName,
        ?string $objectType,
        ?string $objectId,
        ?string $email,
        array $payload,
        string $status,
        ?int $httpCode = null,
        string $responseBody = '',
        string $errorMessage = ''
    ): bool {
        $payloadJson = json_encode($payload);
        if ($payloadJson === false) {
            $payloadJson = '{}';
        }

        $now = date('Y-m-d H:i:s');

        return (bool) Db::getInstance()->insert('brevocustom_event_log', [
            'event_name' => pSQL($eventName),
            'object_type' => $objectType !== null ? pSQL($objectType) : null,
            'object_id' => $objectId !== null ? pSQL($objectId) : null,
            'email' => $email !== null ? pSQL($email) : null,
            'payload_hash' => hash('sha256', $payloadJson),
            'status' => pSQL($status),
            'http_code' => $httpCode,
            'request_payload' => pSQL($payloadJson, true),
            'response_body' => pSQL($responseBody, true),
            'error_message' => pSQL($errorMessage, true),
            'date_add' => pSQL($now),
            'date_upd' => pSQL($now),
        ]);
    }
}
