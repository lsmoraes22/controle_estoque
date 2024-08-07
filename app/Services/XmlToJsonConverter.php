<?php

namespace App\Services;

use Exception;
use SimpleXMLElement;

class XmlToJsonConverter
{
    /**
     * Convert an XML string to JSON.
     *
     * @param string $xmlString The XML string to convert.
     * @return string The JSON representation of the XML.
     * @throws Exception If the XML string cannot be parsed.
     */
    public function convert($xmlString)
    {
        // Disable error reporting for XML parsing errors
        libxml_use_internal_errors(true);

        // Load the XML string
        $xml = simplexml_load_string($xmlString, "SimpleXMLElement", LIBXML_NOCDATA);

        if ($xml === false) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            throw new Exception("Failed to parse XML: " . implode(", ", array_map(function($error) {
                return $error->message;
            }, $errors)));
        }

        // Convert the SimpleXML object to JSON
        $json = json_encode($this->simpleXmlToArray($xml));
        return $json;
    }

    /**
     * Recursively convert a SimpleXML object to an array.
     *
     * @param SimpleXMLElement $xml The SimpleXML object to convert.
     * @return array The array representation of the XML.
     */
    private function simpleXmlToArray($xml)
    {
        $array = [];

        // Convert attributes
        foreach ($xml->attributes() as $key => $value) {
            $array['@attributes'][$key] = (string) $value;
        }

        // Convert children
        foreach ($xml->children() as $key => $value) {
            $value = $this->simpleXmlToArray($value);

            if (isset($array[$key])) {
                if (!isset($array[$key][0])) {
                    $array[$key] = [$array[$key]];
                }
                $array[$key][] = $value;
            } else {
                $array[$key] = $value;
            }
        }

        // Convert text content
        if (!empty(trim((string) $xml))) {
            $array['@text'] = (string) $xml;
        }

        return $array;
    }
}
