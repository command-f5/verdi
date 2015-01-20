<?php

class ImportkitListnerCategory extends ImportkitListner implements InterfaceObserver
{
	public $weight = 10;

	public function batch($argument, &$context)
	{
	}

	public function settings_form($form_state = array(), $submit = false)
	{
		$form = array();

		$form[__CLASS__] = array(
			'#type' => 'fieldset',
			'#title' => t('Category settings'),
			//'#description' => t('Category settings'),
			'#collapsible' => TRUE,
			'#collapsed' => FALSE,
		);

				$options = array();

		// Выбор словаря для каталога
		$vocabularies = taxonomy_get_vocabularies();
		foreach ($vocabularies as $vocabulary)
			$options[$vocabulary->vid] = $vocabulary->name;

		$form[__CLASS__]['cml_store_category_vid'] = array(
			'#type' => 'select',
			'#title' => t('Product vocabulary'),
			'#options' => $options,
			'#default_value' => $this->getVariable('cml_store_category_vid', 1),
			'#weight' => 10,
			'#description' => t('The vocabulary of product set'),
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
				'Классификатор',
				'terms',
				'imported_terms',
				'remove_term',
			)
			)) {
				 return $form;
			}

		$form[__CLASS__] = array(
			'#type' => 'fieldset',
			'#title' => 'Импорт категорий',
			'#description' => '',
			'#collapsible' => TRUE,
			'#collapsed' => FALSE,
			'#weight' => 0,
		);

		$terms = (int)$this->exeCallBack('terms');
		if ($terms) {

			$form[__CLASS__]['amout_terms'] = array(
				'#type' => 'item',
				'#title' => 'Импортировано терминов: ' . $terms,
				'#description' => '',
			);

		}

		if (!$terms) {
			$form[__CLASS__]['importkit_terms'] = array(
				'#type' => 'checkboxes',
				'#title' => '',
				'#description' => '',
				'#default_value' => array(),
				'#options' => array('import' => 'Импортировать термины'),
			);
		} else {
			$form[__CLASS__]['importkit_terms'] = array(
				'#type' => 'radios',
				'#title' => '',
				'#description' => '',
				'#default_value' => $this->getVariable('importkit_terms', 'skip'),
				'#options' => array(
					'remove' => 'Удалить термины',
					'update' => 'Обновить термины',
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
			if (isset($form_state['values']['importkit_terms']['import'])) {
				switch(@(string)$form_state['values']['importkit_terms']['import']){
					case 'import':
						$this->setJob('parse');
						$this->setParam('parser', 'ImportkitParserCML');
					break;
				}
			}

			// radio
			if (isset($form_state['values']['importkit_terms'])) {
				switch(@(string)$form_state['values']['importkit_terms']){
					case 'update':
						$this->setJob('parse');
						$this->setParam('parser', 'ImportkitParserCML');
					break;
					case 'remove':
						$imported = $this->exeCallBack('imported_terms');
						$this->setJob('remove_terms', $imported);
					break;
				}
			}

		}

	}

	public function parse($reader, $path, $ver, $created)
	{
		if ($reader->name == 'Группы') {
			return $this->exeCallBack($reader->name, array($reader, $path, $ver, $created));
		}
	}

	public function finished($success, $results, $operations)
	{
	}

	public function remove_terms($argument)
	{
		return $this->exeCallBack('remove_term', $argument);
	}

	public function __toString()
	{
		return sprintf("class %s execute", __CLASS__);
	}

	//public function check
}
