<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class BrevoServientregaProvider
{
    public function getTrackingByOrderId(int $idOrder): ?array
    {
        if (!$this->tableExists()) {
            return null;
        }

        $sql = 'SELECT id_order_servientrega, pedido, id_order, fecha, estado, total,
                rastreoEnvio, razon, city
            FROM `' . _DB_PREFIX_ . 'order_servientrega`
            WHERE id_order = ' . (int) $idOrder . '
            ORDER BY date_add DESC';

        $row = Db::getInstance()->getRow($sql);
        if (!is_array($row) || empty($row)) {
            return null;
        }

        return [
            'id_order_servientrega' => (string) $row['id_order_servientrega'],
            'pedido' => (string) $row['pedido'],
            'estado' => (int) $row['estado'],
            'fecha' => (string) $row['fecha'],
            'total' => (string) $row['total'],
            'rastreoEnvio' => (string) $row['rastreoEnvio'],
            'razon' => (string) $row['razon'],
            'city' => (string) $row['city'],
        ];
    }

    private function tableExists(): bool
    {
        $sql = 'SELECT COUNT(*)
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
                AND table_name = \'' . pSQL(_DB_PREFIX_ . 'order_servientrega') . '\'';

        return (int) Db::getInstance()->getValue($sql) > 0;
    }
}
