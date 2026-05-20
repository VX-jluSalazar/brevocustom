# Payloads actuales de eventos Brevo

Documento de referencia para los payloads que `modules/brevocustom` envia a `POST /v3/events`.

## Reglas comunes

Todos los eventos enviados a Brevo usan:

```json
{
  "event_name": "nombre_del_evento",
  "event_date": "2026-05-20T10:30:00-05:00",
  "identifiers": {
    "email_id": "cliente@dominio.com",
    "ext_id": "ps_customer_123"
  },
  "contact_properties": {},
  "event_properties": {
    "customer": {},
    "order": {},
    "cart": {},
    "shipping": {
      "carrier": {}
    },
    "payment": {},
    "reviews": [],
    "main_categories": [],
    "misc": {}
  }
}
```

Notas:

- No se envia `object` a Brevo.
- Los campos internos `_log_object_type` y `_log_object_id` solo se usan para trazabilidad local y el cliente HTTP los elimina antes del request.
- Los eventos de orden y carrito mantienen algunos campos planos como compatibilidad temporal: `order_id`, `products`, `shop_url`, `contact_url`, etc.
- Las URLs custom configuradas desde Back Office se agregan directamente como atributos planos dentro de `misc`.

## vx_order_created

Se dispara cuando se crea una orden.

`event_properties` agrupado:

```json
{
  "customer": {
    "id": 123,
    "email": "cliente@dominio.com",
    "firstname": "Juan",
    "lastname": "Perez",
    "is_customer": true
  },
  "order": {
    "id": 456,
    "reference": "ABC123",
    "date": "2026-05-20T10:30:00-05:00",
    "date_formatted": "20 mayo, 2026",
    "status": "Pago aceptado",
    "status_id": 2,
    "currency": "USD",
    "totals": {
      "paid": 35.5,
      "paid_tax_incl": 35.5,
      "paid_tax_excl": 31.7,
      "tax_amount": 3.8,
      "products": 28.0,
      "products_tax_incl": 31.36,
      "products_tax_excl": 28.0,
      "products_tax_amount": 3.36,
      "shipping": 4.14,
      "shipping_tax_incl": 4.14,
      "shipping_tax_excl": 3.7,
      "shipping_tax_amount": 0.44,
      "discounts": 0.0,
      "discounts_tax_incl": 0.0,
      "discounts_tax_excl": 0.0,
      "discounts_tax_amount": 0.0
    },
    "items": []
  },
  "shipping": {
    "address": {},
    "carrier": {
      "id": 4,
      "reference_id": 2,
      "name": "Servientrega"
    },
    "tracking_code": "",
    "tracking_url": "",
    "servientrega": []
  },
  "payment": {
    "method_id": 12,
    "method": "Transferencia bancaria",
    "module": "ps_wirepayment"
  },
  "reviews": [],
  "main_categories": [],
  "misc": {
    "shop_url": "https://tienda.com",
    "shop_review_url": "https://tienda.com/shop-reviews-add",
    "reorder_url": "https://tienda.com/reorder/456",
    "contact_url": "https://tienda.com/contacto",
    "faq_url": "https://tienda.com/faq"
  }
}
```

## vx_order_sent

Se dispara cuando la orden cambia al estado configurado como enviada.

Usa la misma estructura agrupada de `vx_order_created`, con estos campos adicionales:

```json
{
  "order": {
    "sent_at": "2026-05-20T10:30:00-05:00"
  },
  "shipping": {
    "tracking_code": "GUIA-123456789",
    "tracking_url": "https://www.servientrega.com.ec/rastreo",
    "servientrega": {}
  }
}
```

`contact_properties` agrega:

```json
{
  "LAST_SENT_ORDER_ID": 456,
  "LAST_SENT_ORDER_DATE": "2026-05-20T10:30:00-05:00"
}
```

## vx_order_delivered

Se dispara cuando la orden cambia al estado configurado como entregada.

Usa la misma estructura agrupada de `vx_order_sent`, con:

```json
{
  "order": {
    "delivered_at": "2026-05-20T10:30:00-05:00"
  }
}
```

`contact_properties` agrega:

```json
{
  "LAST_DELIVERED_ORDER_ID": 456,
  "LAST_DELIVERED_ORDER_DATE": "2026-05-20T10:30:00-05:00"
}
```

## vx_subscriber

Se dispara cuando un email se suscribe al newsletter.

```json
{
  "customer": {
    "id": 123,
    "email": "cliente@dominio.com",
    "firstname": "Juan",
    "lastname": "Perez",
    "is_customer": true
  },
  "reviews": [],
  "main_categories": [],
  "misc": {
    "shop_url": "https://tienda.com",
    "contact_url": "https://tienda.com/contacto",
    "faq_url": "https://tienda.com/faq"
  }
}
```

`contact_properties`:

```json
{
  "EMAIL": "cliente@dominio.com",
  "FIRSTNAME": "Juan",
  "LASTNAME": "Perez",
  "NEWSLETTER": true,
  "SUBSCRIBED_AT": "2026-05-20T10:30:00-05:00",
  "SUBSCRIPTION_SOURCE": "ps_emailsubscription"
}
```

## vx_abandoned_cart

Se dispara desde el cron de carritos abandonados.

```json
{
  "customer": {
    "id": 123,
    "email": "cliente@dominio.com",
    "firstname": "Juan",
    "lastname": "Perez",
    "is_customer": true
  },
  "cart": {
    "id": 789,
    "updated_at": "2026-05-20T10:30:00-05:00",
    "abandoned_minutes": 60,
    "url": "https://tienda.com/cart?action=show",
    "totals": {
      "total": 35.5,
      "total_tax_incl": 35.5,
      "total_tax_excl": 31.7,
      "total_tax_amount": 3.8,
      "products": 31.36,
      "products_tax_incl": 31.36,
      "products_tax_excl": 28.0,
      "products_tax_amount": 3.36,
      "shipping": 4.14,
      "shipping_tax_incl": 4.14,
      "shipping_tax_excl": 3.7,
      "shipping_tax_amount": 0.44,
      "discounts": 0.0,
      "discounts_tax_incl": 0.0,
      "discounts_tax_excl": 0.0,
      "discounts_tax_amount": 0.0
    },
    "items": []
  },
  "shipping": {
    "carrier": {}
  },
  "payment": {},
  "reviews": [],
  "main_categories": [],
  "misc": {
    "shop_url": "https://tienda.com",
    "cart_url": "https://tienda.com/cart?action=show",
    "contact_url": "https://tienda.com/contacto",
    "faq_url": "https://tienda.com/faq"
  }
}
```

## Estructuras reutilizadas

### order.items

```json
{
  "id_product": 10,
  "id_product_attribute": 0,
  "reference": "SKU-10",
  "name": "Producto",
  "category_id": 3,
  "category_name": "Categoria",
  "quantity": 2,
  "unit_price": 11.2,
  "unit_price_tax_incl": 11.2,
  "unit_price_tax_excl": 10.0,
  "unit_price_tax_amount": 1.2,
  "total_price": 22.4,
  "total_price_tax_incl": 22.4,
  "total_price_tax_excl": 20.0,
  "total_price_tax_amount": 2.4,
  "tax_rate": 12.0,
  "tax_name": "IVA",
  "product_url": "https://tienda.com/producto",
  "image_url": "https://tienda.com/img/p/...",
  "is_variant": false,
  "attributes": []
}
```

### cart.items

Igual que `order.items`, excepto que no incluye `is_variant`. Siempre incluye `attributes`.

### shipping.address

```json
{
  "firstname": "Juan",
  "lastname": "Perez",
  "dni": "0102030405",
  "address1": "Calle 1",
  "address2": "",
  "city": "Quito",
  "state": "Pichincha",
  "country": "Ecuador",
  "phone": "0999999999"
}
```

### reviews

```json
{
  "id_review": 1,
  "id_order": 456,
  "id_customer": 123,
  "customer_firstname": "Juan",
  "customer_lastname": "Perez",
  "customer_name": "Juan Perez",
  "customer_initials": "JP",
  "id_lang": 1,
  "title": "Excelente",
  "summary": "Muy buen servicio",
  "rating": 5,
  "review_status": 1,
  "id_shop": 1,
  "created_at": "2026-05-20 10:30:00",
  "updated_at": "2026-05-20 10:30:00"
}
```

### main_categories

```json
{
  "id_category": 3,
  "name": "Categoria",
  "description": "Descripcion sin HTML",
  "category_url": "https://tienda.com/categoria",
  "image_url": "https://tienda.com/img/c/3.jpg"
}
```
