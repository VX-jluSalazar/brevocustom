# Fase 08: validacion

## Objetivo

Confirmar que los eventos se aceptan en Brevo, que el modulo no rompe flujos criticos de PrestaShop y que los payloads contienen datos utiles.

## Checklist

- Enviar payload minimo a Brevo sandbox/cuenta de prueba.
- Validar que Brevo acepte cada `event_name`.
- Validar que workflows de Brevo puedan leer propiedades anidadas.
- Confirmar que `identifiers.email_id` siempre exista.
- Confirmar que URLs abren correctamente.
- Confirmar que productos relacionados no incluyen productos comprados.
- Confirmar que reviews y guia Servientrega se llenan desde sus tablas.
- Confirmar que el checkout no falla aunque Brevo este caido.

## Pruebas manuales sugeridas

- Crear orden nueva y verificar `vx_order_created`.
- Suscribir un email y verificar `vx_subscriber`.
- Cambiar una orden a enviada y verificar `vx_order_sent`.
- Cambiar una orden a entregada y verificar `vx_order_delivered`.
- Crear carrito abandonado de prueba y verificar `vx_abandoned_cart`.

## Pruebas tecnicas sugeridas

- Validar payload JSON antes de enviarlo.
- Mockear cliente HTTP de Brevo para pruebas unitarias.
- Probar respuesta exitosa, error 400, error 401 y timeout.
- Confirmar idempotencia del registro local de eventos.
