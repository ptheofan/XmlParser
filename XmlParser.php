<?php
/**
 * Created by PhpStorm.
 * User: ptheofan
 * Date: 31/10/13
 * Time: 10:56
 */

class XmlParser {
    /**
     * @var array
     */
    public $handlersConfig;

    /**
     * @var resource
     */
    protected $parser = null;

    /**
     * @var string
     */
    protected $file;

    /**
     * Stack of elements (xPath)
     * @var array
     */
    private $xPath = array();

    /**
     * Stack of objects (handlers)
     * @var AbstractXmlParserHandler[]
     */
    protected $handlers = array();

    /**
     * @param $config
     */
    public function setHandlersConfig($config) {
        $this->handlersConfig = $config;
    }

    /**
     * @param $file
     * @throws Exception
     */
    public function setFile($file) {
        if ($this->parser)
            throw new Exception("Cannot change file while parsing");

        $this->file = $file;
    }

    /**\
     * @return string
     */
    public function getFile() {
        return $this->file;
    }

    /**
     * @param AbstractXmlParserHandler $handler
     */
    public function addHandler(AbstractXmlParserHandler $handler) {
        $this->handlers[] = $handler;
    }

    /**
     * @return AbstractXmlParserHandler
     */
    public function popHandler() {
        $rVal = $this->getHandler();
        array_pop($this->handlers);
        return $rVal;
    }

    /**
     * @return AbstractXmlParserHandler
     */
    public function getHandler() {
        return end($this->handlers);
    }

    /**
     * @return AbstractXmlParserHandler[]
     */
    public function getHandlers() {
        return $this->handlers;
    }

    /**
     * @return array
     */
    public function getCurrentElementStack() {
        return $this->xPath;
    }

    /**
     * @param $elementName
     * @return string
     */
    public function getFunctionName($elementName) {
        $pattern = array('/_(.?)/e', '/-(.?)/e', '/ (.?)/e');
        return preg_replace($pattern, 'strtoupper("$1")', strtolower($elementName)) . 'Handler';
    }

    /**
     * @param $parser
     * @param $elementName
     * @param $elementAttrs
     */
    public function startElementHandler($parser, $elementName, $elementAttrs) {
        // Callback function name
        $name = $this->getFunctionName($elementName);
        $functionName = 'open'.ucfirst($name);

        // Update XPath
        $this->xPath[] = $elementName;
        $compiledXPath = implode('.', $this->xPath);

        // Get the current handler
        $handler = $this->getHandler();

        // Try to match an entity
        foreach($this->handlersConfig as $path => $pathHandler) {
            if (strcasecmp($compiledXPath, $path) == 0) {
                $handler = $this->instantiate($pathHandler, $handler, $this->xPath);
                $this->addHandler($handler);
                $functionName = 'openHandler';
            }
        }

        // If no handler, element is not of interest... move on
        if (!$handler)
            return;

        if(method_exists($handler, $functionName)) {
            call_user_func(array($handler, $functionName), $elementName, $elementAttrs);
        } else {
            call_user_func(array($handler, 'openDefaultHandler'), $elementName, $elementAttrs);
        }
    }

    /**
     * @param $parser
     * @param $elementName
     * @throws Exception
     */
    public function endElementHandler($parser, $elementName) {
        // Callback function name
        $functionName = 'close'.ucfirst($this->getFunctionName($elementName));

        // XPath
        $compiledXPath = implode('.', $this->xPath);
        array_pop($this->xPath);

        // Get the current handler
        $handler = $this->getHandler();

        // Try to match an entity
        // TODO: Optimize
        foreach($this->handlersConfig as $path => $pathHandler) {
            if (strcasecmp($compiledXPath, $path) == 0) {
                $functionName = 'closeHandler';
                $handler = $this->popHandler();
            }
        }

        // If no handler, element is not of interest... move on
        if (!$handler)
            return;

        if(method_exists($handler, $functionName)) {
            call_user_func(array($handler, $functionName), $elementName);
        } else {
            call_user_func(array($handler, 'closeDefaultHandler'), $elementName);
        }

        unset($handler);
    }

    /**
     * @param $parser
     * @param $data
     * @throws Exception
     */
    public function characterDataHandler($parser, $data) {
        $elementName = end($this->xPath);
        $functionName = 'value' . ucfirst($this->getFunctionName($elementName));

        $compiledXPath = implode('.', $this->xPath);

        // Get Active handler
        $handler = $this->getHandler();

        // Try to match an entity
        // TODO: Optimize?
        foreach($this->handlersConfig as $path => $pathHandler) {
            if (strcasecmp($compiledXPath, $path) == 0) {
                $functionName = 'valueHandler';
            }
        }

        // If no handler, element is not of interest... move on
        if (!$handler)
            return;

        if(method_exists($handler, $functionName)) {
            call_user_func(array($handler, $functionName), $elementName, $data);
        } else {
            call_user_func(array($handler, 'valueDefaultHandler'), $elementName, $data);
        }
    }

    /**
     * @param string $file
     */
    public function __construct($file = null) {
        $this->file = $file;
    }

    /**
     * @param string $charset
     * @throws XmlParserErrorException
     * @throws Exception
     * @return bool
     */
    public function parse($charset = '') {
        $this->parser = xml_parser_create($charset);
        xml_set_element_handler($this->parser, array($this, 'startElementHandler'), array($this, 'endElementHandler'));
        xml_set_character_data_handler($this->parser, array($this, 'characterDataHandler'));

        $fh = fopen($this->file, "r");
        if (!$fh) {
            throw new Exception("XmlParser cannot open `$this->file` for read.");
        }

        while(!feof($fh)) {
            while (!feof($fh)) {
                $data = fread($fh, 4096);
                if (xml_parse($this->parser, $data, feof($fh)) == 0) {
                    throw new XmlParserErrorException($this->parser);
                }
            }
        }

        xml_parser_free($this->parser);
    }

    /**
     * @param $config
     * @return Object
     */
    public function instantiate($config) {
        if (is_array($config)) {
            $type = $config['class'];
            unset($config['class']);
        } else {
            $type = $config;
        }

        $args = func_num_args() > 1 ? func_get_args() : null;
        unset($args[0]);

        $class = new ReflectionClass($type);
        $object = call_user_func_array(array($class,'newInstance'),$args);

        if (is_array($config)) {
            foreach($config as $key => $value)
                $object->{$key} = $value;
        }

        return $object;
    }
} 