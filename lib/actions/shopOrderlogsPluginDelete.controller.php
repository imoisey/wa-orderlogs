<?php

class shopOrderlogsPluginDeleteController extends waJsonController
{
    public function execute()
    {
        $orderlogs_id = waRequest::post('orderlogs_id', false);
        if (!$orderlogs_id) {
            return;
        }

        // проверка на админа
        $user_id = wa()->getUser()->getId();
        $user = new waContact($user_id);
        if( !$user->isAdmin('shop') ) {
            return;
        }

        // get logs
        $orderlogs_model = new shopOrderLogModel();
        $del_result = $orderlogs_model->deleteById($orderlogs_id);

        // Интеграция с плагином orderfiles
        if( class_exists('shopOrderfilesPluginModel') ) {
            // Подключаем модель и получаем данные
            $m_orderfiles = new shopOrderfilesPluginModel();
            $files_data = $m_orderfiles->getByField('orderlog_id', $orderlogs_id, true);

            // Проверяем, получили ли данные
            if( is_array($files_data) ) {
                $file_dir = wa()->getDataPath('orderfiles').DIRECTORY_SEPARATOR;
                foreach ($files_data as $file_data) {
                    // Удаляем файл физически
                    waFiles::delete($file_dir . $file_data['path']);
                }
            }

        }
        // Конец интеграции с orderfiles

        if( !$del_result )
            $this->error = _wp('Запись не была удалена');
    }
}