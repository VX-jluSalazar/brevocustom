<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class BrevoEventClient
{
    private const ENDPOINT = 'https://api.brevo.com/v3/events';

    private $apiKey;
    private $logger;
    private $debug;

    public function __construct(string $apiKey, BrevoEventLogger $logger, bool $debug = false)
    {
        $this->apiKey = $apiKey;
        $this->logger = $logger;
        $this->debug = $debug;
    }

    public function send(array $payload): array
    {
        $eventName = isset($payload['event_name']) ? (string) $payload['event_name'] : 'unknown';
        $email = $payload['identifiers']['email_id'] ?? null;
        $objectType = $payload['_log_object_type'] ?? null;
        $objectId = $payload['_log_object_id'] ?? null;
        $requestPayload = $this->payloadForRequest($payload);

        if ($this->apiKey === '') {
            $this->logger->log($eventName, $objectType, $objectId, $email, $requestPayload, 'failed', null, '', 'Missing Brevo API key');

            return [
                'success' => false,
                'http_code' => null,
                'response' => '',
                'error' => 'Missing Brevo API key',
            ];
        }

        $body = json_encode($requestPayload);
        if ($body === false) {
            $error = 'Unable to encode Brevo payload as JSON';
            $this->logger->log($eventName, $objectType, $objectId, $email, $requestPayload, 'failed', null, '', $error);

            return [
                'success' => false,
                'http_code' => null,
                'response' => '',
                'error' => $error,
            ];
        }

        if (!function_exists('curl_init')) {
            $error = 'PHP cURL extension is not available';
            $this->logger->log($eventName, $objectType, $objectId, $email, $requestPayload, 'failed', null, '', $error);

            return [
                'success' => false,
                'http_code' => null,
                'response' => '',
                'error' => $error,
            ];
        }

        $ch = curl_init(self::ENDPOINT);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 12);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'api-key: ' . $this->apiKey,
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $success = $error === '' && $httpCode >= 200 && $httpCode < 300;
        $status = $success ? 'sent' : 'failed';
        if (!$success && ($error !== '' || $httpCode === 0 || $httpCode >= 500 || $httpCode === 429)) {
            $status = 'retry_pending';
        }

        $this->logger->log(
            $eventName,
            $objectType,
            $objectId,
            $email,
            $this->debug ? $requestPayload : $this->minimalPayloadForLog($requestPayload),
            $status,
            $httpCode ?: null,
            is_string($response) ? $response : '',
            $error
        );

        return [
            'success' => $success,
            'http_code' => $httpCode ?: null,
            'response' => is_string($response) ? $response : '',
            'error' => $error,
        ];
    }

    private function minimalPayloadForLog(array $payload): array
    {
        return [
            'event_name' => $payload['event_name'] ?? null,
            'event_date' => $payload['event_date'] ?? null,
            'identifiers' => $payload['identifiers'] ?? [],
        ];
    }

    private function payloadForRequest(array $payload): array
    {
        unset($payload['_log_object_type'], $payload['_log_object_id']);

        return $payload;
    }
}
