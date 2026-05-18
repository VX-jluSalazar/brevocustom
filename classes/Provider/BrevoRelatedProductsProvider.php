<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class BrevoRelatedProductsProvider
{
    private $context;

    public function __construct(?Context $context = null)
    {
        $this->context = $context ?: Context::getContext();
    }

    public function getRelatedProducts(array $categoryIds, array $excludedProductIds = [], int $limit = 3): array
    {
        $categoryIds = array_values(array_unique(array_filter(array_map('intval', $categoryIds))));
        $excludedProductIds = array_values(array_unique(array_filter(array_map('intval', $excludedProductIds))));

        if (empty($categoryIds) || $limit <= 0) {
            return [];
        }

        $sql = 'SELECT DISTINCT p.id_product, p.id_category_default, pl.name, pl.link_rewrite,
                cl.name AS category_name, product_shop.price
            FROM `' . _DB_PREFIX_ . 'category_product` cp
            INNER JOIN `' . _DB_PREFIX_ . 'product` p ON (p.id_product = cp.id_product)
            ' . Shop::addSqlAssociation('product', 'p') . '
            INNER JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON (
                pl.id_product = p.id_product
                AND pl.id_lang = ' . (int) $this->context->language->id . '
                AND pl.id_shop = ' . (int) $this->context->shop->id . '
            )
            LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl ON (
                cl.id_category = p.id_category_default
                AND cl.id_lang = ' . (int) $this->context->language->id . '
                AND cl.id_shop = ' . (int) $this->context->shop->id . '
            )
            WHERE cp.id_category IN (' . implode(',', $categoryIds) . ')
                AND product_shop.active = 1
                AND product_shop.visibility IN (\'both\', \'catalog\')
                ' . (!empty($excludedProductIds) ? 'AND p.id_product NOT IN (' . implode(',', $excludedProductIds) . ')' : '') . '
            ORDER BY product_shop.date_upd DESC
            LIMIT ' . (int) $limit;

        $rows = Db::getInstance()->executeS($sql);
        if (!is_array($rows)) {
            return [];
        }

        $products = [];
        foreach ($rows as $row) {
            $idProduct = (int) $row['id_product'];
            $product = new Product($idProduct, false, (int) $this->context->language->id, (int) $this->context->shop->id);
            $cover = Product::getCover($idProduct);
            $imageUrl = '';

            if (is_array($cover) && !empty($cover['id_image'])) {
                $imageUrl = $this->context->link->getImageLink(
                    (string) $row['link_rewrite'],
                    (string) $cover['id_image'],
                    ImageType::getFormattedName('home')
                );
            }

            $products[] = [
                'id_product' => $idProduct,
                'name' => (string) $row['name'],
                'category_id' => (int) $row['id_category_default'],
                'category_name' => (string) $row['category_name'],
                'price' => (float) Product::getPriceStatic($idProduct, true),
                'currency' => $this->context->currency ? (string) $this->context->currency->iso_code : '',
                'product_url' => $this->context->link->getProductLink($product),
                'image_url' => $imageUrl,
            ];
        }

        return $products;
    }
}
