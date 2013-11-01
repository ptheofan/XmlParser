<?php
/**
 * Created by PhpStorm.
 * User: ptheofan
 * Date: 01/11/13
 * Time: 01:27
 */

class Element {
    public $attrs = array();
    public $name;
    public $value;

    /**
     * @var Element[]
     */
    public $children = array();

    /**
     * @var Element
     */
    public $parent = null;

    /**
     * @return array
     */
    public function getXPath() {
        $path = array();
        if ($this->parent instanceof Element)
            $path = $this->parent->getXPath();

        $path[] = $this->name;

        return $path;
    }

    /**
     * @return string
     */
    public function getXPathAsString() {
        return strtolower(implode('.', $this->getXPath()));
    }

    /**
     * TODO: should search based on XPath or partial XPath
     * This will not look in depth. Just on first level.
     * @param $name
     * @return Element|false
     */
    public function getChild($name) {
        foreach($this->children as $child)
            if ($child->name == $name)
                return $child;

        return false;
    }
}

abstract class AbstractXmlParserHandler extends Element {
    /**
     * Use with caution. This will block the parent node from
     * being released and can cause memory exhaustion if
     * this element has too many siblings
     * @var bool
     */
    public $makeParentAware = false;

    /**
     * @var Element
     */
    protected $currentElement = null;

    /**
     * @param $elementName
     * @param $attrs
     * @throws Exception
     */
    public function openDefaultHandler($elementName, $attrs) {
//        if ($this->currentElement !== null)
//            die;
//            throw new Exception("XML parsing error, opening new element `{$elementName}` whilst existing element `{$this->currentElement->name}` has not yet closed");

        $elem = new Element();
        $elem->name = strtolower($elementName);
        $elem->attrs = array_combine(array_map('strtolower', array_keys($attrs)), array_values($attrs));
        $elem->parent = $this->currentElement;

        if ($this->currentElement)
            $this->currentElement->children[] = $elem;

        $this->currentElement = $elem;
    }

    /**
     * @param $elementName
     * @throws Exception
     */
    public function closeDefaultHandler($elementName) {
//        if ($elementName !== $this->currentElement->name)
//            throw new Exception("XML parsing error, closing element `{$elementName}` when expecting element `{$this->currentElement->name}`");

        $this->currentElement = $this->currentElement->parent;
    }

    /**
     * @param $elementName
     * @param $value
     * @throws Exception
     */
    public function valueDefaultHandler($elementName, $value) {
//        if ($elementName !== $this->currentElement->name)
//            throw new Exception("XML parsing error, assigning value to element `{$elementName}` when expecting element `{$this->currentElement->name}`");

        if ($this->currentElement->value)
            $this->currentElement->value .= $value;
        else
            $this->currentElement->value = $value;
    }

    /**
     * Self open handler
     * @param $elementName
     * @param $attrs
     */
    public function openHandler($elementName, $attrs) {
        $this->name = strtolower($elementName);
        $this->attrs = array_combine(array_map('strtolower', array_keys($attrs)), array_values($attrs));
    }

    /**
     * Self value handler
     * @param $elementName
     * @param $value
     */
    public function valueHandler($elementName, $value) {
        if ($this->value)
            $this->value .= $value;
        else
            $this->value = $value;
    }

    /**
     * @param $elementName
     */
    public function closeHandler($elementName) {
        // Dereference the parent
        $this->parent = null;
    }


    /**
     * @param AbstractXmlParserHandler $parent
     * @param $xPath
     */
    public function __construct($parent, $xPath) {
        $this->parent = $parent;
        if ($this->makeParentAware)
            $this->parent->children[] = $this;

        $this->xPath = $xPath;
        $this->currentElement = $this;
    }
} 