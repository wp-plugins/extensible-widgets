<?php
/**
 * This file defines xf_utils_DOMDocument, which provides personal (utility) methods to the class DOMDocument.
 * 
 * PHP version 5
 * 
 * @package    xf
 * @subpackage utils
 * @author     Jim Isaacs <jimpisaacs@gmail.com>
 * @copyright  2009-2010 Jim Isaacs
 * @link       http://jidd.jimisaacs.com
 */

/**
 * Extends the DOMDocument to implement personal (utility) methods.
 *
 * @since xf 1.0.0
 * @author Jim Isaacs <jimpisaacs@gmail.com>
 * @package xf
 * @subpackage utils
 */
class xf_utils_DOMDocument extends DOMDocument {
	
	// CONSTANTS
	
	const ASSOC_NUMERIC_PREFIX = 'number-';
	const SERIALIZED_SUFFIX = '-serialized';
	const JSON_SUFFIX = '-json';
	
	// STATIC MEMBERS
	
	public function isAssocArray( $array ) { 
		if( is_array($array) && !empty($array) ) {
			for( $i = count($array) - 1; $i; $i-- ) {
			    if ( !array_key_exists($i, $array) ) return true;
			}
			return !array_key_exists(0, $array);
		}
		return false;
	}

	public static function handleXmlError($errno, $errstr, $errfile, $errline) {
		if ( $errno == E_WARNING && (substr_count($errstr,"DOMDocument::loadXML()") > 0) ) {
			throw new DOMException($errstr);
		} else {
			return false;
		}
	}

	/**
	 * Convert an array to an xml string using and instance of this class
	 *
	 * @param array $data The string that will be converted to an array
	 * @return string|false
	 */
	public static function array2Xml( &$data ) {
		$instance = new self( '1.0', 'UTF-8' );
		try {
			$instance->fromArray( $data, $instance );
		} catch( DOMException $e ) {
			return false;
		}
		return $instance->saveXML();
	}
	
	/**
	 * Convert an xml string to an array using and instance of this class
	 *
	 * @param array $data The data that will be converted to an xml string
	 * @return array
	 */
	public static function xml2Array( $xml ) {		
    	$instance = new self( '1.0', 'UTF-8' );
		$instance->validateOnParse = true;
		set_error_handler( array(self,'handleXmlError') );
		try {
			$instance->loadXML( $xml );
		} catch( DOMException $e ) {
			restore_error_handler();
			return false;
		}
		restore_error_handler();
		return $instance->toArray();
		
    }
	
	// INSTANCE MEMBERS
	
	/**
	 * Creates an array from the DOM tree of this instance
	 *
	 * @param DOMNode[optional] $domNode The DOMNode that the array will start from, if none specified it uses this instance
	 * @param mixed $defaultEmpty The default value for empty array indexes, the default here is an emtpy array.
	 * @return array
	 */
	public function toArray(DOMNode &$domNode = null, $defaultEmpty = '' ) {
        // return empty array if dom is blank
        if (is_null($domNode) && !$this->hasChildNodes()) {
            return array();
        }
        // Whether to start from specified node or this document
        $domNode = is_null($domNode) ? $this : $domNode;
        
        if (!$domNode->hasChildNodes()) {
            $mResult = $domNode->nodeValue;
        } else {
            $mResult = array();
            foreach ($domNode->childNodes as $oChildNode) {
                // how many of these child nodes do we have?
                // this will give us a clue as to what the result structure should be
                $oChildNodeList = $domNode->getElementsByTagName($oChildNode->nodeName);  
                $iChildCount = 0;
                // there are x number of childs in this node that have the same tag name
                // however, we are only interested in the # of siblings with the same tag name
                foreach ($oChildNodeList as $oNode) {
                    if ($oNode->parentNode->isSameNode($oChildNode->parentNode)) {
                        $iChildCount++;
                    }
                }
                $mValue = $this->toArray($oChildNode);
                $sKey   = ($oChildNode->nodeName{0} == '#') ? 0 : $oChildNode->nodeName;
                $mValue = is_array($mValue) ? $mValue[$oChildNode->nodeName] : $mValue;
                // how many of thse child nodes do we have?
                if ($iChildCount > 1) {  // more than 1 child - make numeric array
                    $mResult[$sKey][] = $mValue;
                } else {
                	// If there is a numberic prefix in use only grab the numeric index part of the string
                	if( function_exists('json_decode') ) {
						if( preg_match( '/^(.+)'.self::JSON_SUFFIX.'$/', $sKey ) ) {
	                		$mValue = json_decode($mValue);
	                	}
					} else {
						if( preg_match( '/^(.+)'.self::SERIALIZED_SUFFIX.'$/', $sKey ) ) {
	                		$mValue = unserialize($mValue);
	                	}
					}
					if( preg_match( '/^'.self::ASSOC_NUMERIC_PREFIX.'([0-9]+)(.*)$/', $sKey, $matches ) ) {
                		$sKey = $matches[1];
                	}
                	if( !is_array($mValue) && empty($mValue) ) $mValue = $defaultEmpty;
                    $mResult[$sKey] = $mValue;
                }
            }
            // if the child is <foo>bar</foo>, the result will be array(bar)
            // make the result just 'bar'
            if (count($mResult) == 1 && isset($mResult[0]) && !is_array($mResult[0])) {
                $mResult = $mResult[0];
            }
        }
        // get our attributes if we have any
        $arAttributes = array();
        if ($domNode->hasAttributes()) {
            foreach ($domNode->attributes as $sAttrName=>$oAttrNode) {
                // retain namespace prefixes
                $arAttributes["@{$oAttrNode->nodeName}"] = $oAttrNode->nodeValue;
            }
        }
        // check for namespace attribute - Namespaces will not show up in the attributes list
        if ($domNode instanceof DOMElement && $domNode->getAttribute('xmlns')) {
            $arAttributes["@xmlns"] = $domNode->getAttribute('xmlns');
        }
        if (count($arAttributes)) {
            if (!is_array($mResult)) {
                $mResult = (trim($mResult)) ? array($mResult) : array();
            }
            $mResult = array_merge($mResult, $arAttributes);
        }
        $arResult = array($domNode->nodeName=>$mResult);
        return $arResult;
    }
    
	/**
	 * Constructs the DOM tree from an array or string. The array can contain 
	 * an element's name in the index part and an element's text in the value part.
	 *
	 * @param array $data An array of data to built the DOM tree with.
	 * @param DOMNode[optional] $node The element from where the array will construct
	 * @param bool $forceAssociative This is a fix for php arrays being able to make associative arrays containing integer keys
	 * @return void
	 */
	
	public function fromArray( $data, DOMnode $node, $forceAssociative = false ) {
		if( empty($node->parentNode) ) {
			$node = $this->createElement( 'document' );
			$this->appendChild( $node );
		}
		if( is_array($data) ) {
			// encode or serialize an empty array to decode or deserialize back into an empty array
			if( !count($data) ) {
				if( function_exists('json_encode') ) {
					$child = $this->createElement( $node->tagName.self::JSON_SUFFIX );
					$child->appendChild( $this->createCDATASection( json_encode($data) ) );
				} else {
					$child = $this->createElement( $node->tagName.self::SERIALIZED_SUFFIX );
					$child->appendChild( $this->createCDATASection( serialize($data) ) );
				}
				$node->parentNode->replaceChild( $child, $node );
			} else {
				if( $forceAssociative || self::isAssocArray($data) ) {
					foreach( $data as $key => $item ) {
						if( is_numeric($key) ) $key = self::ASSOC_NUMERIC_PREFIX.$key;
						$child = $this->createElement( $key );
						$node->appendChild( $child );
						$this->fromArray( $item, $child, $forceAssociative );
					}
				} else {
					foreach( $data as $i => $item ) {
						if( $i == 0 ) {
							$child = $node;
						} else {
							$child = $this->createElement( $node->tagName );
						}
						$node->parentNode->appendChild( $child );
						$this->fromArray( $item, $child, $forceAssociative );
					}
				}
			}
		} else {
			if( preg_match('/^([a-zA-Z0-9_\-.\/,]+)$/', $data) ) {
				$child = $this->createTextNode( $data );
			} else {
				$child = $this->createCDATASection( $data );
			}
			$node->appendChild( $child );
		}
	}

}
?>