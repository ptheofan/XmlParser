<?php
include('AbstractXmlParserHandler.php');
include('XmlParserErrorException.php');
include('XmlParser.php');

include('example/ExampleChannelXmlParser.php');
include('example/ExampleItemXmlParser.php');

$xmlFile = 'rss.xml';

$parser = new XmlParser($xmlFile);
$parser->setHandlersConfig(array(
    'rss.channel' => 'ExampleChannelXmlParser',
    'rss.channel.item' => 'ExampleItemXmlParser',
));

$parser->parse();
