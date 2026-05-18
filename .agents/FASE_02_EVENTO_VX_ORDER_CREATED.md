# Fase 02: evento `vx_order_created`

## Estado

Implementado en `modules/brevocustom/brevocustom.php` mediante el hook `actionValidateOrder`.

## Disparador sugerido

- Cuando se confirma una orden en PrestaShop.
- Hook candidato: `actionValidateOrder`.

## Uso

- Confirmacion de compra.
- Recomendaciones post-compra.
- Solicitud futura de review.
- Segmentacion por categorias/productos comprados.

## Payload recomendado

```json
{
  "event_name": "vx_order_created",
  "event_date": "2026-05-18T11:30:00-05:00",
  "identifiers": {
    "email_id": "cliente@example.com",
    "ext_id": "ps_customer_123"
  },
  "contact_properties": {
    "FIRSTNAME": "Nombre",
    "LASTNAME": "Apellido",
    "PS_CUSTOMER_ID": 123
  },
  "event_properties": {
    "order_id": 456,
    "order_reference": "ABCDEF",
    "order_status": "Pago aceptado",
    "order_status_id": 2,
    "shop_url": "https://farmagro.desarrollovelox.com",
    "currency": "USD",
    "total_paid": 89.5,
    "total_products": 75.0,
    "total_shipping": 4.5,
    "total_discounts": 0,
    "payment_method": "Transferencia bancaria",
    "carrier_name": "Servientrega",
    "customer_email": "cliente@example.com",
    "customer_firstname": "Nombre",
    "customer_lastname": "Apellido",
    "shipping_address": {
      "firstname": "Nombre",
      "lastname": "Apellido",
      "address1": "Direccion principal",
      "address2": "Referencia",
      "city": "Quito",
      "state": "Pichincha",
      "postcode": "170000",
      "country": "Ecuador",
      "phone": "+593999999999"
    },
    "products": [
      {
        "id_product": 101,
        "id_product_attribute": 0,
        "reference": "SKU-101",
        "name": "Producto comprado",
        "category_id": 12,
        "category_name": "Categoria",
        "quantity": 2,
        "unit_price": 20.0,
        "total_price": 40.0,
        "product_url": "https://farmagro.desarrollovelox.com/producto",
        "image_url": "https://farmagro.desarrollovelox.com/img/p/...",
        "is_variant": true,
        "attributes": [
          {
            "label": "Color",
            "value": "Rojo"
          },
          {
            "label": "Talla",
            "value": "40"
          }
        ]
      }
    ],
    "related_products": [
      {
        "id_product": 321,
        "name": "Producto relacionado 1",
        "category_id": 12,
        "category_name": "Categoria",
        "price": 19.99,
        "currency": "USD",
        "product_url": "https://farmagro.desarrollovelox.com/producto-relacionado-1",
        "image_url": "https://farmagro.desarrollovelox.com/img/p/..."
      },
      {
        "id_product": 322,
        "name": "Producto relacionado 2",
        "category_id": 12,
        "category_name": "Categoria",
        "price": 24.99,
        "currency": "USD",
        "product_url": "https://farmagro.desarrollovelox.com/producto-relacionado-2",
        "image_url": "https://farmagro.desarrollovelox.com/img/p/..."
      },
      {
        "id_product": 323,
        "name": "Producto relacionado 3",
        "category_id": 12,
        "category_name": "Categoria",
        "price": 14.99,
        "currency": "USD",
        "product_url": "https://farmagro.desarrollovelox.com/producto-relacionado-3",
        "image_url": "https://farmagro.desarrollovelox.com/img/p/..."
      }
    ],
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

## Pendientes

- Confirmar URL final de recompra. -> Este formato https://farmagro.desarrollovelox.com/pedido?submitReorder=1&id_order=456
- Confirmar campos finales de direccion que se quieren exponer en Brevo. -> estos son los campos de las direcciones seteados en la tienda Internacional -> Country -> Address
firstname lastname
dni 
address1
address2 
Country:name
State:name 
city 
phone

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

Mapeo:

- `title`: `title_review`.
- `summary`: `text_review`.
- `rating`: `rating_value`.
- `created_at`: `date_add`.
- `updated_at`: `date_upd`.
