<?php


/**
 * @file
 * Drush commands for import files.
 */

/**
 * Implementation of hook_drush_command().
 * Sample: drush importer-import
 */
function importkit_drush_command()
{
	$items['importkit-import'] = array(
		'callback' => 'importkit_drush_import',
		'description' => 'Import products from CommerceML2 format.'
	);
	$items['importkit-update'] = array(
		'callback' => 'importkit_drush_update',
		'description' => 'Update imported products.'
	);
	$items['importkit-remove'] = array(
		'callback' => 'importkit_drush_remove',
		'description' => 'Remove all imported products.'
	);
	return $items;
}

/**
 * Implementation of hook_drush_help().
 */
function importkit_drush_help($section)
{
	switch ($section) {
		case 'drush:importkit-import':
			return t("Import products from CommerceML2 format using default settings.");
		case 'drush:importkit-update':
			return t("Update iported products from 1C files using default settings.");
		case 'drush:importkit-remove':
			return t("Remove all imported products.");
	}
}

/**
 * Import 1C files.
 */
function importkit_drush_import()
{
	// Полный импорт
	// $values = variable_get('imporkit_settings', array()),
	watchdog('test', '1');
	$values = array(
		'importkit_terms' => 'import',
		'importkit_products' => 'import',
		'importkit_offers' => 'import',
		'importkit_prices' => 'import',
		'importkit_stock' => 'import',
	);
	watchdog('test', '2');

	importkit_drush_run($values);
	watchdog('test', '3');

	return TRUE;
}

/**
 * Update imported products.
 */
function importkit_drush_update()
{
	// Обновление импортированных продуктов
	$values = array(
		'importkit_terms' => 'update',
		'importkit_products' => 'update',
		'importkit_offers' => 'update',
		'importkit_prices' => 'update',
		'importkit_stock' => 'update',
	);

	importkit_drush_run($values);

	return TRUE;
}

/**
 * Importkit tools of clear iported products.
 */
function importkit_drush_remove()
{
	// Удаление импортированных продуктов
	$values = array(
		'importkit_terms' => 'remove',
		'importkit_products' => 'remove',
		'importkit_offers' => 'remove',
		'importkit_prices' => 'remove',
		'importkit_stock' => 'remove',
	);

	importkit_drush_run($values);

	return TRUE;
}

function importkit_drush_run($values)
{
	$t = get_t();

	$form_state = array(
		'values' => $values,
		'submit_handlers' => array('importkit_form_submit'),
	);

	importkit_form_submit(NULL, $form_state, true);

	cache_set('importkit_progressive', TRUE);

	importkit_product_batch_set();

	return TRUE;
}