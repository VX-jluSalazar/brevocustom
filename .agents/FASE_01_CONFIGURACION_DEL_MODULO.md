# Fase 01: configuracion del modulo

## Objetivo

Crear la base tecnica del modulo `brevocustom` para enviar eventos a Brevo de forma centralizada, configurable y trazable.

## Tareas

- Crear modulo `brevocustom` con estructura PrestaShop.
- Agregar configuracion para guardar API key de Brevo.
- Agregar configuracion para activar/desactivar envio de eventos.
- Agregar modo debug/log.
- Agregar configuracion para URL base de la tienda.
- Agregar configuracion para URL de contacto WhatsApp.
- Agregar configuracion para patron de URL de recompra.
- Centralizar cliente HTTP para `POST /v3/events`.
- Registrar logs de request/respuesta sin exponer API key.

## Criterios

- Si Brevo responde error, guardar log con evento, orden/contacto y respuesta.
- No bloquear el checkout si falla el envio del evento.
- El cliente debe poder reutilizarse desde hooks, cron o comandos.

## Configuracion sugerida

- `BREVOCUSTOM_ENABLED`
- `BREVOCUSTOM_BREVO_API_KEY`
- `BREVOCUSTOM_DEBUG`
- `BREVOCUSTOM_ABANDONED_CART_DELAY_MINUTES`
- `BREVOCUSTOM_SHOP_URL`
- `BREVOCUSTOM_SHOP_REVIEW_URL`
- `BREVOCUSTOM_CONTACT_URL`
- `BREVOCUSTOM_REORDER_URL_PATTERN`

Valores iniciales sugeridos:

```text
BREVOCUSTOM_SHOP_URL=https://farmagro.desarrollovelox.com
BREVOCUSTOM_SHOP_REVIEW_URL=https://farmagro.desarrollovelox.com/shop-reviews-add
BREVOCUSTOM_CONTACT_URL=https://api.whatsapp.com/send/?phone=593959212641&text=%C2%A1Hola%21+Necesito+informaci%C3%B3n+sobre%3A+&type=phone_number&app_absent=0
BREVOCUSTOM_REORDER_URL_PATTERN=https://farmagro.desarrollovelox.com/pedido?submitReorder=1&id_order={id_order}
```

## Servicio sugerido

- `BrevoEventClient`: envia payloads a Brevo.
- `BrevoPayloadBuilder`: arma payloads por evento.
- `BrevoEventLogger`: registra intentos, respuestas y errores.
- `BrevoRelatedProductsProvider`: obtiene 3 productos relacionados.
- `BrevoShopReviewsProvider`: obtiene reviews desde `PREFIX_bt_spr_shop_reviews`.
- `BrevoServientregaProvider`: obtiene codigo de guia desde `PREFIX_order_servientrega`.
