<?php

class shopOrderlogsPlugin extends shopPlugin {

	public static $configs;
    private static $view;

    private static function getView()
    {
        if (!empty(self::$view)) {
            $view = self::$view;
        } else {
            $view = waSystem::getInstance()->getView();
        }
        return $view;
    }


	public function backendOrderlogs($order_data) 
	{
		if(!isset($order_data['id'])) {
			return false;
		}

		// Получаем ID заказа
		$order_id = $order_data['id'];
		
		// Получаем настройки плагина
		shopOrderlogsPlugin::getSettingsDefault();
		// Если плагин выключен
		if( !shopOrderlogsPlugin::$configs['ENABLED'] )
			return false;

		$output = <<<TEXTJS
		<script>
			$(document).ready(function(){
				$("#order-edit-form").attr("action", "?plugin=orderlogs&action=save&id={$order_id}");
			});
		</script>
TEXTJS;

		return $output;
	}

	// Управление логами
	public function backendOrderlogsManager($order) {
		$user_id = wa()->getUser()->getId();
		$user = new waContact($user_id);

		// Получаем настройки плагина
		shopOrderlogsPlugin::getSettingsDefault();

		$view = self::getView();
		$view->assign('order_id', $order['id']);
		$view->assign('toggle_log', shopOrderlogsPlugin::$configs['TOGGLE_LOG']);

		$output = "";
		$output .= $view->fetch($this->path . '/templates/Styles.html');

		// Проверка на админа
		if( !$user->isAdmin('shop') ) 
			return array(
				'info_section' => $output,
			);

		$output .= $view->fetch($this->path . '/templates/DeleteLogs.html');

		return array(
            'info_section' => $output,
        );
	}

	/**
	 * Проверка всех настроек и установка значений поумолчанию
	 * @return [array] Массив с настройками плагина
	 */
	public static function getSettingsDefault()
	{
		$settings = wa()->getPlugin('orderlogs')->getSettings();
		return self::$configs = $settings;
	}

}