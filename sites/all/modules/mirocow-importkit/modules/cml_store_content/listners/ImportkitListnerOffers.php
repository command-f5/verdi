<?php

class ImportkitListnerOffers extends ImportkitListner implements InterfaceObserver
{
	public $weight = 30;

	public function batch($argument, &$context)
	{
		$params = array(
			'argument' => $argument,
			'call_back' => 'create_offers',
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
		 '#title' => t('Offers settings'),
		 '#description' => t('The offers configuration settings'),
		 '#collapsible' => TRUE,
		 '#collapsed' => FALSE,
		);

		$importkit_content_module = variable_get('importkit_content_module_use', 'ubercart');
		$bundle = $this->getVariable('importkit_common_product_type', 'product');
		$fields = array();
		switch($importkit_content_module)
		{
			case 'commerce':

				// Привязка продукта к дисплею
				$fields = field_info_instances('node', $this->getVariable('importkit_content_node_type', 'product'));
				$referenceable_types = $options = array();
				$commerce_products = commerce_product_type_get_name();
				foreach ($fields as $field_name => $field)
				{
					if(isset($field['label']) && isset($field['settings']['referenceable_types']))
					{
						foreach ($field['settings']['referenceable_types'] as $field_type => $flag){
							if($field_type === $flag){
								$referenceable_types[ $field_type ] = $commerce_products[ $field_type ];
							}
						}
						$options[$field_name] = $field_name;
						break;

					}

				}

				$form[__CLASS__]['importkit_common_product_field_name'] = array(
					'#type' => 'select',
					'#title' => t('Field name'),
					'#options' => $options,
					'#default_value' => $this->getVariable('importkit_common_product_field_name', 'field_product'),
					'#weight' => 10,
					'#description' => t('The field name which tie with product'),
					'#suffix' => t('<sup>Current: @current</sup>', array('@current' => $this->getVariable('importkit_common_product_field_name', 'field_product')))
				);

				$form[__CLASS__]['importkit_common_product_type'] = array(
					'#type' => 'select',
					'#title' => t('Field type'),
					'#options' => $referenceable_types,
					'#default_value' => $this->getVariable('importkit_common_product_type', 'product'),
					'#weight' => 11,
					'#description' => t('The product type'),
					'#suffix' => t('<sup>Current: @current</sup>', array('@current' => $this->getVariable('importkit_common_product_type', 'product')))
				);

				$fields = field_info_instances('commerce_product', $bundle);

				// Image
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
					$form[__CLASS__]['importkit_product_image_field'] = array(
						'#type' => 'select',
						'#title' => t('Image field name of display'),
						'#options' => $options,
						'#default_value' => $this->getVariable('importkit_product_image_field', 'image'),
						'#weight' => 12,
						'#description' => t('The machine image field name'),
						'#suffix' => t('<sup>Current: @current</sup>', array('@current' => $this->getVariable('importkit_product_image_field', 'image')))
					);
				}
			break;
			case 'ubercart':

				if(module_exists('uc_attribute')){

					$form[__CLASS__]['importkit_content_features'] = array(
						'#type' => 'fieldset',
						'#title' => t('Uc attributes (import)'),
						'#description' => t('Uc attributes'),
						'#collapsible' => TRUE,
						'#collapsed' => FALSE,
					);

					$attributes = array();
					$rows = db_select('uc_attributes','a')
												->fields('a')
												->execute();
					foreach($rows as $row){
						$attributes[$row->aid] = $row;
					}

					if($attributes) {
						// Attributes fields
						$form[__CLASS__]['importkit_content_features'] = array(
							'#type' => 'fieldset',
							'#title' => t('Attributes'),
							'#description' => t('If you want to use attributes, insert the 1C properties name of "ХарактеристикиТовара" below..'),
							'#collapsible' => TRUE,
							'#collapsed' => FALSE,
						);
						foreach($attributes as $name => $attribute){
							//$attribute_name = preg_replace('~\s+~','',$attribute->name);
							$form[__CLASS__]['importkit_content_features']
								[ 'importkit_content_feature_' . $attribute->aid ] = array(
								'#type' => 'textfield',
								'#title' => $attribute->name . ' aid:' . $attribute->aid,
								'#description' => $attribute->label,
								'#default_value' => $this->getVariable('importkit_content_feature_' . $attribute->aid, ''),
							);
						}
					}
				}

			break;
		}

		// Fields API
		if(module_exists('field')){
			$all_fields = field_read_fields();
			if($all_fields){
				$attributes =  array();

				foreach($all_fields as $name => $field){
					if($name == 'taxonomy_catalog') continue;
					$instances = field_read_instances(array('field_id' => $field['id']));
					if(isset($instances[0]['bundle'])){
						switch($field['module'])
						{
							case 'taxonomy':
								switch($importkit_content_module)
								{
									case 'commerce':
										if(isset($instances[0]['commerce_cart_settings']['attribute_field'])){
											$instance = reset($instances);
											$instance['vocabulary_machine_name'] = $field['settings']['allowed_values'][0]['vocabulary'];
											$attributes[] = $instance;
											continue;
										}
									break;
								}
							break;
						}
					}
				}

				// ХарактеристикиТовара/ХарактеристикаТовара
				if($attributes){

					$form[__CLASS__]['importkit_content_features'] = array(
					 '#type' => 'fieldset',
					 '#title' => t('Attributes <ХарактеристикиТовара>'),
					 '#description' => t('The product attributes settings <ХарактеристикиТовара>'),
					 '#collapsible' => TRUE,
					 '#collapsed' => TRUE,
					 '#weight' => 20,
					);

					foreach($attributes as $attribute){
						if(isset($fields[ $attribute['field_name'] ])){
							$form[__CLASS__]['importkit_content_features']
								[ 'importkit_content_feature_' . $attribute['field_id'] ] = array(
								'#type' => 'textfield',
								'#size' => 50,
								'#title' => $attribute['label'],
								'#description' => t('The NAME of property') . '<br>' .
									l(t('Add attribute'), 'admin/structure/taxonomy/'.$attribute['vocabulary_machine_name'].'/add'),
								'#weight' => 30,
								'#default_value' =>  $this->getVariable('importkit_content_feature_' . $attribute['field_id'], ''),
							);
						}
					}
				}

			}
		}

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
				'Предложения',
				'products',
				'imported_offers',
				'remove_offer',
			)
			)) {
				 return $form;
			}

		$products = (int)$this->exeCallBack('products');

		$form[__CLASS__] = array(
			'#type' => 'fieldset',
			'#title' => 'Импорт товарных предложений',
			'#description' => '',
			'#collapsible' => TRUE,
			'#collapsed' => FALSE,
			'#weight' => 0,
		);

		$offers = (int)$this->exeCallBack('offers');
		if ($products && $offers) {

			$form[__CLASS__]['amount_offers'] = array(
				'#type' => 'item',
				'#title' => 'Импортировано товарных предложений: ' . $offers,
				'#description' => '',
			);

		}

		if (!$offers)
		{
			$form[__CLASS__]['importkit_offers'] = array(
				'#type' => 'checkboxes',
				'#title' => '',
				'#description' => '',
				'#default_value' => array('import' => 'import'),
				'#options' => array('import' => 'Импортировать товарные предложения'),
			);
		}
		elseif($products) {

			$options = array();

			switch(variable_get('importkit_content_module_use', 'ubercart'))
			{
				case 'commerce':
					$options['remove'] = 'Удалить товарные предложения';
				break;

				case 'ubercart':

				break;
			}

			$options['update'] = 'Обновить товарные предложения';
			$options['skip'] = 'Пропустить';

			$form[__CLASS__]['importkit_offers'] = array(
				'#type' => 'radios',
				'#title' => '',
				'#description' => '',
				'#default_value' => $this->getVariable('importkit_offers', 'update'),
				'#options' => $options,
			);

			$options = array();

			// TODO: Добавить флаги обновления
			//
			// Обновить наименование
			// Обновить описание
			// Обновить картинку (Ubercart)
			// Обновить харрактеристики товара (Ubercart: attributes)
			// Обновить все
		}

		return $form;
	}

	public function form_submit($form, &$form_state = array())
	{
		$form = array();

		if (isset($form_state['values'])) {

			// checkbox
			if (isset($form_state['values']['importkit_offers'])) {
				switch(@(string)$form_state['values']['importkit_offers']['import']){
					case 'import':
						$this->setJob('parse');
						$this->setParam('parser', 'ImportkitParserCML');
					break;
				}
			}

			// radio
			if (isset($form_state['values']['importkit_offers']) &&
				$form_state['values']['importkit_products'] != 'remove') {
				switch(@(string)$form_state['values']['importkit_offers']){
					case 'update':
						$this->setJob('parse');
						$this->setParam('parser', 'ImportkitParserCML');
					break;
					case 'remove':
						switch(variable_get('importkit_content_module_use', 'ubercart'))
						{
							case 'commerce':
								$imported = $this->exeCallBack('imported_offers', array('pid'));
								$this->setJob('remove_offers', $imported);
								$this->setParam('chunks', 100);
							break;

							case 'ubercart':
								// Нельзя удалить
							break;
						}
					break;
				}
			}

		}

		return $form;
	}

	public function parse($reader, $path, $ver, $created)
	{
		if ($reader->name == 'Предложения') {
			return $this->exeCallBack($reader->name, array($reader, $path, $ver, $created));
		}
	}

	public function finished($success, $results, $operations)
	{
		if($success && isset($results['method']) && $results['method'] == 'parse')
		{
			$keys = $this->exeCallBack('get_content_offers');
			if (isset($keys)) {
				// Регистрируем функцию обновления продукта
				$this->setJob('batch', $keys);
				$this->setParam('chunks', 25);
			}
		}
	}

	public function remove_offers($argument)
	{
		return $this->exeCallBack('remove_offer', $argument);
	}

	public function __toString()
	{
		return sprintf("class %s execute", __CLASS__);
	}
}
