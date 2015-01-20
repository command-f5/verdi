<?php
/**
 * @file
 * Ответчик запросов из 1С при выгрузке товаров на сайт
 *
 * 1С посылает серию запросов с аргументами 'type' и 'mode'
 *
 * обычно запросы группированы по type - сначала все типа "catalog", потом все типа "order"
 * порядок внутри одного типа запросов (виды mode):
 * 1. checkauth - проверка связи.
 */

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

define('ROOT',substr(dirname(__FILE__),0,-3));
define("DEBUG_MODE", 0); // для отладки установим в 1

// логин / пароль, передаваемый с каждым запросом из 1С (HTTP AUTH Basic)
define ("CML_USER_LOGIN", 'admin');
define ("CML_USER_PASS", 'Fvh%ZqG3');

// куда складывать принятые файлы
define ("CML_IMPORT_FILES_DIR", ROOT.'/sites/default/files/cml/');
define ("CML_TEMP_FILES_DIR", 'temp');

// домен
$pu = parse_url($_SERVER['REQUEST_URI']);
$dir = dirname($pu["path"]);
if ($dir == '\\' || $dir =='/') {
    $dir = '';
}
$drupal_domain = "http://" . $_SERVER['HTTP_HOST'];

// адрес формы, с которой формируются батчи
define ("DRUPAL_IMPORTKIT_FORM_ACTION", $drupal_domain.'/admin/importkit');

// пользователь, зарегистрированный в Drupal, от чьего имени осуществляется импорт.
define ("SCRIPT_LOGIN", "admin");
define ("SCRIPT_PASSWORD", "Fvh%ZqG3");

// адреса для авторизациии и проверки, прошла ли авторизация
define ("DRUPAL_LOGIN_URL", $drupal_domain."/user/login");
define ("LOGGED_REDIRECTION", $drupal_domain."/users/admin");
define ("DRUPAL_AUTHORIZED_URL", $drupal_domain."/admin");

require_once 'logger.php.inc';
require_once '1c_exchange_funcs.php.inc';

init_logger("1Creq");
log_event("-------------------------------------------------------------------------------------------------------","DEBUG");

//log_event("GET: \n".print_r($_GET, true), "DEBUG");

// Если этот флаг выставлен, то к концу скрипта будет произведена очистка сессии -
//      удаление файла cookie и файла параметров, временной директории...
$failure_flag = true;

// получим данные из запроса
$type = isset($_GET['type']) ? $_GET['type']: null;
$mode = isset($_GET['mode']) ? $_GET['mode']: null;
$filename = isset($_GET['filename']) ? $_GET['filename']: null;
$userlogin = isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER']: null;
$userpassword = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW']: null;

if (DEBUG_MODE == 1) {
    $pfile = 'datafile.txt';
} else {
// получим имя файла с параметрами
    $pfile = isset($_COOKIE['pfile']) ? $_COOKIE['pfile'] : null;
}

// пустой / отсутствующий файл параметров допустим только при инициации сеанса связи
if (empty($pfile) && $mode!='checkauth') {
    print ("failure");
    log_event("Empty params file name - cookie lost?", "DEBUG");
    return;
}

// загрузка параметров
$params = load_params($pfile);

// авторизация - может быть несколько подряд перед началом собственно передачи (логин)
if ($type == 'catalog' && $mode == 'checkauth') {
    mode_checkauth();
    return;
}

// инициализация (запрос формы importkit)
if ($type == 'catalog' && $mode == 'init') {
    // создадим временную директорию для приёма файлов. нужна новая пустая папка, т.к. файлы могут приходить
    // частями - и нельзя обнаружить, первая часть пришла с текущим запросом или последняя.
    $tempdir = tempdir(CML_TEMP_FILES_DIR);
    if (empty($tempdir)) {
        log_event("Can't create temp directory", "DEBUG");
        print ("failure");
        return;
    }
    $params['tempdir'] = $tempdir;

    // Удалим import.xml и offers.xml
    @unlink(CML_IMPORT_FILES_DIR.'/import.xml');
    @unlink(CML_IMPORT_FILES_DIR.'/offers.xml');
	// и import_files
	@r_rmdir(CML_IMPORT_FILES_DIR.'/import_files');
	
    //$importkitFormData = extract_form_data($out); // идентификаторы формы / экземпляра формы.
	$importkitFormData = array();
    $params = array_merge($params, $importkitFormData);
    file_put_contents($pfile, serialize($params));
	
	
    print ("zip=no\n");
    print ("file_limit=".(1024*1024)."\n");
    log_event("Request processed successfully", "DEBUG");
    return;
}

// передача файлов (инициализация батчей)
if ($type == 'catalog' && $mode == 'file' && !empty($filename)) {
    if (DEBUG_MODE != 1) {
        $write_result = write_transferred_data_to_file($filename, $params['tempdir']);
		
        if ($write_result <= 0) {
            print ("failure\n");
            print ("Unspecified file system error on writing $filename\n");
            log_event("Can't write a file $filename. \$write_result=$write_result","DEBUG");
            return;
        }
		log_event("file  writed","DEBUG");
    }
	
	// сразу посылаем на юг
		print ("success\n");
		return;
}

// команды импорта (прогрес батчей)
if ($type == 'catalog' && $mode == 'import') {
    if ($params==null) {
        print ("failure\n");
        print ("Lost a cookie with params file\n");
        return;
    }
	
    // Если есть скачанные файлы, то
    // переносим файлы в рабочую директорию для импорта и удаляем временную директорию
    $sourceDir = isset($params['tempdir']) ? $params['tempdir'] : NULL;
    if (!empty($sourceDir)) {
        $targetDir = CML_IMPORT_FILES_DIR;
        log_event("trying rmove $sourceDir to $targetDir","DEBUG");
        rmove($sourceDir, $targetDir);
        if (is_dir($sourceDir)) {
            print ("failure\n");
            print ("Can't move from a temporary directory\n");
            log_event("Can't rmove $sourceDir to $targetDir","DEBUG");
            return;
        }
        r_rmdir($params['tempdir']);
        unset($params['tempdir']);
    }
	
	
	// сразу посылаем на юг
		print ("success\n");
		return;
}

// ----------------------------------------------------
// Завершение операций.
if ($failure_flag) {
    // удаляем файл параметров
    if (!empty($pfile)) {
        unlink($pfile);
    }

    // удаляем временную папку, если есть
    if (!empty($params['tempdir'])) {
        r_rmdir($params['tempdir']);
    }
	
	log_event("Unlinked temps ","DEBUG");
}
?>
