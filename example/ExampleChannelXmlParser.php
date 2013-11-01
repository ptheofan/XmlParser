<?php
/**
 * Created by PhpStorm.
 * User: ptheofan
 * Date: 01/11/13
 * Time: 22:13
 */

class ExampleChannelXmlParser extends AbstractXmlParserHandler {
    public $itemsCounter = 0;

    public function openHandler($elementName, $attrs) {
        parent::openHandler($elementName, $attrs);
        echo "\nChannel: ({$this->attrs['language']})\n";
    }

    public function valueTitleHandler($elementName, $value) {
        parent::valueDefaultHandler($elementName, $value);

        echo "Title: {$value}\n";
    }

    public function closeHandler($elementName) {
        parent::closeHandler($elementName);
        echo "\tTotal {$this->itemsCounter} items\n";
    }
} 