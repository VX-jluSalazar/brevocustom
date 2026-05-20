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
  "event_properties": {}
}
```

Notas:

- No se envia `object` a Brevo.
- Los campos internos `_log_object_type` y `_log_object_id` solo se usan para trazabilidad local y el cliente HTTP los elimina antes del request.
- Las fechas ISO usan `DATE_ATOM`.
- Los totales historicos (`total_paid`, `cart_total`, `unit_price`, `total_price`) se mantienen con impuestos incluidos para no romper plantillas existentes.

## vx_order_created

Se dispara cuando se crea una orden.

`identifiers`:

```json
{
  "email_id": "cliente@dominio.com",
  "ext_id": "ps_customer_123"
}
```

`contact_properties`:

```json
{
  "FIRSTNAME": "Juan",
  "LASTNAME": "Perez",
  "PS_CUSTOMER_ID": 123
}
```

`event_properties`:

```json
{
  "order_id": 456,
  "order_reference": "ABC123",
  "order_date": "2026-05-20T10:30:00-05:00",
  "order_date_formatted": "20 mayo, 2026",
  "order_status": "Pago aceptado",
  "order_status_id": 2,
  "shop_url": "https://tienda.com",
  "currency": "USD",
  "total_paid": 35.5,
  "total_paid_tax_incl": 35.5,
  "total_paid_tax_excl": 31.7,
  "total_tax_amount": 3.8,
  "total_products": 28.0,
  "total_products_tax_incl": 31.36,
  "total_products_tax_excl": 28.0,
  "total_products_tax_amount": 3.36,
  "total_shipping": 4.14,
  "total_shipping_tax_incl": 4.14,
  "total_shipping_tax_excl": 3.7,
  "total_shipping_tax_amount": 0.44,
  "total_discounts": 0.0,
  "total_discounts_tax_incl": 0.0,
  "total_discounts_tax_excl": 0.0,
  "total_discounts_tax_amount": 0.0,
  "payment_method_id": 12,
  "payment_method": "Transferencia bancaria",
  "payment_module": "ps_wirepayment",
  "carrier_id": 4,
  "carrier_reference_id": 2,
  "carrier_name": "Servientrega",
  "customer_email": "cliente@dominio.com",
  "customer_firstname": "Juan",
  "customer_lastname": "Perez",
  "shipping_address": {},
  "products": [],
  "related_products": [],
  "shop_reviews": [],
  "shop_review_url": "https://tienda.com/shop-reviews-add",
  "reorder_url": "https://tienda.com/...",
  "contact_url": "https://tienda.com/contacto"
}
```

## vx_order_sent

Se dispara cuando la orden cambia al estado configurado como enviada.

Tiene la misma base de `vx_order_created`, pero agrega datos de tracking y fecha de envio:

```json
{
  "tracking_code": "GUIA-123456789",
  "tracking_url": "https://www.servientrega.com.ec/rastreo",
  "servientrega": {},
  "sent_at": "2026-05-20T10:30:00-05:00"
}
```

`contact_properties` tambien agrega:

```json
{
  "LAST_SENT_ORDER_ID": 456,
  "LAST_SENT_ORDER_DATE": "2026-05-20T10:30:00-05:00"
}
```

## vx_order_delivered

Se dispara cuando la orden cambia al estado configurado como entregada.

Tiene la misma base de `vx_order_sent`, pero la fecha dinamica es:

```json
{
  "delivered_at": "2026-05-20T10:30:00-05:00"
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

`identifiers`:

```json
{
  "email_id": "cliente@dominio.com",
  "ext_id": "newsletter_cliente_dominio_com"
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

`event_properties`:

```json
{
  "email": "cliente@dominio.com",
  "customer_id": 123,
  "is_customer": true,
  "shop_url": "https://tienda.com",
  "contact_url": "https://tienda.com/contacto",
  "shop_reviews": [],
  "main_categories": []
}
```

## vx_abandoned_cart

Se dispara desde el cron de carritos abandonados.

`contact_properties`:

```json
{
  "FIRSTNAME": "Juan",
  "LASTNAME": "Perez",
  "PS_CUSTOMER_ID": 123,
  "LAST_ABANDONED_CART_ID": 789,
  "LAST_ABANDONED_CART_DATE": "2026-05-20T10:30:00-05:00"
}
```

`event_properties`:

```json
{
  "cart_id": 789,
  "customer_id": 123,
  "customer_email": "cliente@dominio.com",
  "shop_url": "https://tienda.com",
  "currency": "USD",
  "cart_total": 35.5,
  "cart_total_tax_incl": 35.5,
  "cart_total_tax_excl": 31.7,
  "cart_total_tax_amount": 3.8,
  "cart_products_total": 31.36,
  "cart_products_total_tax_incl": 31.36,
  "cart_products_total_tax_excl": 28.0,
  "cart_products_total_tax_amount": 3.36,
  "cart_shipping_total": 4.14,
  "cart_shipping_total_tax_incl": 4.14,
  "cart_shipping_total_tax_excl": 3.7,
  "cart_shipping_total_tax_amount": 0.44,
  "cart_discounts_total": 0.0,
  "cart_discounts_total_tax_incl": 0.0,
  "cart_discounts_total_tax_excl": 0.0,
  "cart_discounts_total_tax_amount": 0.0,
  "cart_updated_at": "2026-05-20T10:30:00-05:00",
  "abandoned_minutes": 60,
  "cart_url": "https://tienda.com/cart?action=show",
  "contact_url": "https://tienda.com/contacto",
  "products": [],
  "related_products": []
}
```

## Estructuras reutilizadas

### products en ordenes

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

### products en carrito

Igual que productos de orden, excepto que no incluye `is_variant` ni `attributes`.

### shipping_address

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

### shop_reviews

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
