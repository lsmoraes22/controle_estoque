<?php

namespace App\Services;

use SimpleXMLElement;
use Exception;

class XmlToObjectConverter
{
    public static function getElements($filePath)
    {
        // Disable error reporting for XML parsing errors
        libxml_use_internal_errors(true);

        // Check if the file exists
        if (!is_file($filePath)) {
            throw new Exception("File not found.");
        }

        // Read the XML file content
        $xmlString = file_get_contents($filePath);

        // Load the XML string
        $xml = simplexml_load_string($xmlString, "SimpleXMLElement", LIBXML_NOCDATA);

        if ($xml === false) {
            $errXml = "Failed loading XML" . PHP_EOL;
            foreach (libxml_get_errors() as $error) {
                $errXml .= $error->message . PHP_EOL;
            }
            throw new Exception($errXml);
        }

        // Register namespaces
        $xml->registerXPathNamespace('xs', 'http://www.w3.org/2001/XMLSchema');

        // Fetch the elements you want
        $elements = $xml->xpath('//xs:element');
        $result = [];
        foreach ($elements as $element) {
            $processedElement = self::processElement($element);
            unset($processedElement['content']);
            $result[$processedElement['name']] = $processedElement;
        }
        return $result;
    }

    private static function processElement(SimpleXMLElement $element)
    {
        $elementData = [
            'name' => (string) $element['name'],
            'attributes' => [],
            'content' => (string) $element,
            'children' => [],
            'documentation' => '',
            'pattern' => 'no'
        ];

        // Handle attributes
        foreach ($element->attributes() as $key => $value) {
            $elementData['attributes'][$key] = (string) $value;
        }

        // Handle annotation/documentation
        $annotations = $element->xpath('xs:annotation/xs:documentation');
        if (!empty($annotations)) {
            $elementData['documentation'] = (string) $annotations[0];
        }

        // Handle children
        $children = $element->children('xs', true);
        foreach ($children as $child) {
            if ($child->getName() === 'element') {
                $elementData['pattern'] = 'yes';
                $elementData['children'][] = self::processElement($child);
            }
        }

        return $elementData;
    }
}
