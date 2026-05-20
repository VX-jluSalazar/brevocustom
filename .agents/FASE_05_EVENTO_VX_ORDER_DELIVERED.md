# Fase 05: evento `vx_order_delivered`

## Estado

Implementado en `modules/brevocustom/brevocustom.php` mediante el hook `actionOrderStatusPostUpdate`, filtrando el estado PrestaShop `5` - Entregado.

## Disparador sugerido

- Cuando la orden cambia al estado entregado.
- Hook candidato: `actionOrderStatusPostUpdate`.
- Estado PrestaShop: `5` - Entregado.

## Uso

- Solicitud de review de tienda.
- Recompra.
- Recomendaciones relacionadas.

## Payload recomendado

Nota: el payload implementado actualmente usa la estructura agrupada documentada en `PAYLOADS_EVENTOS.md` (`customer`, `order.items`, `shipping.carrier`, `payment`, `reviews`, `main_categories`, `misc`). El ejemplo plano siguiente queda como referencia historica y compatibilidad temporal.

```json
{
  "event_name": "vx_order_delivered",
  "event_date": "2026-05-18T11:30:00-05:00",
  "identifiers": {
    "email_id": "cliente@example.com",
    "ext_id": "ps_customer_123"
  },
  "contact_properties": {
    "FIRSTNAME": "Nombre",
    "LASTNAME": "Apellido",
    "PS_CUSTOMER_ID": 123,
    "LAST_DELIVERED_ORDER_ID": 456,
    "LAST_DELIVERED_ORDER_DATE": "2026-05-18T11:30:00-05:00"
  },
  "event_properties": {
    "order_id": 456,
    "order_reference": "ABCDEF",
    "order_status": "Entregado",
    "order_status_id": 5,
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
    "delivered_at": "2026-05-18T11:30:00-05:00",
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

## Reviews de tienda

Las reviews se obtienen desde `PREFIX_bt_spr_shop_reviews`.

Consulta sugerida:

```sql
SELECT
  id_review,
  id_order,
  id_customer,
  id_lang,
  title_review,
  text_review,
  rating_value,
  review_status,
  id_shop,
  date_add,
  date_upd
FROM PREFIX_bt_spr_shop_reviews
WHERE review_status = 1
  AND id_shop = {id_shop}
ORDER BY date_add DESC
LIMIT 5;
```

## Guia Servientrega

La guia se obtiene desde `PREFIX_order_servientrega` filtrando por `id_order`.

Consulta sugerida:

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
- `servientrega`: objeto con los campos originales necesarios para personalizar workflows en Brevo.
