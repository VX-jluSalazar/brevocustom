# Fase 07: control de duplicados y trazabilidad

## Estado

Implementado en:

- `modules/brevocustom/brevocustom.php`
- `modules/brevocustom/classes/Service/BrevoEventLogger.php`
- `modules/brevocustom/classes/Service/BrevoEventClient.php`

## Objetivo

Evitar envios duplicados a Brevo y dejar registro auditable de cada intento.

## Tareas

- Crear tabla local para registrar eventos enviados.
- Evitar duplicados por combinacion `event_name + object_type + object_id`.
- Permitir reintento manual o automatico si Brevo devuelve error temporal.
- Guardar payload enviado o hash del payload segun el nivel de auditoria requerido.

## Tabla sugerida

Campos:

- `id_brevocustom_event_log`
- `event_name`
- `object_type`
- `object_id`
- `email`
- `payload_hash`
- `status`
- `http_code`
- `request_payload`
- `response_body`
- `error_message`
- `date_add`
- `date_upd`

## Estados sugeridos

- `pending`
- `sent`
- `failed`
- `skipped_duplicate`
- `retry_pending`

## Criterios

- Un evento exitoso no debe enviarse dos veces para el mismo objeto.
- Los errores HTTP 400 deben quedar como fallidos no reintentables hasta corregir payload.
- Los errores temporales pueden pasar a `retry_pending`.
- Los logs no deben exponer la API key.

## Implementacion actual

- `sent` y `retry_pending` bloquean nuevos envios del mismo `event_name + object_type + object_id`.
- Cuando se detecta duplicado se registra `skipped_duplicate`.
- Errores HTTP `429`, `5xx`, timeouts o errores cURL se guardan como `retry_pending`.
- Errores de payload/credenciales quedan como `failed`.
- En modo debug apagado, el log guarda un payload minimo para reducir exposicion de datos.
