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

        $sql = 'SELECT sr.id_review, sr.id_order, sr.id_customer, sr.id_lang, sr.title_review, sr.text_review,
                sr.rating_value, sr.review_status, sr.id_shop, sr.date_add, sr.date_upd,
                c.firstname AS customer_firstname, c.lastname AS customer_lastname
            FROM `' . _DB_PREFIX_ . 'bt_spr_shop_reviews` sr
            LEFT JOIN `' . _DB_PREFIX_ . 'customer` c ON (c.id_customer = sr.id_customer)
            WHERE sr.review_status = 1
                AND sr.id_shop = ' . (int) $idShop . '
            ORDER BY sr.date_add DESC
            LIMIT ' . (int) $limit;

        $rows = Db::getInstance()->executeS($sql);
        if (!is_array($rows)) {
            return [];
        }

        return array_map(function (array $row): array {
            $customerFirstname = trim((string) $row['customer_firstname']);
            $customerLastname = trim((string) $row['customer_lastname']);
            $customerName = trim($customerFirstname . ' ' . $customerLastname);

            return [
                'id_review' => (int) $row['id_review'],
                'id_order' => (int) $row['id_order'],
                'id_customer' => (int) $row['id_customer'],
                'customer_firstname' => $customerFirstname,
                'customer_lastname' => $customerLastname,
                'customer_name' => $customerName,
                'customer_initials' => $this->getInitials($customerName),
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

    private function getInitials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name));
        if (!is_array($parts) || empty($parts)) {
            return '';
        }

        $initials = '';
        $count = 0;
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }

            $initials .= function_exists('mb_substr') ? mb_substr($part, 0, 1, 'UTF-8') : substr($part, 0, 1);
            ++$count;
            if ($count >= 2) {
                break;
            }
        }

        return function_exists('mb_strtoupper') ? mb_strtoupper($initials, 'UTF-8') : strtoupper($initials);
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
