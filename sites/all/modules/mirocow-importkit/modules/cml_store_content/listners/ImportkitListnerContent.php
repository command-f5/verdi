<?php

class ImportkitListnerContent extends ImportkitListner implements InterfaceObserver
{
	public $weight = 20;

	public function batch($argument, &$context)
	{
		$params = array(
			'argument' => $argument,
			'call_back' => 'create_node',
			'options' => $this->getParams(),
			'context' => &$context
		);

		return $this->exeCallBack('batch', $params);
	}

	public function settings_form($form_state = array(), $submit = false)
	{
		$form = array();

		$form[__CLASS__] = array(
		 '#type' => 'fieldset',
		 '#title' => t('Content settings (@type)', array('@type' => $this->getVariable('importkit_content_node_type', 'product'))),
		 '#description' => t('The content configuration settings'),
		 '#collapsible' => TRUE,
		 '#collapsed' => FALSE,
		);

		$options = array();

		// Node API
		$types = node_type_get_types();
		foreach ($types as $type)
		{
			$options[$type->type] = $type->name;
		}
		$form[__CLASS__]['importkit_content_node_type'] = array(
			'#type' => 'select',
			'#title' => t('Content type'),
			'#options' => $options,
			'#default_value' => $this->getVariable('importkit_content_node_type', 'product'),
			'#weight' => 10,
			'#description' => t('The content type for import set'),
			'#suffix' => t('<sup>Current: @current</sup>', array('@current' => $this->getVariable('importkit_content_node_type', 'product')))
		);

		if(function_exists('mb_substr')){

			$form[__CLASS__]['importkit_display_title_field_length'] = array(
				'#type' => 'textfield',
				'#size' => 50,
				'#title' => t('Title length'),
				'#description' => t('The length of title'),
				'#weight' => 5,
				'#default_value' => $this->getVariable('importkit_display_title_field_length', 120),
			);

		}

		// Fields API
		if(module_exists('field')){
			$all_fields = field_read_fields();
			if($all_fields){
				$options = array();

				foreach($all_fields as $name => $field){
					if($name == 'taxonomy_catalog') continue;
					$instances = field_read_instances(array('field_id' => $field['id']));
					if(isset($instances[0]['bundle'])){
						switch($field['module'])
						{
							case 'taxonomy':
								$options[ $instances[0]['field_name'] ] = $instances[0]['label'] . " ({$instances[0]['field_name']})";
							break;
						}
					}
				}

				$form[__CLASS__]['importkit_content_category_field'] = array(
					'#type' => 'select',
					'#title' => t('Category field'),
					'#options' => $options,
					'#default_value' => $this->getVariable('importkit_content_category_field', 'field_category'),
					'#weight' => 10,
					'#description' => t('The content type for import set'),
				);

			}
		}

		// Image
		$bundle = $this->getVariable('importkit_content_node_type', 'product');
		$fields = field_info_instances('node', $bundle);
		$options = array();
		foreach ($fields as $field_name => $field)
		{
			if(isset($field['label']) &&
				isset($field['settings']['file_extensions']) &&
				preg_match('~(png|gif|jpg|jpeg)~i', $field['settings']['file_extensions']))
			{
				$options[$field_name] = $field['label'];
			}
		}
		if($options)
		{
			$form[__CLASS__]['importkit_display_image_field'] = array(
				'#type' => 'select',
				'#title' => t('Image field name of display'),
				'#options' => $options,
				'#default_value' => $this->getVariable('importkit_display_image_field', 'image'),
				'#weight' => 12,
				'#description' => t('The machine image field name'),
				'#suffix' => t('<sup>Current: @current</sup>', array('@current' => $this->getVariable('importkit_display_image_field', 'image')))
			);
		}

		// Description
		$form[__CLASS__]['importkit_display_description_field'] = array(
			'#type' => 'select',
			'#title' => t('Image field name of display'),
			'#options' => array(
				'Описание',
				'ОписаниеВФорматеHTML',
			),
			'#default_value' => $this->getVariable('importkit_display_description_field', 0),
			'#weight' => 13,
			'#description' => t('The machine image field name'),
		);

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
				'Товары',
				'products',
				'imported_products',
				'remove_product',
			)
			)) {
				 return $form;
			}

		$form[__CLASS__] = array(
			'#type' => 'fieldset',
			'#title' => 'Импорт продуктов',
			'#description' => '',
			'#collapsible' => TRUE,
			'#collapsed' => FALSE,
			'#weight' => 0,
		);

		$products = (int) $this->exeCallBack('products');
		if ($products) {

			$form[__CLASS__]['amount_products'] = array(
				'#type' => 'item',
				'#title' => 'Импортировано продуктов: ' . $products,
				'#description' => '',
			);

		}

		if (!$products) {
			$form[__CLASS__]['importkit_products'] = array(
				'#type' => 'checkboxes',
				'#title' => '',
				'#description' => '',
				'#default_value' => array('import' => 'import'),
				'#options' => array('import' => 'Импортировать продукты'),
			);
		} else {
			$form[__CLASS__]['importkit_products'] = array(
				'#type' => 'radios',
				'#title' => '',
				'#description' => '',
				'#default_value' => $this->getVariable('importkit_products', 'skip'),
				'#options' => array(
					'remove' => 'Удалить продукты',
					'update' => 'Обновить продукты',
					'skip' => 'Пропустить'
					),
			);
		}

		return $form;
	}

	public function form_submit($form, &$form_state = array())
	{
		if (isset($form_state['values'])) {

			// checkbox
			if (isset($form_state['values']['importkit_products']['import'])) {
				switch(@(string)$form_state['values']['importkit_products']['import']){
					case 'import':
						$this->setJob('parse');
						$this->setParam('parser', 'ImportkitParserCML');
					break;
				}
			}

			// radio
			if (isset($form_state['values']['importkit_products'])) {
				switch(@(string)$form_state['values']['importkit_products']){
					case 'update':
						$this->setJob('parse');
						$this->setParam('parser', 'ImportkitParserCML');
					break;
					case 'remove':
						$imported = $this->exeCallBack('imported_products', array('pid'));
						$this->setJob('remove_products', $imported);
						$this->setParam('chunks', 100);
					break;
				}
			}

		}
	}

	public function parse($reader, $path, $ver, $created)
	{
		if ($reader->name == 'Товары') {
			return $this->exeCallBack($reader->name, array($reader, $path, $ver, $created));
		}
	}

	public function finished($success, $results, $operations)
	{
		if($success && isset($results['method']) && $results['method'] == 'parse')
		{
			$keys = $this->exeCallBack('get_content_products');
			if (isset($keys)) {
				// Регистрируем функцию создания продукта
				$this->setJob('batch', $keys);
				$this->setParam('chunks', 10);
				return true;
			}
		}

		return false;
	}

	public function remove_products($argument)
	{
		return $this->exeCallBack('remove_product', $argument);
	}

	public function __toString()
	{
		return sprintf("class %s execute", __CLASS__);
	}
}
