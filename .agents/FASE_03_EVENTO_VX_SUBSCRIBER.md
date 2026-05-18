# Fase 03: evento `vx_subscriber`

## Estado

Implementado en `modules/brevocustom/brevocustom.php` mediante el hook `actionNewsletterRegistrationAfter` del modulo nativo `ps_emailsubscription`.

## Disparador sugerido

- Cuando un contacto se suscribe al newsletter.
- Hook o flujo segun modulo newsletter instalado.

## Uso

- Bienvenida.
- Segmentacion de nuevos suscriptores.
- Captura de origen de suscripcion.

## Payload recomendado

```json
{
  "event_name": "vx_subscriber",
  "event_date": "2026-05-18T11:30:00-05:00",
  "identifiers": {
    "email_id": "suscriptor@example.com",
    "ext_id": "newsletter_suscriptor_example_com"
  },
  "contact_properties": {
    "EMAIL": "suscriptor@example.com",
    "FIRSTNAME": "",
    "LASTNAME": "",
    "NEWSLETTER": true,
    "SUBSCRIBED_AT": "2026-05-18T11:30:00-05:00",
    "SUBSCRIPTION_SOURCE": "footer"
  },
  "event_properties": {
    "email": "suscriptor@example.com",
    "customer_id": null,
    "is_customer": false,
    "shop_url": "https://farmagro.desarrollovelox.com",
    "contact_url": "https://api.whatsapp.com/send/?phone=593959212641&text=%C2%A1Hola%21+Necesito+informaci%C3%B3n+sobre%3A+&type=phone_number&app_absent=0",
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
    "main_categories": [
      {
        "id_category": 12,
        "name": "Categoria principal 1",
        "description": "Descripcion breve de la categoria principal 1",
        "category_url": "https://farmagro.desarrollovelox.com/categoria-principal-1",
        "image_url": "https://farmagro.desarrollovelox.com/c/12-category_default/categoria-principal-1.jpg"
      },
      {
        "id_category": 13,
        "name": "Categoria principal 2",
        "description": "Descripcion breve de la categoria principal 2",
        "category_url": "https://farmagro.desarrollovelox.com/categoria-principal-2",
        "image_url": "https://farmagro.desarrollovelox.com/c/13-category_default/categoria-principal-2.jpg"
      },
      {
        "id_category": 14,
        "name": "Categoria principal 3",
        "description": "Descripcion breve de la categoria principal 3",
        "category_url": "https://farmagro.desarrollovelox.com/categoria-principal-3",
        "image_url": "https://farmagro.desarrollovelox.com/c/14-category_default/categoria-principal-3.jpg"
      }
    ]
  },
  "object": {
    "type": "subscription",
    "identifiers": {
      "ext_id": "newsletter_suscriptor_example_com"
    }
  }
}
```

## Pendientes

- Confirmar limite de categorias principales a enviar si existen muchas. -> Todas las categorias

## Categorias principales

Para este evento se enviaran categorias del primer nivel visible de la tienda.

Criterio sugerido:

- Categorias activas.
- Categorias visibles para el idioma y tienda actual.
- Hijas directas de la categoria raiz/home usada por la tienda.
- Ordenadas por posicion.

Campos recomendados por categoria:

- `id_category`
- `name`
- `description`
- `category_url`
- `image_url`

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
