<?php

class ImportkitParserCML extends ImportkitXMLReader
{

    public static function getInstance()
    {
        if (self::$_instance == null) {
            self::$_instance = new self;
        }
        return self::$_instance;
    }

    public function parse()
    {
        $ver = '';
        $created = '';
        $path = $this->getPath();
        $this->open($path);
        while (!$this->stop() && $this->read()) {
            // Поиск версии обрабатываемого файла
            if ($this->name == 'КоммерческаяИнформация') {
                $ver = $this->getAttribute('ВерсияСхемы');
                $created = $this->getAttribute('ДатаФормирования');
            }
            $params = array(
                // Объект XMLReader поставляется в модуль обработчик
                'reader' => $this,
                // Путь до обрабатываемого файла
                'path' => $path,
                // Версия схемы
                'ver' => $ver,
                // Дата создания
                'created' => $created,
            );
            $this->setOption('params', array_merge($params, $this->getOption('params')));
            $this->exeCallBack();
        }
        $this->close();
    }
}
