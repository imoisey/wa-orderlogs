<?php

return array(
	'name'			=> 'История изменений заказа',
	'description'	=> 'Собирает историю изменений в заказе и сохраняет ее в логи',
	'version'		=> '1.6.0',
	'vendor'		=> 1028182,
	'handlers'		=> array(
		'backend_order_edit'	=> 'backendOrderlogs',
		'backend_order'	=> 'backendOrderlogsManager' # v. 1.0.0
	),
	'img'			=> 'img/icon.png'
);