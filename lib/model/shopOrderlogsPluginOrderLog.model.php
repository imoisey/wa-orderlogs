<?php

class shopOrderlogsPluginOrderLogModel extends shopOrderLogModel {

	public function editText($order_id, $text) {
		// Получаем 'последнюю' запись из лога
		$log_id = $this->query("SELECT id FROM ".$this->table." WHERE order_id = i:id AND action_id = 'edit' ORDER BY id DESC LIMIT 1", array('id' => $order_id))
			 ->fetchField('id');
		// Убираем все пробельные символы
		$text = str_replace(array("\n","\r","\t"), "", $text);
		$this->updateById($log_id, array('text'=>$text) );
		return true;
	}
}