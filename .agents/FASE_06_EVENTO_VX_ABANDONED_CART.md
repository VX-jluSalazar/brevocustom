# Fase 06: evento `vx_abandoned_cart`

## Estado

Implementado mediante el front controller `modules/brevocustom/controllers/front/cron.php`.

La URL se muestra en la pestaña `Automatizacion` de la configuracion del modulo y usa el token `BREVOCUSTOM_CRON_TOKEN`.

## Disparador sugerido

- Cron del modulo que detecte carritos con productos sin orden asociada.
- Ventana sugerida inicial: carrito actualizado hace mas de 1 hora y menos de 24 horas.

## Uso

- Recuperacion de carrito.
- Personalizacion por productos y categorias abandonadas.
- Recomendaciones relacionadas.

## Consideraciones

- Evitar enviar el mismo carrito varias veces sin control.
- Guardar una marca local del evento enviado por `id_cart`.
- Solo enviar si existe email identificable.
- Si el carrito se convierte en orden, no volver a enviarlo.

## Payload recomendado

Nota: el payload implementado actualmente usa la estructura agrupada documentada en `PAYLOADS_EVENTOS.md` (`customer`, `cart.items`, `shipping.carrier`, `payment`, `reviews`, `main_categories`, `misc`). El ejemplo plano siguiente queda como referencia historica y compatibilidad temporal.

```json
{
  "event_name": "vx_abandoned_cart",
  "event_date": "2026-05-18T11:30:00-05:00",
  "identifiers": {
    "email_id": "cliente@example.com",
    "ext_id": "ps_customer_123"
  },
  "contact_properties": {
    "FIRSTNAME": "Nombre",
    "LASTNAME": "Apellido",
    "PS_CUSTOMER_ID": 123,
    "LAST_ABANDONED_CART_ID": 789,
    "LAST_ABANDONED_CART_DATE": "2026-05-18T11:30:00-05:00"
  },
  "event_properties": {
    "cart_id": 789,
    "customer_id": 123,
    "customer_email": "cliente@example.com",
    "shop_url": "https://farmagro.desarrollovelox.com",
    "currency": "USD",
    "cart_total": 64.5,
    "cart_products_total": 60.0,
    "cart_shipping_total": 4.5,
    "cart_updated_at": "2026-05-18T10:00:00-05:00",
    "abandoned_minutes": 90,
    "cart_url": "https://farmagro.desarrollovelox.com/cart?action=show",
    "contact_url": "https://api.whatsapp.com/send/?phone=593959212641&text=%C2%A1Hola%21+Necesito+informaci%C3%B3n+sobre%3A+&type=phone_number&app_absent=0",
    "products": [
      {
        "id_product": 101,
        "id_product_attribute": 0,
        "reference": "SKU-101",
        "name": "Producto en carrito",
        "category_id": 12,
        "category_name": "Categoria",
        "quantity": 1,
        "unit_price": 20.0,
        "total_price": 20.0,
        "product_url": "https://farmagro.desarrollovelox.com/producto",
        "image_url": "https://farmagro.desarrollovelox.com/img/p/..."
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
    ]
  },
  "object": {
    "type": "cart",
    "identifiers": {
      "ext_id": "ps_cart_789"
    }
  }
}
```

## Pendientes

- Definir si se enviara a invitados con email capturado.
