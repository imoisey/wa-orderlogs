<?php

class shopOrderlogsPluginBackendSaveController extends shopOrderSaveController
{
    private $models = array();

    protected $logs;
    protected $type;

    private $orderdata_old;
    private $orderdate_new;

    private $contact_old;
    private $contact_new;

    public function execute()
    {
        // Получаем ID заказа
        $order_id = waRequest::get('id', null, waRequest::TYPE_INT);

		$order = new shopOrder($order_id);
		$this->orderdata_old = $order->dataArray();
		$this->contact_old = $this->orderdata_old['contact']->load();

		// Сохраняем заказ
		parent::execute();
		// Если возникли ошибки
		if ($this->errors) {
			return;
		}
		$order = new shopOrder($order_id);
		// Получаем измененные данные о заказе
		$this->orderdata_new = $order->dataArray();
		$this->contact_new = $this->orderdata_new['contact']->load();
        
        // Инициализируем типы
        $this->type = array(
            'product'	=> _wp('Продукт'),
            'service'	=> _wp('Услуга')
        );

        $this->logs = array();
        // Добавленные товары
        $this->logs['add_products'] 	= $this->getAddProducts();
        // Удаленные товары
        $this->logs['del_products'] 	= $this->getDelProducts();
        // Измененные товары
        $this->logs['edit_products'] 	= $this->getEditProducts();
        // Измененная информация о покупателе
        $this->logs['edit_client']		= $this->getEditClient();
        // Измененные параметры заказа
        $this->logs['edit_order']		= $this->getEditOrder();

        // Подключаем шаблонизатор
        $view = wa()->getView();
        $plugin_path = wa()->getConfig()->getPluginPath('orderlogs');

        // Подключаем пользовательский шаблон
        $tpl_log = wa()->getDataPath('orderlogs').DIRECTORY_SEPARATOR.'templates/Log.html';
        if (!is_file($tpl_log)) {
            $tpl_log = $plugin_path.'/templates/Log.html';
        }

        $view->assign('logs', $this->logs);
        $output = $view->fetch($tpl_log);

        $m_orderlog = new shopOrderlogsPluginOrderLogModel();
        $m_orderlog->editText($order_id, $output);

        return true;
    }

    /**
     * Возвращает товары, которые были добавлены в заказ
     * @return array массив добавленых товаров
     */
    protected function getAddProducts()
    {
        if (empty($this->orderdata_old) || empty($this->orderdata_old)) {
            return false;
        }
        $old_products = $this->orderdata_old['items'];
        $new_products = $this->orderdata_new['items'];

        // Получаем массив с добавленными товарыми
        $add_products = array_diff_key($new_products, $old_products);
        // Если массив не пустой формируем группу новых товаров
        if (count($add_products) > 0) {
            $add_product_list = array();
            foreach ($add_products as $item_id => $item) {
                // Если параметра тип не существует
                $type = empty($item['type']) ? 'product' : $item['type'];
                $add_product_list[$item_id] = $item['name']." (id: {$item['product_id']}; type: {$this->type[$type]})";
            }
            return $add_product_list;
        }
        return null;
    }

    /**
     * Возвращает товары, которые были удалены из заказа
     * @return array массив удаленных товаров
     */
    protected function getDelProducts()
    {
        if (empty($this->orderdata_old) || empty($this->orderdata_old)) {
            return false;
        }
        $old_products = $this->orderdata_old['items'];
        $new_products = $this->orderdata_new['items'];

        // Получаем массив с удаленными товарыми
        $del_products = array_diff_key($old_products, $new_products);
        // Если массив не пустой формируем группу удаленных товаров
        if (count($del_products) > 0) {
            $del_product_list = array();
            foreach ($del_products as $item_id => $item) {
                // Если параметра тип не существует
                $type = empty($item['type']) ? 'product' : $item['type'];
                $del_product_list[$item_id] = $item['name']." (id: {$item['product_id']}; type: {$this->type[$type]})";
            }
            return $del_product_list;
        }
        return null;
    }

    /**
     * Возвращает товары, которые были отредактированы
     * @return array массив добавленых товаров
     */
    protected function getEditProducts()
    {
        if (empty($this->orderdata_old) || empty($this->orderdata_old)) {
            return false;
        }
        $old_products = $this->orderdata_old['items'];
        $new_products = $this->orderdata_new['items'];
        // Убираем добавленные товары из массива
        if (is_array($this->logs['add_products'])) {
            $new_products = array_diff_key($new_products, $this->logs['add_products']);
        }
        // Определяем параметры, которые не будет проверять
        $nocheck_params = array(
            'id',
            'order_id',
            'product_id',
            'type',
            'parent_id',
            'image_id',
            'image_filename',
            'sku_image_id',
            'ext',
            'file_name',
            'file_size'
        );
        // Проходим по каждому товару
        $edit_products_arr = array();
        foreach ($new_products as $item_id => $product) {
            // Проходим по отдельному параметру товара
            foreach ($product as $param_key => $param_value) {
                // Пропускаем ненужные параметры
                if (in_array($param_key, $nocheck_params) or $param_key == 'name') {
                    continue;
                }
                // Параметр изменился, получаем данные и добавляем в массив
                if ($old_products[$item_id][$param_key] != $param_value) {
                    // Если проверяется актикул товара, получаем его значение
                    if ($param_key == 'sku_id') {
                        $old_skudata = $this->getModel('product_skus')->getSku($old_products[$item_id][$param_key]);
                        $new_skudata = $this->getModel('product_skus')->getSku($param_value);

                        $old_value = $old_skudata['name'];
                        $new_value = $new_skudata['name'];
                    }
                    // Если проверяется склад, получаем его название
                    elseif ($param_key == 'stock_id' && class_exists('shopStockModel')) {
                        $m_stock = new shopStockModel();
                        $old_value = $m_stock->select('name')
                                            ->where('id=?', $old_products[$item_id][$param_key])->fetchField();
                        $new_value = $m_stock->select('name')->where('id=?', $param_value)->fetchField();
                    }
                    // Если проверяется услуга, получаем ее название
                    elseif ($param_key == 'service_id') {
                        $old_value = $this->getModel('service')
                                            ->select('name')
                                            ->where('id=?', $old_products[$item_id][$param_key])
                                            ->fetchField();
                        $new_value = $this->getModel('service')
                                            ->select('name')
                                            ->where('id=?', $param_value)
                                            ->fetchField();
                    }
                    // Если проверяется вариант услуги, получаем его название
                    elseif ($param_key == 'service_variant_id') {
                        $old_value = $this->getModel('service')
                                            ->select('name')
                                            ->where('id=?', $old_products[$item_id][$param_key])
                                            ->fetchField();
                        $new_value = $this->getModel('service_variants')
                                            ->select('name')
                                            ->where('id=?', $param_value)
                                            ->fetchField();
                    } else {
                        $old_value = $old_products[$item_id][$param_key];
                        $new_value = $param_value;
                    }

                    // Добавляем данные
                    $edit_products_arr[$item_id]['params'][$param_key] = array(
                        'name' 	=> $this->getParamLabel($param_key),
                        'old' 	=> $old_value,
                        'new' 	=> $new_value
                    );

                    if (empty($edit_products_arr[$item_id]['name'])) {
                        $edit_products_arr[$item_id]['name'] = $product['name'];
                    }
                }
            }
        }
        // Возвращаем товары, которые были изменились
        return count($edit_products_arr) > 0 ? $edit_products_arr : null;
    }

    /**
     * Получаем изменения в данных покупателя
     * @return array массив с измененными данными
     */
    public function getEditClient()
    {
        if (empty($this->contact_old) || empty($this->contact_old)) {
            return false;
		}
		
        $old_contact = $this->contact_old;
        $new_contact = $this->contact_new;

        $old_params = $this->orderdata_old['params'];
		$new_params = $this->orderdata_new['params'];

        // Проверяем изменения в контактной информации
		$edit_client_arr = $this->compareArray($old_contact, $new_contact);

		// Параметры, которые не проверяем
		$nocheck_params = array(
			'id',
			'registered',
			'photo_50x50',
			'auth_code',
			'auth_pin',
			'payment_id',
			'payment_plugin',
			'shipping_id',
			'shipping_plugin',
			'shipping_rate_id',
			'ip',
			'landing',
			'referer_host',
			'storefront',
			'user_agent',
		);

        // Проверяем изменения параметров
        foreach ($new_params as $param_key => $param_value) {
            // Пропускаем параметры, которые не проверяем
            if (in_array($param_key, $nocheck_params)) {
                continue;
            }
            if (isset($old_params[$param_key]) && $old_params[$param_key] != $param_value) {
                // Если параметр страна
                if ($param_key == 'shipping_address.country') {
                    $m_country = new waCountryModel();

                    $old_value = $m_country->name($old_params[$param_key]);
                    $new_value = $m_country->name($param_value);
                }
                // Если параметр регион
                elseif ($param_key == 'shipping_address.region') {
                    $m_region = new waRegionModel();
                    $old_region = $m_region->get($old_params['shipping_address.country'], $old_params[$param_key]);
                    $new_region = $m_region->get($new_params['shipping_address.region'], $param_value);
                    $old_value = $old_region['name'];
                    $new_value = $new_region['name'];
                } else {
                    $old_value = $old_params[$param_key];
                    $new_value = $param_value;
                }

                $edit_client_arr[$param_key] = array(
                    'name' 	=> $this->getParamLabel($param_key),
                    'old'	=> $old_value,
                    'new'	=> $new_value,
                );
            }
        }

        return count($edit_client_arr) > 0 ? $edit_client_arr : null;
    }


    /**
     * Возвращает измененеия в данных заказа
     * @return array массив с измененными полями заказа
     */
    public function getEditOrder()
    {
        if (empty($this->orderdata_old) || empty($this->orderdata_old)) {
            return false;
        }
        $old_orderdata = $this->orderdata_old;
        $new_orderdata = $this->orderdata_new;
        // Параметры, которые не проверяем
        $nocheck_params = array(
            'id',
            'create_datetime',
            'update_datetime',
            'is_first',
            'unsettled',
            'params',
            'items',
            'contact',
        );
        // Проверяем изменные параметры
        $edit_order_arr = array();
        foreach ($new_orderdata as $param_key => $param_value) {
            // Пропускаем параметры, которые не проверяем
            if (in_array($param_key, $nocheck_params)) {
                continue;
            }
            if ($old_orderdata[$param_key] != $param_value) {
                $edit_order_arr[$param_key] = array(
                    'name'	=> $this->getParamLabel($param_key),
                    'old'	=> $old_orderdata[$param_key],
                    'new'	=> $param_value,
                );
            }
        }
        return count($edit_order_arr) > 0 ? $edit_order_arr : null;
    }

    /**
     * Модели для работы плагина в новой версии
     *
     * @param string $name
     * @return void
     */
    public function getModel($name = 'order')
    {
        if (!isset($this->models[$name])) {
            if ($name == 'product') {
                $this->models[$name] = new shopProductModel();
            } elseif ($name == 'product_skus') {
                $this->models[$name] = new shopProductSkusModel();
            } elseif ($name == 'product_stocks') {
                $this->models[$name] = new shopProductStocksModel();
            } elseif ($name == 'currency') {
                $this->models[$name] = new shopCurrencyModel();
            } elseif ($name == 'order_items') {
                $this->models[$name] = new shopOrderItemsModel();
            } elseif ($name == 'service') {
                $this->models[$name] = new shopServiceModel();
            } elseif ($name == 'service_variants') {
                $this->models[$name] = new shopServiceVariantsModel();
            } else {
                $this->models[$name] = new shopOrderModel();
            }
        }
        return $this->models[$name];
    }

    /**
     * Возвращает человеко-понятное название параметра
     *
     * @param [type] $param_key
     * @return void
     */
    private function getParamLabel($param_key)
    {
        return _wp($param_key);
    }
    
    /**
     * Производит сравнение 2х массивов
     *
     * @return array
     */
    private function compareArray($first_array, $second_array, $level_key = null)
    {
        if (!isset($diff_array)) {
            static $diff_array = [];
        }

        foreach ($second_array as $param_key => $second_value) {
            if (null !== $level_key && !is_numeric($param_key)) {
                $level_key = sprintf("%s.%s", $level_key, $param_key);
			} elseif(!is_numeric($param_key)) {
				$level_key = $param_key;
			}

            $firstValue = $first_array[$param_key];
            if (is_array($second_value)) {
                $this->compareArray($firstValue, $second_value, $level_key);
                continue;
            }

            if ($firstValue != $second_value) {
                $diff_array[$level_key] = array(
                    'name' => $this->getParamLabel($level_key),
                    'old'  => $firstValue,
                    'new'  => $second_value,
                );
			}
			
			$level_key = null;
        }

        return $diff_array;
    }
}
