# Fase 04: evento `vx_order_sent`

## Estado

Implementado en `modules/brevocustom/brevocustom.php` mediante el hook `actionOrderStatusPostUpdate`, filtrando el estado PrestaShop `4` - Enviado.

Nota: el nombre final del evento se corrige a `vx_order_sent`.

## Disparador sugerido

- Cuando la orden cambia al estado de enviada/despachada.
- Hook candidato: `actionOrderStatusPostUpdate`.
- Estado PrestaShop: `4` - Enviado.

## Uso

- Notificacion de envio.
- Envio de codigo de guia.
- Cross-sell mientras el pedido esta en camino.

## Payload recomendado

```json
{
  "event_name": "vx_order_sent",
  "event_date": "2026-05-18T11:30:00-05:00",
  "identifiers": {
    "email_id": "cliente@example.com",
    "ext_id": "ps_customer_123"
  },
  "contact_properties": {
    "FIRSTNAME": "Nombre",
    "LASTNAME": "Apellido",
    "PS_CUSTOMER_ID": 123,
    "LAST_SENT_ORDER_ID": 456,
    "LAST_SENT_ORDER_DATE": "2026-05-18T11:30:00-05:00"
  },
  "event_properties": {
    "order_id": 456,
    "order_reference": "ABCDEF",
    "order_status": "Enviado",
    "order_status_id": 4,
    "shop_url": "https://farmagro.desarrollovelox.com",
    "currency": "USD",
    "total_paid": 89.5,
    "carrier_name": "Servientrega",
    "tracking_code": "GUIA-123456789",
    "tracking_url": "https://www.servientrega.com.ec/rastreo",
    "servientrega": {
      "id_order_servientrega": "GUIA-123456789",
      "pedido": "ABCDEF",
      "estado": 1,
      "fecha": "2026-05-18",
      "total": "89.50",
      "rastreoEnvio": "https://www.servientrega.com.ec/rastreo",
      "razon": "",
      "city": "Quito"
    },
    "sent_at": "2026-05-18T11:30:00-05:00",
    "products": [],
    "related_products": [],
    "shop_reviews": [
      {
        "id_review": 1001,
        "id_order": 456,
        "id_customer": 123,
        "id_lang": 1,
        "title": "Calidad y comodidad",
        "rating": 5,
        "summary": "Un calzado muy versatil y comodo, a mi hija le encanta.",
        "review_status": 1,
        "id_shop": 1,
        "created_at": "2024-04-02T00:00:00-05:00",
        "updated_at": "2024-04-02T00:00:00-05:00"
      }
    ],
    "shop_review_url": "https://farmagro.desarrollovelox.com/shop-reviews-add",
    "reorder_url": "https://farmagro.desarrollovelox.com/pedido?submitReorder=1&id_order=456",
    "contact_url": "https://api.whatsapp.com/send/?phone=593959212641&text=%C2%A1Hola%21+Necesito+informaci%C3%B3n+sobre%3A+&type=phone_number&app_absent=0"
  },
  "object": {
    "type": "order",
    "identifiers": {
      "ext_id": "ps_order_456"
    }
  }
}
```

## Guia Servientrega

La guia se obtiene desde la tabla:

```sql
CREATE TABLE IF NOT EXISTS `PREFIX_order_servientrega` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_order_servientrega` VARCHAR(30) NULL DEFAULT NULL,
  `pedido` VARCHAR(30) NULL DEFAULT NULL,
  `id_order` int(100) NULL DEFAULT NULL,
  `fecha` DATE NULL DEFAULT NULL,
  `estado` int(11) NULL DEFAULT NULL,
  `total` VARCHAR(10) NULL DEFAULT NULL,
  `rastreoEnvio` TEXT NULL DEFAULT NULL,
  `razon` TEXT NULL DEFAULT NULL,
  `city` VARCHAR(30) NULL DEFAULT NULL,
  `date_add` DATETIME NOT NULL,
  `date_upd` DATETIME NOT NULL,
  PRIMARY KEY (`id`)
);
```

Consulta sugerida por orden:

```sql
SELECT
  id_order_servientrega,
  pedido,
  id_order,
  fecha,
  estado,
  total,
  rastreoEnvio,
  razon,
  city
FROM PREFIX_order_servientrega
WHERE id_order = {id_order}
ORDER BY date_add DESC
LIMIT 1;
```

Mapeo recomendado:

- `tracking_code`: `id_order_servientrega`.
- `tracking_url`: `rastreoEnvio` si contiene una URL usable; si no, usar URL base de rastreo Servientrega.
- `servientrega.id_order_servientrega`: codigo de guia.
- `servientrega.pedido`: referencia/pedido registrado por Servientrega.
- `servientrega.estado`: estado interno guardado en la tabla.
- `servientrega.fecha`: fecha de la guia.
- `servientrega.total`: total registrado por Servientrega.
- `servientrega.rastreoEnvio`: dato original de rastreo.
- `servientrega.razon`: mensaje o razon devuelta por Servientrega.
- `servientrega.city`: ciudad registrada.
