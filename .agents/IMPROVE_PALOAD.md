# Eliminar duplicados en payload de Orden
| Representan lo mismo                                     | Quédate con                     | Elimina                |
| -------------------------------------------------------- | ------------------------------- | ---------------------- |
| `currency` y `order.currency`                            | `order.currency`                | `currency`             |
| `customer_email` y `customer.email`                      | `customer.email`                | `customer_email`       |
| `customer_firstname` y `customer.firstname`              | `customer.firstname`            | `customer_firstname`   |
| `customer_lastname` y `customer.lastname`                | `customer.lastname`             | `customer_lastname`    |
| `order_id` y `order.id`                                  | `order.id`                      | `order_id`             |
| `order_reference` y `order.reference`                    | `order.reference`               | `order_reference`      |
| `order_date` y `order.date`                              | `order.date`                    | `order_date`           |
| `order_date_formatted` y `order.date_formatted`          | `order.date_formatted`          | `order_date_formatted` |
| `order_status` y `order.status`                          | `order.status`                  | `order_status`         |
| `order_status_id` y `order.status_id`                    | `order.status_id`               | `order_status_id`      |
| `products` y `order.items`                               | `order.items`                   | `products`             |
| `payment_method` y `payment.method`                      | `payment.method`                | `payment_method`       |
| `payment_method_id` y `payment.method_id`                | `payment.method_id`             | `payment_method_id`    |
| `payment_module` y `payment.module`                      | `payment.module`                | `payment_module`       |
| `carrier_id` y `shipping.carrier.id`                     | `shipping.carrier.id`           | `carrier_id`           |
| `carrier_name` y `shipping.carrier.name`                 | `shipping.carrier.name`         | `carrier_name`         |
| `carrier_reference_id` y `shipping.carrier.reference_id` | `shipping.carrier.reference_id` | `carrier_reference_id` |
| `shipping_address` y `shipping.address`                  | `shipping.address`              | `shipping_address`     |
| `contact_url` y `misc.contact_url`                       | `misc.contact_url`              | `contact_url`          |
| `shop_url` y `misc.shop_url`                             | los dos                         | ninguno                |
| `reorder_url` y `misc.reorder_url`                       | `misc.reorder_url`              |`reorder_url`           | 
| `shop_review_url` y `misc.shop_review_url`               | `misc.shop_review_url`          | `shop_review_url`      |
| `reviews` y `shop_reviews`                               | `shop_reviews`                  | `reviews`              |

# Eliminar duplicados en payload de Cart
| Representan lo mismo                                                   | Quédate con                        | Elimina                           |
| ---------------------------------------------------------------------- | ---------------------------------- | --------------------------------- |
| `abandoned_minutes` y `cart.abandoned_minutes`                         | `cart.abandoned_minutes`           | `abandoned_minutes`               |
| `cart_id` y `cart.id`                                                  | `cart.id`                          | `cart_id`                         |
| `products` y `cart.items`                                              | `cart.items`                       | `products`                        |
| `cart_url`, `cart.url`, `misc.cart_url`                                | `cart.url`,`misc.cart_url`         | `cart_url`                        |
| `cart_updated_at` y `cart.updated_at`                                  | `cart.updated_at`                  | `cart_updated_at`                 |
| `customer_email` y `customer.email`                                    | `customer.email`                   | `customer_email`                  |
| `customer_id` y `customer.id`                                          | `customer.id`                      | `customer_id`                     |
| `cart_total` y `cart.totals.total`                                     | `cart.totals.total`                | `cart_total`                      |
| `cart_total_tax_amount` y `cart.totals.total_tax_amount`               | `cart.totals.total_tax_amount`     | `cart_total_tax_amount`           |
| `cart_total_tax_excl` y `cart.totals.total_tax_excl`                   | `cart.totals.total_tax_excl`       | `cart_total_tax_excl`             |
| `cart_total_tax_incl` y `cart.totals.total_tax_incl`                   | `cart.totals.total_tax_incl`       | `cart_total_tax_incl`             |
| `cart_products_total` y `cart.totals.products`                         | `cart.totals.products`             | `cart_products_total`             |
| `cart_products_total_tax_amount` y `cart.totals.products_tax_amount`   | `cart.totals.products_tax_amount`  | `cart_products_total_tax_amount`  |
| `cart_products_total_tax_excl` y `cart.totals.products_tax_excl`       | `cart.totals.products_tax_excl`    | `cart_products_total_tax_excl`    |
| `cart_products_total_tax_incl` y `cart.totals.products_tax_incl`       | `cart.totals.products_tax_incl`    | `cart_products_total_tax_incl`    |
| `cart_shipping_total` y `cart.totals.shipping`                         | `cart.totals.shipping`             | `cart_shipping_total`             |
| `cart_shipping_total_tax_amount` y `cart.totals.shipping_tax_amount`   | `cart.totals.shipping_tax_amount`  | `cart_shipping_total_tax_amount`  |
| `cart_shipping_total_tax_excl` y `cart.totals.shipping_tax_excl`       | `cart.totals.shipping_tax_excl`    | `cart_shipping_total_tax_excl`    |
| `cart_shipping_total_tax_incl` y `cart.totals.shipping_tax_incl`       | `cart.totals.shipping_tax_incl`    | `cart_shipping_total_tax_incl`    |
| `cart_discounts_total` y `cart.totals.discounts`                       | `cart.totals.discounts`            | `cart_discounts_total`            |
| `cart_discounts_total_tax_amount` y `cart.totals.discounts_tax_amount` | `cart.totals.discounts_tax_amount` | `cart_discounts_total_tax_amount` |
| `cart_discounts_total_tax_excl` y `cart.totals.discounts_tax_excl`     | `cart.totals.discounts_tax_excl`   | `cart_discounts_total_tax_excl`   |
| `cart_discounts_total_tax_incl` y `cart.totals.discounts_tax_incl`     | `cart.totals.discounts_tax_incl`   | `cart_discounts_total_tax_incl`   |
| `contact_url` y `misc.contact_url`                                     | `misc.contact_url`                 | `contact_url`                     |
| `shop_url` y `misc.shop_url`                                           | los dos                            | ninguno                           |
| `cart_url` y `misc.cart_url`                                           | `cart.url`, `misc.cart_url`        |  `cart_url`                       |


# Eliminar duplicados en payload de Suscriber
| Representan lo mismo                   | Quédate con            | Elimina            |
| -------------------------------------- | ---------------------- | ------------------ |
| `customer_id` y `customer.id`          | `customer.id`          | `customer_id`      |
| `email` y `customer.email`             | `customer.email`       | `email`            |
| `is_customer` y `customer.is_customer` | `customer.is_customer` | `is_customer`      |
| `contact_url` y `misc.contact_url`     | `misc.contact_url`     | `contact_url`      |
| `shop_url` y `misc.shop_url`           |los dos                 | ninguno            |
| `reviews` y `shop_reviews`             | `shop_reviews`         | `reviews`          |
