<?php
/**
 * Created by PhpStorm.
 * User: ptheofan
 * Date: 31/10/13
 * Time: 20:43
 */

class XmlParserErrorException extends Exception {
    protected $errorInformation;

    public function __construct($parser, Exception $previous = null) {
        $this->errorInformation = array(
            'errorCode' => xml_get_error_code(parser),
            'errorString' => xml_error_string(xml_get_error_code(parser)),
            'line' => xml_get_current_line_number(parser),
            'column' => xml_get_current_column_number(parser),
            'byteIdx' => xml_get_current_byte_index(parser),
        );

        parent::__construct($this->errorInformation['errorString'], $this->errorInformation['errorCode'], $previous);
    }
} 