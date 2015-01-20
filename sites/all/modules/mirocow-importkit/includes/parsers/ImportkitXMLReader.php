<?php

abstract class ImportkitXMLReader extends XMLReader implements InterfaceReader {

    static $_instance;

    private $_options = array();

    private $_call_back = array();

    private $_path = '';

    private $_stop = FALSE;

    public static function getInstance()
    {
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

    public function setCallBack($call_back)
    {
        $this->_call_back = $call_back;
        return $this;
    }

    public function getCallBack()
    {
        return $this->_call_back;
    }

    public function setPath($path)
    {
        $this->_path = $path;
        return $this;
    }

    public function getPath()
    {
        return $this->_path;
    }

    public function parse()
    {

    }

    public function stop($status = null)
    {
        if(!is_null($status))
        {
            $this->_stop = $status;
        }
         else
        {
            return $this->_stop;
        }
    }

    protected function exeCallBack()
    {
        if(is_callable($this->getCallBack())){
            call_user_func_array($this->getCallBack(), array($this->getOptions()));
        }
    }

}
