<?php
/**
 * Created by PhpStorm.
 * User: ptheofan
 * Date: 01/11/13
 * Time: 23:16
 */

class ExampleItemXmlParser extends AbstractXmlParserHandler {
    public function openHandler($elementName, $attrs) {
        parent::openHandler($elementName, $attrs);

        $this->parent->itemsCounter++;
    }

    public function closeHandler($elementName) {
        parent::closeHandler($elementName);

        $title = $this->getChild('title');
        $titleValue = substr($title->value, 0, 80);
        echo "\titem: " . ($titleValue == $title->value ? $titleValue : $titleValue.'...') . "\n";
    }
} 