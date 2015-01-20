<?php

class ImportkitListnerStock extends ImportkitListner implements InterfaceObserver
{
	public $weight = 50;

	public function batch($argument, &$context)
	{
		$params = array(
			'argument' => $argument,
			'options' => $this->getParams(),
			'context' => &$context
		);
		return $this->exeCallBack('stock_batch', $params);
	}

	public function settings_form($form_state = array(), $submit = false)
	{
		//global $user;

		$form = array();
		return $form;
	}

	public function settings_form_submit($form, &$form_state = array())
	{
		$form = array();
		return $form;
	}

	public function form($form_state = array(), $submit = false)
	{
		$form = array();

		if(!$this->callbackExists(
			array(
				'stock',
				'offers',
				'products',
				'imported_offers',
			)
			)) {
				 return $form;
			}

		$products = (int)$this->exeCallBack('products');
		$offers = (int)$this->exeCallBack('offers');
		if(!($offers || $products))
		{

			$form['importkit_stock'] = array(
				'#type' => 'hidden',
				'#value' => array('import' => 'import'),
			);

			return $form;
		}

		$form[__CLASS__] = array(
		 '#type' => 'fieldset',
		 '#title' => 'Импорт позиций по остаткам',
		 //'#description' => t('The stock update configuration settings'),
		 '#collapsible' => TRUE,
		 '#collapsed' => FALSE,
		);

		$stock = (int)$this->exeCallBack('stock');
		if ($products && $stock) {

			$form[__CLASS__]['amount_stock'] = array(
				'#type' => 'item',
				'#title' => 'Импортировано позиций по остаткам: ' . $stock,
				'#description' => '',
			);

		}

		if (!$stock) {

			switch(variable_get('importkit_content_module_use', 'ubercart'))
			{
				case 'commerce':
					if(module_exists('commerce_ss')){
						$form[__CLASS__]['importkit_stock'] = array(
							'#type' => 'checkboxes',
							'#title' => '',
							'#description' => '',
							'#default_value' => array('import' => 'import'),
							'#options' => array('import' => 'Импортировать информацию по остаткам'),
						);
					} else {
						$form[__CLASS__]['amout_terms'] = array(
							'#type' => 'item',
							'#title' => t('Не установлен модуль !module',
								array('!module' => l('commerce_ss', 'https://drupal.org/project/commerce_stock', array('absolute' => TRUE)))),
							'#description' => '',
						);
					}
				break;

				case 'ubercart':
					if(module_exists('uc_stock')){
						$form[__CLASS__]['importkit_stock'] = array(
							'#type' => 'checkboxes',
							'#title' => '',
							'#description' => '',
							'#default_value' => array('import' => 'import'),
							'#options' => array('import' => 'Импортировать информацию по остаткам'),
						);
					} else {
						$form[__CLASS__]['amout_terms'] = array(
							'#type' => 'item',
							'#title' => 'Не установлен модуль uc_stock',
							'#description' => '',
						);
					}
				break;
			}

		} elseif($products) {

			$form[__CLASS__]['importkit_stock'] = array(
				'#type' => 'radios',
				'#title' => '',
				'#description' => '',
				'#default_value' => $this->getVariable('importkit_stock', 'update'),
				'#options' => array(
					//'remove' => 'Удалить информацию по остаткам',
					'update' => 'Обновить информацию по остаткам',
					'skip' => 'Пропустить'
					),
			);

		}

		return $form;
	}

	public function prepaire_batch(){
	}

	public function import(){
	}

	public function update(){
	}

	public function form_submit($form, &$form_state = array())
	{
		$form = array();

		if (isset($form_state['values'])) {

			// checkbox
			if (isset($form_state['values']['importkit_stock'])) {
				switch(@(string)$form_state['values']['importkit_stock']['import']){
					case 'import':
						$this->setJob('import');
					break;
				}
			}

			// radio
			if (isset($form_state['values']['importkit_stock']) &&
				$form_state['values']['importkit_products'] != 'remove') {
				switch(@(string)$form_state['values']['importkit_stock']){
					case 'update':
						$this->setJob('update');
					break;
					case 'remove':
						//$imported = $this->exeCallBack('imported_stock', array());
						//$this->setJob('remove_stock', $imported);
					break;
				}
			}

		}

		return $form;
	}

	public function parse($reader, $path, $ver, $created)
	{
	}

	public function finished($success, $results, $operations)
	{
		if($success && isset($results['method']) && $results['method'] == 'import')
		{
			// Регистрируем функцию-обработчик после выполнеия импорта контента
			$this->setJob('prepaire_batch', $results['method']);
		}

		if($success && isset($results['method']) && $results['method'] == 'update')
		{
			// Регистрируем функцию-обработчик после выполнеия импорта контента
			$this->setJob('prepaire_batch', $results['method']);
		}

		if($results['method'] == 'prepaire_batch')
		{
			// Регистрируем обработчик для Обработки offers
			$imported = $this->exeCallBack('imported_offers', array('fields' => array('nid','pid','guid1', 'guid2')));
			$this->setJob('batch', $imported);
			$this->setParam('chunks', 100);
		}

	}

	public function __toString()
	{
		return sprintf("class %s execute", __CLASS__);
	}
}
