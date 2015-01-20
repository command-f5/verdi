<?php
/**
 * @file
 * Функции ведения лога.
 * Необходимо использовать "require_once" при подключении этого файла
 */
define('MAX_LOG_SIZE', 5*1024*1024);

$filehandles = array(); // все открытые хэндлы логов

/**
 * Инициализация логгера, проверяет размер файла и обрезает по необходимости.
 * @param  string $logname 	- имя файла для хранения логов
 * @return boolean			- true, если успешно, иначе false.
 */
function init_logger($logname) {
	global $logfile;

	if (empty($logname))
		return false;

    date_default_timezone_set("Europe/Moscow"); // логировать во времени по Москве

	$logfile = $logname.'.log';

	if (file_exists($logfile) && (filesize($logfile) > MAX_LOG_SIZE)) {
		$tmpfile = tempnam(__DIF__,'_');
		$logHandle = fopen($logfile, 'r');
		$tmpHandle = fopen($tmpfile, 'w');
		if (!$tmpHandle || !$logHandle)
			return false; //какая-то ошибка. :(

		// обрезаем до половины максимальной, чтобы не вызывать слишком часто
		fseek($logHandle, - MAX_LOG_SIZE * 0.5, SEEK_END);

		stream_copy_to_stream($logHandle, $tmpHandle);
		fclose($logHandle);
		fclose($tmpHandle);

		rename($tmpfile, $logfile);
		//unlink($tmpfile);
		chmod($logfile, 0644);
	}

	return true;
}
/**
 * Записать сообщение в файл и на экран (html вывод). Имя файла определяется глобальной переменной $logfile или передаётся аргументом
 * @param  string $message текст сообщения.
 * @param  string $level   "уровень тревожности" сообщения. Если $level и $message - оба пустые,
 * то будет выведена в файл пустая строка для декорации. Если $level == "DEBUG", то вывод на экран будет пропущен.
 * @param  string $file    имя файла. Если null, то используется глобальная переменная $logfile
 */
function log_event($message, $level="INFO", $file=null) {

	//открываем нужный файл
	global $filehandles;
	global $logfile;

	$logName = $file;
	if ($logName == null) $logName = $logfile;
	if ($logName == null)
		return; // без логирования, если файл лога не задан.

	$filename = __DIR__.DIRECTORY_SEPARATOR.$logName;
	if (isset($filehandles[$filename])) {
		$handle = $filehandles[$filename];
	} else {
		$handle = fopen($filename, "at");
		$filehandles[$filename] = $handle;
	}

	if ($handle === false || $handle === null)
		return false;

	//теперь пишем сообщение.

	//пустая строка в лог-файл
	if (empty($message) && empty($level)) {
		fwrite($handle, "\r\n");
		return;
	}

	$dateTime = new DateTime(); // время на часах хоста
	$sDate = $dateTime->format("[d M Y H:i:s]");

	$line = $sDate." [$level] ".$message."\r\n";
	fwrite($handle, $line);
	if ($level != "DEBUG")
		echo $line."<br>";
}
?>