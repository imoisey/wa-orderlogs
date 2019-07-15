<?php

return array(
	'ENABLED' 		=> array(
		'title'			=> _wp('Включить плагин'),
		'description'	=> _wp('Если плагин перестал быть полезен, его можно выключить.'),
		'control_type'	=> waHtmlControl::CHECKBOX,
		'value'			=> 1
	),

	'TOGGLE_LOG'	=> array(
		'title'			=> _wp('Добавить спойлер'),
		'description'	=> _wp('Включенная настройка поумолчанию скрывает логи под спойлер'),
		'control_type'	=> waHtmlControl::CHECKBOX,
		'value'			=> 0
	),

);