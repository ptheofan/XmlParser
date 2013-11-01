XmlParser
=========

PHP XmlParser for HUGE xml documents
This XML parser will allow you to easily traverse through huge XML documents that normaly don't fit in the memory (or just don't want to abuse the server resources)
Check example.php to see how it works.

In short, for any element of interest create a class that extends the AbstractXmlParserHandler and add it in the XmlParser XPath mapping configuration
