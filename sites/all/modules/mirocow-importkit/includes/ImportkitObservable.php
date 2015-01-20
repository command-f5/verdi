<?php

class ImportkitObservable implements InterfaceSubject, IteratorAggregate, Countable
{

	protected static $_instance;

	protected $index = 0;

	private $_observers = array();

	//private $_settings = array();

	private $_import = array();

	private $_export = array();

	private $_result = array();

	private $_errors = array();

	private $_jobs = array();

	public static function getInstance($listners = 1)
	{
		if (self::$_instance == null) {
			self::$_instance = new self($listners);
		}
		return self::$_instance;
	}

	private function __clone()
	{
		// Запрещено
	}

	protected function __construct($listners = 1)
	{
		$this->_observers = new SplFixedArray($listners);
	}

	/**
	 * Возвращает все пойманные ошибки
	 *
	 */
	public function getErrors()
	{
		return $this->_errors;
	}

	public function getJobs()
	{
		return $this->_jobs;
	}

	/**
	 * Вывод последней ошибки слушателя
	 *
	 * @param mixed $listner
	 */
	public function getErrorsByListner($listner)
	{
		$errors = array();
		$className = get_class($listner);
		$last_error = cache_get('importkit_errors_' . $className);
		if($className && $last_error)
		{
			$errors[] = t('Errors in class %class:', array('%class' => $className));
			$errors = array_merge($errors,$last_error->data);
		}
				return  $errors;
	}

	/*public static function factory($listener, array $args = array())
	{
		try {
			$reflect = new \ReflectionClass($listener);
			return $reflect->newInstanceArgs($args);
		} catch (\ReflectionException $e) {
			// no implementation yet
		}
	}*/

	public static function resetInstance($andRecreate = false)
	{
		self::$_instance = null;
		return $andRecreate ? self::getInstance() : null;
	}

	/**
	 * Регистрация слушателя
	 *
	 * @param InterfaceObserver $ob
	 * @return ImportkitObservable
	 */
	public function attach(InterfaceObserver $ob)
	{
		$this->_observers[$this->index] = $ob;
		$this->index++;
		return $this;
	}

	/**
	 * Удаление слушателя
	 *
	 * @param InterfaceObserver $ob
	 */
	public function detach(InterfaceObserver $ob)
	{
		foreach($this as $i => $obj)
		{
			if($obj === $ob){
				unset($this->_observers[$i]);
			}
		}
	}

	/**
	 * Оповещение слушателей
	 *
	 * @param mixed $options
	 */
	public function notify($options = array())
	{
		foreach ($this as $listner) {
			if ($listner instanceof $options['class']) {
				if (isset($options['method'])) {
					$methodName = $options['method'];
					$className = $options['class'];
					$params = isset($options['params']) ? $options['params'] : array();
					try {
						$method = new ReflectionMethod($listner, $methodName);
						$listner->setParams($options);

						// Очистка выполненых задач
						$listner->clearJobs();

						// Запуск обработчика
						if ($result = $method->invokeArgs($listner, $params)) {
							$call_back = $className . '__' . $methodName;
							$this->_result[$call_back] = (bool)$result;
						}

						// Установка полученных задач
						if($jobs = $listner->getJobs()){
							$this->_jobs[ $className ] = $jobs;
						}

					} catch (Exception $e) {
						$error = $e->getMessage();
						watchdog('importkit', $error, array(), WATCHDOG_ERROR);
						$key = md5($error);
						if(!isset($this->_errors[ $className ][ $key ]))
						{
							$this->_errors[ $className ][ $key ] = $error;
						}
					}
				}
			}
		}
	}

	/**
	 * Используется для перебора foreach()
	 *
	 */
	public function getIterator()
	{
		// Преобразуем в массив
		$listners = $this->_observers->toArray();

		// Сортируем в зависимости от веса слушателя
		usort($listners, array($this, 'cmp'));

		// Преобразуем в SplFixedArray
		$this->_observers = $this->_observers->fromArray($listners, true);

		return $this->_observers;
	}

	/**
	 * Сортирует слушателей по весу
	 *
	 * @param mixed $a
	 * @param mixed $b
	 */
	public function cmp($a, $b)
	{
		return $a->weight > $b->weight? 1: 0;
	}

	/**
	 * Возвращает кол-во зарегистрированных слушателей
	 *
	 */
	public function count()
	{
		return count($this->_observers);
	}

	/*public function __call($funct, $args)
	{
		if (preg_match('#(?P<prefix>at|de)tach(?P<listener>\w+)#', $funct, $matches)) {
			$meth     = $matches['prefix'] . 'tach';
			$listener = ucfirst(strtolower($matches['listener']));
			return $this->$meth(self::factory($listener, $args));
		}
		throw new BadMethodCallException("unknown method $funct");
	}*/

	public function getSettings()
	{

	}

	/**
	 * Установка файлов импорта
	 *
	 * @param mixed $name
	 * @param mixed $path
	 */
	public function setImport($name, $path)
	{
		$this->_import[$name] = $path;
	}

	/**
	 * Установка файлов экспорта
	 *
	 * @param mixed $name
	 * @param mixed $path
	 */
	public function setExport($name, $path)
	{
		$this->_export[$name] = $path;
	}

	/**
	 * Запускает обработчик методов листнеров
	 *
	 * @param mixed $options
	 */
	public function run($options)
	{
		if (!isset($options['method'])) {
			$this->_errors[] = 'Не найден обработчик';
			return false;
		}

		// Выбор типа обработчика
		switch ($options['method']) {
			case 'parse':
				try{
					if (!isset($options['parser'])) continue;

					$parser = $options['parser']::getInstance()
						->setOptions($options)
						->setCallBack(array($this, 'notify'));

					foreach ($this->_import as $name => $path) {
						$parser->setPath($path)->parse();
					}

					unset($parser);
				} catch (Exception $e) {
					$this->_errors['common'][ ] = $e->getMessage();
				}
				break;

			default:
				$this->notify($options);
				break;
		}

		return $this->_result;
	}

}
