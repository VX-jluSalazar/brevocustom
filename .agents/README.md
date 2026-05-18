# Documentacion tecnica de brevocustom

Esta carpeta contiene la documentacion de trabajo para crear el modulo `brevocustom` e integrar eventos personalizados con Brevo.

## Archivos

- `00_oficial_documentation.md`: referencia oficial resumida del endpoint Brevo `POST /v3/events`.
- `01_brevo_events_plan.md`: indice general, reglas comunes y datos pendientes.
- `FASE_01_CONFIGURACION_DEL_MODULO.md`: base tecnica del modulo y configuracion.
- `FASE_02_EVENTO_VX_ORDER_CREATED.md`: payload y criterio para orden creada.
- `FASE_03_EVENTO_VX_SUBSCRIBER.md`: payload y criterio para suscriptor.
- `FASE_04_EVENTO_VX_ORDER_SENT.md`: payload y criterio para orden enviada.
- `FASE_05_EVENTO_VX_ORDER_DELIVERED.md`: payload y criterio para orden entregada.
- `FASE_06_EVENTO_VX_ABANDONED_CART.md`: payload y criterio para carrito abandonado.
- `FASE_07_CONTROL_DUPLICADOS_TRAZABILIDAD.md`: registro local, idempotencia y reintentos.
- `FASE_08_VALIDACION.md`: checklist de pruebas y validacion.

## Eventos planificados

- `vx_order_created`
- `vx_subscriber`
- `vx_order_sent`
- `vx_order_delivered`
- `vx_abandoned_cart`

## Pendiente de definicion
