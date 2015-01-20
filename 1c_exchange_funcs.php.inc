<?php
/**
 * @file
 * Вспомогательные функции для импорта товаров в 1С
 */


/**
 * Осуществляет вход в Drupal как пользователь, имеющий права на импорт.
 * Создаёт файл для хранения временных данных (эта сессия от 1С) c префиксом "1С_"
 * Сохраняет в этот файл переменную "cookie" - куки сессии
 */
function mode_checkauth() {
    global $type, $mode;
    log_event("$type:$mode", "DEBUG");
    $login_results = login_to_drupal();
    log_event("login ".($login_results? "success": "failed"), "DEBUG");
    if ($login_results) {
        if (DEBUG_MODE == 1) {
            global $pfile;
        } else {
            $pfile = tempnam(CML_TEMP_FILES_DIR, '1C_');
        }
        chmod($pfile, 0777);
        log_event ("temp file is $pfile", "DEBUG");
        $params = array (
            'cookie' => $login_results,
        );

        file_put_contents($pfile, serialize($params));
        print ("success\n");
        print ("pfile\n");
        print ("$pfile\n");
        log_event("printed success", "DEBUG");
        // $z = ob_get_flush();
        // log_event("screen: \n".$z,"DEBUG");
        return;
    } else {
        print "failure";
        return;
    }
}

/**
 * Создать временную директорию
 * @param  string  $dir    где
 * @param  string  $prefix префикс
 * @param  integer $mode   права
 * @return string          что получилось. null, если не удалось.
 * @author Ron Korving php.net/manual/en/function.tempnam.php#61436
 */
function tempdir($dir, $prefix='', $mode=0700) {
    if (substr($dir, -1) != '/') $dir .= '/';
    $tries = 100;

    do {
        $path = $dir.$prefix.mt_rand(0, 9999999);
    } while (!mkdir($path, $mode) && $tries--);

    return ($tries >=0) ? $path : null;
}

/**
 * Загрузить параметры сессии обмена с 1С из файла
 * @param  string $pfile - имя файла
 * @return array         - набор сохранённых параметров или null
 */
function load_params($pfile) {
    if (empty($pfile) || !file_exists($pfile) || !is_readable($pfile)) {
        log_event("Params file does not exist or is not accessible [$pfile]", "DEBUG");
        return null;
    }

    $fileContents = file_get_contents($pfile);
    $params = unserialize($fileContents);
    log_event("Restored params: ".print_r($params, TRUE), "DEBUG");
    return $params;
}

/**
 * Выделяет из HTML кода страницы формы отправки запроса на обновление/импорт (importkit) данные.
 * @param  string $sDocument - HTML документ
 * @param  string $encoding  - исходная кодировка документа (НЕ РЕАЛИЗОВАНО - необходимо utf-8)
 * @return array             - параметры формы (токен, информация import или update)
 */
function extract_form_data($sDocument, $encoding=NULL) {
    // 1. transcode to UTF-8
    if (!empty($encoding) && strtolower($encoding)!="utf-8") {
        //TODO
    }

    // 2. create dom document
    $doc = new DOMDocument();
    $doc->loadHTML($sDocument);

    // 3. XPath
    $xpath = new DOMXPath($doc);

    $form_token =               get_attr_by_xpath('//form[@id="importkit-form"]//input[@name="form_token"]/@value', $xpath);
    $form_build_id =            get_attr_by_xpath('//form[@id="importkit-form"]//input[@name="form_build_id"]/@value', $xpath);
    $form_terms_input_type =    get_attr_by_xpath('//form[@id="importkit-form"]//div[@id="edit-importkit-terms"]/@class', $xpath);
    $form_products_input_type = get_attr_by_xpath('//form[@id="importkit-form"]//div[@id="edit-importkit-products"]/@class', $xpath);
    $form_offers_input_type =   get_attr_by_xpath('//form[@id="importkit-form"]//div[@id="edit-importkit-offers"]/@class', $xpath);
    $form_prices_input_type =   get_attr_by_xpath('//form[@id="importkit-form"]//div[@id="edit-importkit-prices"]/@class', $xpath);

    return array (
        'form_token' => $form_token,
        'form_build_id' => $form_build_id,
        'terms_exist' => $form_terms_input_type=='form-radios',
        'products_exist' => $form_products_input_type=='form-radios',
        'offers_exist' => $form_offers_input_type=='form-radios',
        'prices_exist' => $form_prices_input_type=='form-radios',
    );
}

/**
 * Получить значение атрибута по пути
 * @param  string $path          - путь
 * @param  DOMXPath $xpathObject - объект XPath, который вычисляет путь
 * @return string                - полученный атрибут (строка), или null, если произошла ошибка.
 */
function get_attr_by_xpath($path, $xpathObject) {
    $results = $xpathObject->evaluate($path);

    if (is_a($results, 'DOMNodeList') && $results->length) {
        $domObject = $results->item(0);
        return (is_a($domObject, 'DOMAttr'))? $domObject->value : null;
    } else
        return null;
}

/**
 * Записать файл, полученный от 1C
 * @param  string $filename - имя файла (может содержать путь)
 * @param  string $dirname  - имя корневой директории, без закрывающего слэша
 * @return int              - число реально записанных байтов.
 */
function write_transferred_data_to_file($filename, $dirname='.') {
    log_event("writing `$filename` to `$dirname`", "DEBUG");
    if ((strpos($filename, './') !== false) ||
        (strpos($filename, '.\\') !== false)) {
        log_event("[$filename] contains ./ or .\\", "DEBUG");
        return 0; // some hackers will fail here.
    }

    $filename = $dirname.'/'.$filename;
    log_event("full name is $filename", "DEBUG");
    // Если имя файла содержить путь кроме базового - убедимся, что путь существует
    if (dirname($filename) != '.' && dirname($filename)!=$dirname) {
        log_event ("Dir ".dirname($filename)." will be created", "DEBUG");
        $d = dirname($filename);
        if (is_file($d))
            unlink($d); // убить файл, чтобы на его месте создать папку.
        if (!file_exists($d))
            mkdir($d, 0777, TRUE);
    }

    $dest = fopen($filename, 'a');
    $src = fopen("php://input", 'r');
    if ($dest === FALSE)
        log_event('CANT open outp file', "DEBUG");
    if ($src === FALSE)
        log_event('CANT open inp file', "DEBUG");

    $write_result = stream_copy_to_stream($src, $dest);
    fclose($src);
    fclose($dest);
    return $write_result;
}

/**
 * Выделить из ответа сервера номер сгенерированного батча
 * @param  string $serverResponse - ответ сервера, должен содержать заголовки
 * @return string/bool            - номер батча или
 *                                  true, если редирект происходит на страницу DRUPAL_IMPORTKIT_FORM_ACTION (конец батча),
 *                                  false иначе (редирект на другую страницу или ошибка)
 */
function parse_relocation_for_batch($serverResponse) {
    preg_match_all('/Location: (.*)/', $serverResponse, $matched);
    $location = trim(implode(';', $matched[1]));
    if (empty($location)) {
        log_event("Can't find redirection location", "DEBUG");
        return FALSE;
    }

    log_event("Redirected to [$location]", "DEBUG");
    if ($location == DRUPAL_IMPORTKIT_FORM_ACTION) {
        return TRUE;
    }

    $locationData = parse_url($location);
    parse_str($locationData['query'], $locQuery);

    if ($locationData['path'] == '/batch' && is_numeric($locQuery['id'])) {
        return $locQuery['id'];
    }

    log_event ('strange relocation detected', "DEBUG");
    return FALSE;
}

/**
 * Прогоняет итерацию с батчем.
 * @param  int $batchN - номер батча
 * @return int/bool    - если булев тип, то обозначает успешность пакета операций (true - всё закончилось хорошо)
 *                       если целое, то это номер батча, который надо выполнить в следующей итерации (может совпадать
 *                       со входным номером, может быть новым)
 */
function tick_batch($batchN) {
    global $params, $pr_pr, $drupal_domain;
    $link_do = "$drupal_domain/index.php?q=batch&op=do&id=$batchN";
    $link_finish = "$drupal_domain/index.php?q=batch&op=finished&id=$batchN";
    //http://kstest.acerbicdev.info/batch?op=start&id=381
    //http://kstest.acerbicdev.info/batch?op=do&id=381
    //http://kstest.acerbicdev.info/batch?op=finished&id=381

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_COOKIE, $params['cookie']);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_URL, $link_do);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, '');
    curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:30.0) Gecko/20100101 Firefox/30.0");

    // curl_setopt($curl, CURLOPT_VERBOSE, true);
    // curl_setopt($curl, CURLINFO_HEADER_OUT, true);
    // curl_setopt($curl, CURLOPT_HEADER, true);

    // хотя на самом деле в этом случае посылается GET и прикладывается op=do_nojs к URL и ловится
    // <meta content="0; URL=/batch?id=522&op=finished" http-equiv="Refresh"> или
    // <meta content="0; URL=/batch?id=523&op=do_nojs" http-equiv="Refresh"> в возвращаемом результате,
    // а POST генерится только сайтом с включённым Javascript и has_js=1 посылается аргументом POST

    log_event("About to post a request to: $link_do", "DEBUG");
    $out  = curl_exec($curl);
    $code = curl_getinfo($curl,CURLINFO_HTTP_CODE);
    if ($code != 200) {
        log_event("POST op=do failed. Returned $code code and `$out`", "DEBUG");
        // log_event("HEADERS SENT: \n".curl_getinfo($curl,CURLINFO_HEADER_OUT), "DEBUG");
        // log_event("INFO DOWN: \n".curl_getinfo($curl,CURLINFO_CONTENT_LENGTH_DOWNLOAD), "DEBUG");
        // log_event("INFO UP: \n".curl_getinfo($curl,CURLINFO_CONTENT_LENGTH_UPLOAD), "DEBUG");
        curl_close($curl);
        return false;
    }
    curl_close($curl);

    $progress = json_decode($out);
    log_event("Progress reported: ".$progress->percentage, "DEBUG");
    if (!isset($progress->percentage)) {
        log_event("Whoops.", "DEBUG");
        return false;
    }
    $pr_pr = $progress->percentage;
    if ($progress->percentage == 100) {
        log_event("Trying to finish the batch", "DEBUG");
        // заканчиваем обработку этого батча и определяем, может быть требуется перейти к следующему.
        //http://kstest.acerbicdev.info/batch?op=finished&id=381
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_COOKIE, $params['cookie']);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_URL, $link_finish);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, '');
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:30.0) Gecko/20100101 Firefox/30.0");

        $out  = curl_exec($curl);
        $code = curl_getinfo($curl,CURLINFO_HTTP_CODE);

        if ($code != 302) {
            log_event("Recieved code $code when finishing batch $batchN.", "DEBUG");
            return false;
        }

        $newBatchN = parse_relocation_for_batch($out);
        log_event("The new batchN is $newBatchN", "DEBUG");
        return is_bool($newBatchN)? $newBatchN: (int)$newBatchN;
    } else {
        // продолжаем выполнять этот же батч на следующем запросе.
        return $batchN;
    }
}

/**
 * Recursively move files from one directory to another
 *
 * @param String $src - Source of files being moved
 * @param String $dest - Destination of files being moved
 * @author Ben Lobaugh @ http://ben.lobaugh.net/blog/864/php-5-recursively-move-or-copy-files
 */
function rmove($src, $dest){

// If source is not a directory stop processing
    if(!is_dir($src)) return false;

// If the destination directory does not exist create it
    if(!is_dir($dest)) {
        if(!mkdir($dest)) {
// If the destination directory could not be created stop processing
            return false;
        }
    }

// Open the source directory to read in files
    $i = new DirectoryIterator($src);
    foreach($i as $f) {
        if($f->isFile()) {
            rename($f->getRealPath(), "$dest/" . $f->getFilename());
        } else if(!$f->isDot() && $f->isDir()) {
            rmove($f->getRealPath(), "$dest/$f");
            @rmdir($f->getRealPath());
        }
    }
    rmdir($src);
}

/**
 * Рекурсивно удалить папку и все файлы в ней. Основано на скрипте 'rmove' выше.
 * @param  String $dir - папка на удаление
 */
function r_rmdir($dir) {
    if(!is_dir($dir)) return false;

// Open the source directory to read in files
    $i = new DirectoryIterator($dir);
    foreach($i as $f) {
        if($f->isFile()) {
            unlink($f->getRealPath());
        } else if(!$f->isDot() && $f->isDir()) {
            r_rmdir($f->getRealPath());
            rmdir($f->getRealPath());
        }
    }
    rmdir($dir);
}

/**
 * Текстовое преобразование между кодировками
 * @param  string $from    исходная кодировка
 * @param  string $into    целевая кодировка
 * @param  string $content текст
 * @return string          результат
 * @author Дмитрий 'mirocow' Морозов (http://mirocow.com/)
 */
function importkit_convert($from = "UTF-8", $into = "WINDOWS-1251", $content) {

    if (strtoupper($from) == strtoupper($into)) return $content;
    if (function_exists("iconv")) {
        $content = iconv($from, $into, $content);
    } elseif (function_exists("libiconv")) {
        $content = libiconv($from, $into, $content);
    } elseif (function_exists("mb_convert_encoding")) {
        $content = mb_convert_encoding($content, $into, $from);
    }
    return $content;
}

/**
 * Пытается авторизироваться в Drupal используя SCRIPT_LOGIN и SCRIPT_PASSWORD
 * @param  string $cookie - набор печенек для продолжения банкета или null
 * @return string         - печеньки при успешной авторизации или null, если не успешно.
 */
function login_to_drupal($cookie=NULL) {
    if (check_logged($cookie))
        return $cookie;

    // аргументы для формы логина
    $post_arguments = array (
        "form_id" => "user_login",
        "name" => SCRIPT_LOGIN,
        "op" => "Log in",
        "pass" => SCRIPT_PASSWORD,
    );

    log_event("trying to log with params ".print_r($post_arguments, TRUE), "DEBUG");
    log_event("trying to log with params ".print_r(http_build_query($post_arguments), TRUE), "DEBUG");
    log_event("using url ".DRUPAL_LOGIN_URL, "DEBUG");


    $curl = curl_init();
    curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl,CURLOPT_URL, DRUPAL_LOGIN_URL);
    curl_setopt($curl,CURLOPT_HEADER, true);
    curl_setopt($curl,CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:30.0) Gecko/20100101 Firefox/30.0");

    curl_setopt($curl,CURLOPT_POSTFIELDS, http_build_query($post_arguments));

    // отсылаем POST логина
    $out = curl_exec($curl);
    $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    log_event("login_to_drupal login form submit return code = $code", "DEBUG");

    // Вытаскиваем куки и редирект
    preg_match_all('|Set-Cookie: (.*);|U', $out, $results);
    $cookies = implode(';', $results[1]);
    preg_match_all('/Location: (.*)/', $out, $results);
    $location = trim(implode(';', $results[1]));
    if ($code == 302)
        log_event("Redirected to [$location]", "DEBUG");

    if (($code == 200 && check_logged($cookies)) ||
        ($code == 302 && $location == LOGGED_REDIRECTION)) {
        return $cookies;
    }

    return null;
}

/**
 * Проверяет, авторизован ли скрипт в Drupal
 * @param  string $cookie - набор печенек, который используется при проверке
 * @return bool           - true если печеньки авторизованы
 */
function check_logged($cookie) {

    // пытаемся зайти в админку
    $curl = curl_init();
    curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl,CURLOPT_URL, DRUPAL_AUTHORIZED_URL);
    curl_setopt($curl,CURLOPT_NOBODY, true);
    curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:30.0) Gecko/20100101 Firefox/30.0");
    if ($cookie)
        curl_setopt($curl,CURLOPT_COOKIE, $cookie);

    $out = curl_exec($curl);
    $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    log_event("check_logged code: $code", "DEBUG");
    return ($code == 200);
}
?>