<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class BrevoShopReviewsProvider
{
    public function getLatestApprovedReviews(int $idShop, int $limit = 5): array
    {
        if (!$this->tableExists()) {
            return [];
        }

        $sql = 'SELECT id_review, id_order, id_customer, id_lang, title_review, text_review,
                rating_value, review_status, id_shop, date_add, date_upd
            FROM `' . _DB_PREFIX_ . 'bt_spr_shop_reviews`
            WHERE review_status = 1
                AND id_shop = ' . (int) $idShop . '
            ORDER BY date_add DESC
            LIMIT ' . (int) $limit;

        $rows = Db::getInstance()->executeS($sql);
        if (!is_array($rows)) {
            return [];
        }

        return array_map(function (array $row): array {
            return [
                'id_review' => (int) $row['id_review'],
                'id_order' => (int) $row['id_order'],
                'id_customer' => (int) $row['id_customer'],
                'id_lang' => (int) $row['id_lang'],
                'title' => (string) $row['title_review'],
                'summary' => (string) $row['text_review'],
                'rating' => (int) $row['rating_value'],
                'review_status' => (int) $row['review_status'],
                'id_shop' => (int) $row['id_shop'],
                'created_at' => (string) $row['date_add'],
                'updated_at' => (string) $row['date_upd'],
            ];
        }, $rows);
    }

    private function tableExists(): bool
    {
        $sql = 'SELECT COUNT(*)
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
                AND table_name = \'' . pSQL(_DB_PREFIX_ . 'bt_spr_shop_reviews') . '\'';

        return (int) Db::getInstance()->getValue($sql) > 0;
    }
}
