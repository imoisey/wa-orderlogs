<?php

class shopOrderlogsPluginLogsController extends waJsonController
{
    public function execute()
    {
        $order_id = waRequest::post('order_id', false);
        if (!$order_id) {
            return;
        }

        // get logs
        $orderlogs_model = new shopOrderLogModel();
        $rows = $orderlogs_model->getByField('order_id', $order_id, true);

        // Правильная сортировка
        krsort($rows);

        foreach ($rows as $log) {
            $this->response[] = $log['id'];
        }
    }
}