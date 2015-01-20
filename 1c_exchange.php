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
//ob_start();
define("DEBUG_MODE", 0); // для отладки установим в 1

// логин / пароль, передаваемый с каждым запросом из 1С (HTTP AUTH Basic)
define ("CML_USER_LOGIN", 'admin');
define ("CML_USER_PASS", 'Fvh%ZqG3');

// куда складывать принятые файлы
define ("CML_IMPORT_FILES_DIR", 'sites/default/files/cml');
define ("CML_TEMP_FILES_DIR", 'sites/default/files/cmltemp');

// домен
$pu = parse_url($_SERVER['REQUEST_URI']);
$dir = dirname($pu["path"]);
if ($dir == '\\' || $dir =='/') {
    $dir = '';
}
$drupal_domain = "http://" . $_SERVER['HTTP_HOST'] . $dir;
// define ("DRUPAL_DOMAIN", "http://banana");
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

log_event("GET: \n".print_r($_GET, true), "DEBUG");

// Если этот флаг выставлен, то к концу скрипта будет произведена очистка сессии -
//      удаление файла cookie и файла параметров, временной директории...
$failure_flag = false;

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

// проверка пользователя и пароля
    if ($userlogin != CML_USER_LOGIN || $userpassword != CML_USER_PASS) {
        print ("failure\n");
        log_event("Request auth failed", "DEBUG");
        return;
    }
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
    // ob_start();
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

    // получим страницу формы импорта из importkit
    log_event("about to get importkit import form page [".DRUPAL_IMPORTKIT_FORM_ACTION."]", "DEBUG");
    $curl = curl_init();
    curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl,CURLOPT_URL, DRUPAL_IMPORTKIT_FORM_ACTION);
    curl_setopt($curl,CURLOPT_COOKIE, $params['cookie']);
    curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:30.0) Gecko/20100101 Firefox/30.0");

    $out = curl_exec($curl);
    $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($code!=200) {
        log_event("Can't get import form: code $code", "DEBUG");
        print ("failure");
        return;
    }

    $importkitFormData = extract_form_data($out); // идентификаторы формы / экземпляра формы.
    $params = array_merge($params, $importkitFormData);
    file_put_contents($pfile, serialize($params));

    print ("zip=no\n");
    print ("file_limit=".(1024*1024)."\n");
    log_event("Request processed successfully", "DEBUG");
    // log_event("buffer: >>>" . ob_get_contents() . "<<<", "DEBUG");
    // ob_end_flush();
    return;
}

// передача файлов (инициализация батчей)
if ($type == 'catalog' && $mode == 'file' && !empty($filename)) {
    if (DEBUG_MODE != 1) {
        $write_result = write_transferred_data_to_file($filename, $params['tempdir']);

        if ($write_result <= 0) {
            print ("failure\n");
            print ("Unspecified file system error on writing $filename\n");
            log_event("Can't write a file. \$write_result=$write_result","DEBUG");
            return;
        }
    }

    // создадим пакетные задачи, если нужно.
    $post_args = array (
        "form_id" => "importkit_form",
        "op" => "Отправить",
    );
    if ($params['form_build_id'])
        $post_args["form_build_id"] = $params['form_build_id'];
    if ($params['form_token'])
        $post_args["form_token"] = $params['form_token'];

    if ($filename=='import.xml') {
        if ($params['terms_exist'])
            $post_args["importkit_terms"] = "update";
        else
            $post_args["importkit_terms[import]"] = "import";

        if ($params['products_exist'])
            $post_args["importkit_products"] = "update";
        else
            $post_args["importkit_products[import]"] = "import";

        if ($params['offers_exist'])
            $post_args['importkit_offers'] = 'skip';

        if ($params['prices_exist'])
            $post_args['importkit_prices'] = 'skip';

    } elseif ($filename=='offers.xml') {
        // понадеемся, что offers.xml всегда следует ПОСЛЕ import.xml
        if ($params['offers_exist']) {
            $normalUpdate = array (
                "importkit_terms" =>    "skip",
                "importkit_products" => "update",
                "importkit_offers" =>   "update",
                "importkit_prices" =>   "update",
                "importkit_stock[#import]" => "import",
            );
            $post_args = array_merge($post_args, $normalUpdate);
        } else {
            // когда ничего не импортировано, всё пусто и лишь бесплотный importkit летает над гладью Drupal-а
            $importOffers = array (
                "importkit_offers[import]" => "import",
                "importkit_prices" => "import",
                "importkit_stock[import]" => "import",
            );
            $post_args = array_merge($post_args, $importOffers);
        }
    } else {
        // any other file does not require batch creation
        print ("success\n");
        return;
    }

    // постим форму с данными для создания пакетов
    log_event("about to submit a form to [".DRUPAL_IMPORTKIT_FORM_ACTION."] with POST arguments: ".print_r($post_args, TRUE), "DEBUG");
    $curl = curl_init();
    curl_setopt($curl,CURLOPT_REFERER, DRUPAL_IMPORTKIT_FORM_ACTION);
    curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl,CURLOPT_URL, DRUPAL_IMPORTKIT_FORM_ACTION);
    curl_setopt($curl,CURLOPT_COOKIE, $params['cookie']);
    curl_setopt($curl,CURLOPT_HEADER, true); // <-- хотим поймать редирект с номером батча
    curl_setopt($curl,CURLOPT_NOBODY, true);
    curl_setopt($curl,CURLOPT_POST, true);
    curl_setopt($curl,CURLOPT_POSTFIELDS, http_build_query($post_args));
    curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:30.0) Gecko/20100101 Firefox/30.0");


    $out = curl_exec($curl);
    $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $headersOut = curl_getinfo($curl, CURLINFO_HEADER_OUT);
    curl_close($curl);

    log_event("Trying to create a batch for $filename. Post submit returned code $code","DEBUG");
    if ($code == 200) {
        file_put_contents("1C_impkit_form_response", $out);
        print ("failure\n");
        return;
    }
    if ($code == 302) {
        // получаем адрес редиректа
        $batchN = parse_relocation_for_batch($out);
        if (!is_bool($batchN) && empty($batchN)) {
            print ("failure\n");
            print ("did not aquire a batch number for import\n");
            log_event("can't establish batch number", "DEBUG");
            return;
        }

        log_event("выделен номер бачта! Всем радоваться! $batchN", "DEBUG");
        $params[$filename."_batch_number"] = $batchN;
        file_put_contents($pfile, serialize($params));
        print ("success\n");
        return;
    } else {
        print ("failure\n");
        log_event("Batch creation form returned a strange HTTP code: $code", "DEBUG");
        return;
    }
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

    // Батчи прогрессируют только для следующих файлов.
    if (($filename=='import.xml') ||
        ($filename=='offers.xml')) {

        $batchN = $params[$filename."_batch_number"];
        if (empty($batchN)) {
            print ("failure\n");
            log_event("Lost batch N", "DEBUG");
            return;
        }

        $result = tick_batch($batchN);
        if (is_bool($result)) {
            print ($result? "success\n": "failure\n");
            return;
        } else {
            log_event("tick_batch($batchN) returned: ".print_r($result, TRUE),"DEBUG");
            $params[$filename."_batch_number"] = $result;
            file_put_contents($pfile, serialize($params));
            print ("progress\n");
            print ("$pr_pr"); // глобальная
            return;
        }
    } else {
        print ("failure\n");
        print ("What the file!?: $filename\n");
        log_event("unusual file is proposed for import: $filename", "DEBUG");
        return;
    }
}

// // авторизация этапа заказов
// if ($type == 'sale' && $mode == 'checkauth') {
//     /* TODO remove prev pfile? */
//     mode_checkauth();
// }

// // инициализация этапа обмена заказами
// if ($type == 'sale' && $mode == 'init') {
//     print ("zip=no\n");
//     print ("file_limit=".(1024*1024)."\n");
//     return;
// }

// // запрос заказов ОТ сайта для 1С
// if ($type == 'sale' && $mode == 'query') {
//  /* Сайт передает сведения о заказах в формате CommerceML 2. */

//     // получить список файлов типа order_X.xml
//     $files = scandir(CML_IMPORT_FILES_DIR);
//     $fnOrders = array();
//     foreach ($files as $filename) {
//         $matches = array();
//         if (preg_match('~order_(\d+)\.xml~i', $filename, $matches)) {
//             $fnOrders[$matches[1]] = $filename;
//         }
//     }
//     log_event("Found ".count($fnOrders)." order files", "DEBUG");

//     // выделить все внутренние документы из файлов в массив документов
//     $domDocs = array();
//     foreach ($fnOrders as $orderNumber => $filename) {
//         $domOrder = new DOMDocument();
//         $domOrder->load(CML_IMPORT_FILES_DIR."/$filename");

//         if (!$domOrder)
//             continue;

//         $xpath = new DOMXPath($domOrder);
//         $domOrderDocs = $xpath->evaluate("//Документ");

//         if (is_a($domOrderDocs, 'DOMNodeList') && $domOrderDocs->length) {
//             foreach ($domOrderDocs as $domDoc)  {
//                 $domDocs[] = $domDoc;
//             }
//         }
//     }

//     // собрать документы в одну структуру XML (КоммерческаяИнформация)
//     $timechange = time();
/*     $no_spaces = '<?xml version="1.0" encoding="windows-1251"?>
    <КоммерческаяИнформация ВерсияСхемы="2.05"
        ДатаФормирования="' . date ( 'Y-m-d', $timechange ) . 'T' . date ( 'H:m:s', $timechange ) . '"
        ФорматДаты="ДФ=yyyy-MM-dd; ДЛФ=DT"
        ФорматВремени="ДФ=ЧЧ:мм:сс; ДЛФ=T"
        РазделительДатаВремя="T"
        ФорматСуммы="ЧЦ=18; ЧДЦ=2; ЧРД=."
        ФорматКоличества="ЧЦ=18; ЧДЦ=2; ЧРД=.">
    </КоммерческаяИнформация>';*/

//     // 2. create dom document
//     $domFullOrder = new DOMDocument();
//     // $domFullOrder = new DOMDocument('1.0', 'WINDOWS-1251');
//     $domFullOrder->loadXML(importkit_convert('UTF-8', 'WINDOWS-1251', $no_spaces));

//     $commercialInfo = $domFullOrder->getElementsByTagName("КоммерческаяИнформация")->item(0);

//     foreach ($domDocs as $domDoc) {
//         $node = $domFullOrder->importNode($domDoc, TRUE);
//         try {
//             $commercialInfo->appendChild($node);
//         } catch (Exception $e) {
//             print ("failure\n");
//             log_event('Caught exception: '.$e->getMessage(), "DEBUG");
//             return;
//         }
//     }

//     // сохранить полный документ с заказами в строку в строку
//     $sFinaldoc = $domFullOrder->saveXML();
//     file_put_contents('fullOrder.xml', $sFinaldoc);
//     log_event("Saved all docs into fullOrder.xml (size = ".count($sFinaldoc).")", "DEBUG");

//     // вернуть строку, как ответ для 1С
//     print($sFinaldoc);
//     // $params['documents_sent'] = (int) $params['documents_sent'] + count($domDocs);

//     $params['orders_sent'] = $fnOrders;

//     // TODO ???? Перенести в $type == 'sale' && $mode == 'file' ?
//     // Удалить файлы заказов, как обработанные.
//     foreach ($fnOrders as $filename) {

//         // FIXME
//         rename(CML_TEMP_FILES_DIR."/$filename", CML_TEMP_FILES_DIR."/_$filename");
//         //unlink(CML_TEMP_FILES_DIR."/$filename");
//     }
//     file_put_contents($pfile, serialize($params));
//     return;
// }

// /* В случае успешного получения и записи заказов "1С:Предприятие" передает на сайт запрос */
// if ($type == 'sale' && $mode == 'success') {
//     // TODO ???? Запомнить успешность в параметрах?

//     $params['sales_export_success'] = TRUE;
//     file_put_contents($pfile, serialize($params));

//     print("success\n");
//     return;
// }

// if ($type == 'sale' && $mode == 'file' && !empty($filename)) {
//     // TODO ???? Проверить успешность экспорта заказов и если да - удалить обработанные файлы заказов.


//     if (empty($params['tempdir'])) {
//         $tempdir = tempdir(CML_TEMP_FILES_DIR);
//         if (empty($tempdir)) {
//             log_event("failed to create a temporary directory", "DEBUG");
//             print ("failure\n");
//             return;
//         }// ^_*
//         $params['tempdir'] = $tempdir;
//     }

//     if (DEBUG_MODE != 1) {
//         $write_result = write_transferred_data_to_file($filename, $params['tempdir']);

//         if ($write_result <= 0) {
//             print ("failure\n");
//             print ("Unspecified file system error on writing $filename\n");
//             return;
//         }
//     }
//     //TODO
//     // получить файл, V check
//     // провести файл... хм...


//     //FIXME
//     // print "success\n";
//     return;
// }


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
}
?>
