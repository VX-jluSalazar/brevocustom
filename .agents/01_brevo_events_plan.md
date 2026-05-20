# Planificacion de eventos Brevo para brevocustom

Este documento es el indice general de la implementacion del modulo `brevocustom`.
Cada fase vive en un archivo separado para que sea mas facil modificar payloads, queries y criterios sin mezclar temas.

## Referencia Brevo

- Endpoint: `POST https://api.brevo.com/v3/events`
- Header: `api-key: <BREVO_API_KEY>`
- Header: `Content-Type: application/json`
- Documentacion base: `00_oficial_documentation.md`

## Eventos planificados

- `vx_order_created`
- `vx_subscriber`
- `vx_order_sent`
- `vx_order_delivered`
- `vx_abandoned_cart`

Nota: el evento de orden enviada se corrige a `vx_order_sent`.

## Fases

- `FASE_01_CONFIGURACION_DEL_MODULO.md`
- `FASE_02_EVENTO_VX_ORDER_CREATED.md`
- `FASE_03_EVENTO_VX_SUBSCRIBER.md`
- `FASE_04_EVENTO_VX_ORDER_SENT.md`
- `FASE_05_EVENTO_VX_ORDER_DELIVERED.md`
- `FASE_06_EVENTO_VX_ABANDONED_CART.md`
- `FASE_07_CONTROL_DUPLICADOS_TRAZABILIDAD.md`
- `FASE_08_VALIDACION.md`

## Reglas generales de payload

Todos los eventos deben enviar:

- `event_name`: nombre del evento.
- `event_date`: fecha ISO 8601 del momento real del evento.
- `identifiers.email_id`: email del cliente o suscriptor.
- `contact_properties`: atributos utiles para actualizar/segmentar el contacto.
- `event_properties`: informacion del evento para personalizacion y condiciones.
- `object`: identificador externo del objeto relacionado cuando aplique.

Convenciones sugeridas:

- Fechas en ISO 8601, por ejemplo `2026-05-18T11:30:00-05:00`.
- Montos como numeros, no strings.
- Moneda como codigo ISO, por ejemplo `USD`.
- URLs absolutas y listas para usar en plantillas.
- Evitar datos sensibles innecesarios.
- Mantener el payload completo debajo de 50 KB, limite indicado por Brevo.

## Datos pendientes por completar

- Criterio final para identificar cuando una orden esta enviada.
- Criterio final para identificar cuando una orden esta entregada.
- Tiempo de espera para carrito abandonado.
- URL final/base de tienda por ambiente.

## URLs base sugeridas

- URL base de tienda: se obtiene desde PrestaShop con la URL base de la tienda actual.
- Link para agregar review: `{shop_url}/shop-reviews-add`
- URL de contacto WhatsApp: `https://api.whatsapp.com/send/?phone=593123456789`
- URL para hacer el pedido de nuevo:

Patron para `reorder_url`:

```text
{shop_url}/pedido?submitReorder=1&id_order={id_order}
```

## Estados de orden PrestaShop

Estados relevantes para los eventos:

- `2`: Pago aceptado.
- `4`: Enviado.
- `5`: Entregado.

Listado compartido de estados:

| ID | Nombre | Plantilla |
| --- | --- | --- |
| 1 | En espera de pago por cheque | cheque |
| 2 | Pago aceptado | payment |
| 3 | Preparacion en curso | preparation |
| 4 | Enviado | shipped |
| 5 | Entregado |  |
| 6 | Cancelado | order_canceled |
| 7 | Reembolsado | refund |
| 8 | Error en pago | payment_error |
| 9 | Pedido pendiente por falta de stock (pagado) | outofstock |
| 10 | En espera de pago por transferencia bancaria | bankwire |
| 11 | Pago remoto aceptado | payment |
| 12 | Pedido pendiente por falta de stock (no pagado) | outofstock |
| 13 | En espera de validacion por contra reembolso. | cashondelivery |
| 14 | Esperando el pago |  |
| 15 | Reembolso parcial |  |
| 16 | Pago parcial |  |
| 17 | Autorizado. El vendedor lo capturara |  |
| 18 | Awaiting for PayPal payment |  |

## Productos relacionados para ordenes

Para eventos de orden se recomienda incluir 3 productos relacionados.

Criterio:

1. Tomar las categorias de los productos comprados.
2. Buscar productos activos de esas mismas categorias.
3. Excluir productos ya comprados en la orden.
4. Priorizar productos disponibles, visibles y con imagen.
5. Limitar a 3 resultados.

Payload sugerido para cada producto relacionado:

```json
{
  "id_product": 321,
  "name": "Producto relacionado",
  "category_id": 12,
  "category_name": "Categoria",
  "price": 19.99,
  "currency": "USD",
  "product_url": "https://farmagro.desarrollovelox.com/producto-relacionado",
  "image_url": "https://farmagro.desarrollovelox.com/img/p/..."
}
```

## Reviews de tienda

Las reviews se obtienen desde `PREFIX_bt_spr_shop_reviews`.

En la tienda actual la tabla es `ps_bt_spr_shop_reviews`.

Columnas disponibles:

- `id_review`
- `id_order`
- `id_customer`
- `id_lang`
- `title_review`
- `text_review`
- `rating_value`
- `review_status`
- `id_shop`
- `date_add`
- `date_upd`

Consulta sugerida para obtener reviews aprobadas:

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

Estructura recomendada:

```json
{
  "shop_reviews": [
    {
      "id_review": 1001,
      "id_order": 456,
      "id_customer": 123,
      "id_lang": 1,
      "title": "Calidad y comodidad",
      "summary": "Un calzado muy versatil y comodo, a mi hija le encanta.",
      "rating": 5,
      "review_status": 1,
      "id_shop": 1,
      "created_at": "2024-04-02T00:00:00-05:00",
      "updated_at": "2024-04-02T00:00:00-05:00"
    }
  ],
  "shop_review_url": "https://farmagro.desarrollovelox.com/shop-reviews-add"
}
```

Mapeo recomendado:

- `id_review`: `id_review`.
- `id_order`: `id_order`.
- `id_customer`: `id_customer`.
- `id_lang`: `id_lang`.
- `title`: `title_review`.
- `summary`: `text_review`.
- `rating`: `rating_value`.
- `review_status`: `review_status`.
- `id_shop`: `id_shop`.
- `created_at`: `date_add`.
- `updated_at`: `date_upd`.

## Guia Servientrega

La guia se obtiene desde `PREFIX_order_servientrega` usando `id_order`.

Campo principal:

- `id_order_servientrega`: codigo de guia enviado como `tracking_code`.

Campos adicionales:

- `pedido`
- `fecha`
- `estado`
- `total`
- `rastreoEnvio`
- `razon`
- `city`

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
