<?php

class ImportkitExampleParser implements InterfaceReader {

	static $_instance;

	private $_options = array();

	private $_call_back = array();

	private $_path = '';

	private $_stop = FALSE;

	public static function getInstance()
	{
		if (self::$_instance == null) {
			self::$_instance = new self;
		}
		return self::$_instance;
	}

	protected function __clone()
	{
	}

	protected function __construct()
	{
	}

	public function setOptions($options)
	{
		$this->_options = $options;
		return $this;
	}

	public function getOptions()
	{
		return $this->_options;
	}

	protected function setOption($name, $value)
	{
		$this->_options[$name] = $value;
		return $this;
	}

	protected function getOption($name = '')
	{
		if(isset($this->_options[$name]))
		{
			return $this->_options[$name];
		}
		 else {
			return FALSE;
		}
	}

	public function parse()
	{

	}

}
