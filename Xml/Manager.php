<?php
/**
 * Simple XML <-> PHP converter
 *
 * @author edubskiy
 * @example
 * Xml_Manager::Create()->CreateElements($array_of_elements)
 */
class Xml_Manager
{
    protected $optionCaseFolding = false;
    protected $optionSkipWhite = true;

    public $parsedData = array();
    public $parsedDataIndexes = array();

    public $xmlVersion = '1.0';
    public $xmlEncoding = 'utf-8';

    /* @var DOMElement **/
    protected $lastBuiltElement = '';

    public $elemAttributeMark = "_attr";

    /* @var DOMDocument **/
    public $xmlDoc = '';

    public function __construct()
    {
        $this->xmlDoc = new DOMDocument(
            $this->xmlVersion,
            $this->xmlEncoding
        );
    }

    /**
     * @static
     * @return Xml_Manager
     */
    public static function Create()
    {
        return new self();
    }

    // field => value
    public function CreateElements($fields)
    {
        $elements = array();
        foreach ($fields as $field => $value)
        {
            $elements[] = $this->xmlDoc->createElement($field, $value);
        }
        return $elements;
    }

    /**
     * @param $parentElement DOMDocument
     * @param $appendingElements array
     * @return DOMDocument
     */
    public function AppendElementsTo($appendingElements, $parentElement = null)
    {
        $childElement = false;

        if (empty($parentElement))
        {
            $parentElement = $this->xmlDoc;
        }

        foreach($appendingElements as $element)
        {
            $childElement = $parentElement->appendChild($element);
        }
        return $childElement;
    }

    /**
     * @param $parentElement DOMElement
     * @param $appendingAttributes array
     * @return DOMAttr | bool
     */
    public function AppendAttributesTo($appendingAttributes, DOMElement $parentElement = null)
    {
        /* @var $createdAttribute DOMAttr **/
        $createdAttribute = false;

        if (empty($parentElement))
        {
            $parentElement = $this->xmlDoc;
        }

        foreach($appendingAttributes as $field => $value)
        {
            $createdAttribute = $this->xmlDoc->createAttribute($field);
            $createdAttribute->value = $value;
            $parentElement->appendChild($createdAttribute);
        }

        return $createdAttribute;
    }

    protected function IsMarkedAsAttribute($element)
    {
        return $element == $this->elemAttributeMark;
    }

    public function AsXML()
    {
        return $this->xmlDoc->saveXML();
    }

    // method recursion to create and immidiately append elements with attributes
    public function BuildElements($fields, DOMElement $parentElement = null)
    {
        if (empty($parentElement))
        {
            $parentElement = $this->xmlDoc;
        }

        $childElement = null;
        foreach ($fields as $field => $fieldElement)
        {
            if (is_array($fieldElement))
            {
                // @ref  if this is attribute (ATTRIBUTE MUST CONTAIN AN ARRAY!)
                if ($this->IsMarkedAsAttribute($field))
                {
                    $this->AppendAttributesTo($fieldElement, $parentElement);
                    continue;
                }

                // Creating Parent Element for recursive creation of children
                $element = $this->xmlDoc->createElement($field, '');
                $this->lastBuiltElement = $parentElement->appendChild($element);

                $this->BuildElements($fieldElement, $this->lastBuiltElement);
            }
            else
            {
                $element = $this->xmlDoc->createElement($field, $fieldElement);
                $this->lastBuiltElement = $parentElement->appendChild($element);
            }
        }
        return $this->lastBuiltElement;
    }

    public function ParseString($xmlStringData)
    {
        if ( ! function_exists('xml_parse'))
        {
            return false;
        }

        $XMLParser = xml_parser_create();

        xml_parser_set_option($XMLParser, XML_OPTION_CASE_FOLDING, $this->optionCaseFolding);
        xml_parser_set_option($XMLParser, XML_OPTION_SKIP_WHITE, $this->optionSkipWhite);

        xml_parse_into_struct(
            $XMLParser,
            $xmlStringData,
            $this->parsedData,
            $this->parsedDataIndexes
        );

        xml_parser_free($XMLParser);

        return $this->parsedData;
    }

    public function GetElementByName($tagName)
    {
        return $this->xmlDoc->getElementsByTagName($tagName);
    }

    public function ParseFile()
    {
        // @todo
    }

};