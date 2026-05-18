<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class BrevocustomCronModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    public $ajax = true;
    public $display_header = false;
    public $display_footer = false;

    public function initContent()
    {
        parent::initContent();

        header('Content-Type: application/json');

        if (!$this->module instanceof Brevocustom) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid module instance.',
            ]);
            exit;
        }

        $result = $this->module->runAbandonedCartCron((string) Tools::getValue('token'));

        if (empty($result['success']) && ($result['message'] ?? '') === 'Invalid cron token.') {
            http_response_code(403);
        }

        echo json_encode($result);
        exit;
    }
}
