# Plan de agrupacion de payloads y URLs custom

Este documento propone la siguiente evolucion del payload de eventos Brevo y de la seccion de URLs del Back Office.

## Objetivo

Agrupar los atributos actuales para que los JSON sean mas faciles de leer, mantener y usar en plantillas Brevo.

Estructura objetivo:

```json
{
  "customer": {},
  "order": {
    "items": [
      {
        "attributes": []
      }
    ]
  },
  "cart": {
    "items": [
      {
        "attributes": []
      }
    ]
  },
  "shipping": {
    "carrier": {}
  },
  "payment": {
    "billing_address":{}
  },
  "reviews": [],
  "main_categories": [],
  "misc": {}
}
```

## Agrupacion propuesta

### customer

Campos relacionados con el cliente o email receptor.

- `customer_id`
- `customer_email`
- `customer_firstname`
- `customer_lastname`
- `is_customer`
- `email`

Ejemplo:

```json
{
  "customer": {
    "id": 123,
    "email": "cliente@dominio.com",
    "firstname": "Juan",
    "lastname": "Perez",
    "is_customer": true
  }
}
```

### order

Campos propios de la orden.

- `order_id`
- `order_reference`
- `order_date`
- `order_date_formatted`
- `order_status`
- `order_status_id`
- `sent_at`
- `delivered_at`
- totales e impuestos de orden

Ejemplo:

```json
{
  "order": {
    "id": 456,
    "reference": "ABC123",
    "date": "2026-05-20T10:30:00-05:00",
    "date_formatted": "20 mayo, 2026",
    "status": "Pago aceptado",
    "status_id": 2,
    "sent_at": "2026-05-20T10:30:00-05:00",
    "delivered_at": null,
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
  }
}
```

### order.items

Equivale al arreglo actual `products` en eventos de orden.

Cada item mantiene:

- `id_product`
- `id_product_attribute`
- `reference`
- `name`
- `category_id`
- `category_name`
- `quantity`
- precios e impuestos
- `tax_rate`
- `tax_name`
- `product_url`
- `image_url`
- `is_variant`
- `attributes`

Ejemplo:

```json
{
  "items": [
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
  ]
}
```

### cart

Campos propios del carrito abandonado.

- `cart_id`
- `cart_updated_at`
- `abandoned_minutes`
- totales e impuestos de carrito
- `cart_url`

Ejemplo:

```json
{
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
  }
}
```

### cart.items

Equivale al arreglo actual `products` en carrito abandonado.

Para consistencia con `order.items`, cada item deberia incluir `attributes`, aunque inicialmente sea un arreglo vacio.

### shipping

Contiene datos de direccion, tracking y transportista.

- `carrier_id`
- `carrier_reference_id`
- `carrier_name`

Campos de direccion:

- `firstname`
- `lastname`
- `dni`
- `address1`
- `address2`
- `city`
- `state`
- `country`
- `phone`

Campos de tracking:

- `tracking_code`
- `tracking_url`
- `servientrega`

Ejemplo:

```json
{
  "shipping": {
      "id": 4,
      "reference_id": 2,
      "name": "Servientrega",
    "address": {
      "firstname": "Juan",
      "lastname": "Perez",
      "dni": "0102030405",
      "address1": "Calle 1",
      "address2": "",
      "city": "Quito",
      "state": "Pichincha",
      "country": "Ecuador",
      "phone": "0999999999"
    },
    "servientrega": {
      "tracking_code": "GUIA-123456789",
      "tracking_url": "https://www.servientrega.com.ec/rastreo",
    }
  }
}
```

### payment

Campos del metodo de pago.

- `payment_method_id`
- `payment_method`
- `payment_module`

Ejemplo:

```json
{
  "payment": {
    "method_id": 12,
    "method": "Transferencia bancaria",
    "module": "ps_wirepayment",
    "billing_address": {
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
  }
}
```

### reviews

Equivale al arreglo actual `shop_reviews`.

Debe conservar:

- `id_review`
- `id_order`
- `id_customer`
- `customer_firstname`
- `customer_lastname`
- `customer_name`
- `customer_initials`
- `title`
- `summary`
- `rating`
- `created_at`
- `updated_at`

### main_categories

Equivale al arreglo actual `main_categories`.

Debe conservar:

- `id_category`
- `name`
- `description`
- `category_url`
- `image_url`

### misc

Contiene URLs y datos auxiliares que no pertenecen a customer, order, cart, shipping o payment.
Todas las URLs adicionales configuradas desde Back Office se agregan directamente como atributos de `misc`; no existe un subgrupo `custom_urls`.

- `shop_url`
- `contact_url`
- `shop_review_url`
- `reorder_url`
- `cart_url`, si no se decide dejarlo solo en `cart.url`
- URLs custom configuradas desde Back Office como campos planos

Ejemplo:

```json
{
  "misc": {
    "shop_url": "https://tienda.com",
    "shop_review_url": "https://tienda.com/shop-reviews-add",
    "contact_url": "https://tienda.com/contacto",
    "reorder_url": "https://tienda.com/reorder/456",
    "new_custom_attribute": "https://tienda.com/custom",
    "another_custom_attribute": "https://dasdasda.com"
  }
}
```

## Plan de implementacion de agrupacion

1. Crear helpers internos:
   - `buildCustomerGroupPayload(...)`
   - `buildOrderGroupPayload(...)`
   - `buildCartGroupPayload(...)`
   - `buildShippingGroupPayload(...)`
   - `buildCarrierGroupPayload(...)`
   - `buildPaymentGroupPayload(...)`
   - `buildMiscGroupPayload(...)`

2. Actualizar eventos de orden:
   - `vx_order_created`
   - `vx_order_sent`
   - `vx_order_delivered`

3. Actualizar evento de carrito:
   - `vx_abandoned_cart`

4. Actualizar evento de suscripcion:
   - `vx_subscriber`
   - Usar `customer`, `reviews`, `main_categories` y `misc`.

5. Mantener compatibilidad temporal:
   - Durante una fase de migracion, conservar campos planos criticos si ya existen plantillas en Brevo.
   - Ejemplos: `order_id`, `products`, `shop_url`, `contact_url`.

6. Actualizar documentacion:
   - `PAYLOADS_EVENTOS.md`
   - Fases `FASE_02` a `FASE_06`

7. Validar:
   - Enviar un evento de prueba por tipo.
   - Confirmar que Brevo permite usar rutas anidadas como `params.order.items`.
   - Confirmar loops sobre `params.order.items` y `params.cart.items`.

## Plan de URLs custom en Back Office

Objetivo: permitir que la seccion URLs del modulo tenga items custom con boton de agregar y eliminar.

### Configuracion

Mantener las URLs actuales:

- `BREVOCUSTOM_SHOP_URL`
- `BREVOCUSTOM_CONTACT_URL`
- `BREVOCUSTOM_SHOP_REVIEW_URL`
- `BREVOCUSTOM_REORDER_URL_PATTERN`

Agregar:

- `BREVOCUSTOM_CUSTOM_URLS`

Formato JSON:

```json
[
  {
    "key": "faq_url",
    "label": "Preguntas frecuentes",
    "url": "https://tienda.com/faq"
  }
]
```

### Back Office

En `views/templates/admin/configure.tpl`:

- Agregar tabla o lista editable en la seccion URLs.
- Boton `Agregar URL`.
- Boton eliminar por fila.
- Campos por fila:
  - `key`
  - `label`
  - `url`

### Validacion

- `key`: requerido, unico, normalizado con `normalizeIdentifier`.
- `label`: texto plano.
- `url`: requerida para guardar la fila; validar con `isValidUrl`.
- Ignorar filas vacias.

### Guardado

En `postProcess`:

- Leer arrays:
  - `custom_url_key[]`
  - `custom_url_label[]`
  - `custom_url_value[]`
- Normalizar y validar.
- Guardar JSON en `Configuration::updateValue('BREVOCUSTOM_CUSTOM_URLS', json_encode($urls))`.

### Uso en payload

Agregar cada URL custom directamente en `misc` usando su `key` como nombre del atributo.

```json
{
  "misc": {
    "shop_url": "https://tienda.com",
    "shop_review_url": "https://tienda.com/shop-reviews-add",
    "contact_url": "https://tienda.com/contacto",
    "reorder_url": "https://tienda.com/reorder/456",
    "faq_url": "https://tienda.com/faq",
    "terms_url": "https://tienda.com/terminos"
  }
}
```

Regla de conflicto:

- Si una URL custom usa una key reservada (`shop_url`, `shop_review_url`, `contact_url`, `reorder_url`, `cart_url`), no se debe guardar o debe sobrescribirse con advertencia explicita en Back Office.

Opcionalmente los labels pueden guardarse solo para Back Office, pero no se envian en el payload si no son necesarios:

```json
[
  {
    "key": "faq_url",
    "label": "Preguntas frecuentes",
    "url": "https://tienda.com/faq"
  }
]
```
