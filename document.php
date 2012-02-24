<?php
/**
 * Document Model
 * 
 * A system for storing document information and rendering tags and collections
 * of tags. This allows for a modular approach to manipulating and rendering
 * various components of an HTML document.
 * 
 * The model is separated into three components:
 *  - a container class that extends ArrayObject
 *  - an abstract document class with properties, containers, and business logic
 *  - a document class that extends abstractDocument and contains rendering methods
 * 
 * LICENSE
 *
 * Copyright (c) 2012 Al Warren
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * - Redistributions of source code must retain the above copyright notice, this
 *   list of conditions and the following disclaimer.
 *
 * - Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF S
 * UBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 * 
 * @package Document Model
 * @author Al Warren
 * @copyright Copyright (c) 2012, Al Warren
 * @link https://github.com/alwarren
 * @version 1.0 beta
 * @filesource
 */

/**
 * Document Container Class
 * 
 * Extends ArrayObject
 * 
 * @link http://php.net/manual/en/class.arrayobject.php
 */
class documentContainer extends ArrayObject
{
	/**
	 * Prepend an item to the container.
	 * 
	 * (there is no native prepend method)
	 * 
	 * @param mixed $value
	 */	
	public function prepend($value)
	{
		$values = $this->getArrayCopy();
		array_unshift($values, $value);
		$this->exchangeArray($values);
	}
	
	/**
	 * Empty a container and set it's initial contents.
	 * 
	 * @param mixed $value
	 */
	public function set($value=null)
	{
		if (!empty($value))
		{
			$this->exchangeArray(array());
			$this->prepend($value);
		}
	}
	
	/**
	 * Magic method to allow common array functions
	 * 
	 * Experimental - not recommended for production servers
	 * 
	 * @param string $func
	 * @param mixed $argv
	 * @return mixed|NULL
	 */
	public function __call($func, $argv)
	{
		if (substr($func, 0, 6) == 'array_' && is_callable($func))
		{
			return call_user_func_array($func, array_merge(array($this->getArrayCopy()), $argv));
		}
		
		return null;
	}
}

/**
 * Abstract Document Class
 * 
 * Contains properties and business methods
 *
 * @package Document Model
 */
class abstractDocument
{
	/**
	 * Document type
	 * 
	 * @var string
	 */
	public $doctype = 'html5';
	
	/**
	 * Character set
	 * 
	 * @var string
	 */
	public $charset = 'UTF-8';
	
	/**
	 * Language
	 * 
	 * @var string
	 */
	public $language = 'en-gb';
	
	/**
	 * Rendering direction
	 * 
	 * @var string
	 */
	public $direction = 'ltr';
	
	/**
	 * Meta Description
	 * 
	 * @var string
	 */
	public $description = null;
	
	/**
	 * Meta Keywords
	 * 
	 * @var string
	 */
	public $keywords = null;
	
	/**
	 * Title Separator
	 * 
	 * @var string
	 */
	public $separator = ' : ';
	
	/**
	 * End of line character(s)
	 * 
	 * @var string
	 */
	public $eol = "\12";
	
	/**
	 * Tab character
	 * 
	 * @var string
	 */
	protected $tab = "\11";
	
	/**
	 * Document containers
	 * 
	 * @var array of ArrayObject
	 */
	protected $containers = null;
	
	/**
	 * Core containers key list
	 * 
	 * @var array
	 */
	private $core_container_keys = null;
	
	// remove this for production
	public function getContainers()
	{
		return $this->containers;
	}
	
	/**
	 * Constructor
	 *
	 * Creates document containers
	 */
	 public function __construct()
	{
		$this->addContainer('title', true);
		$this->addContainer('metas', true);
		$this->addContainer('stylesheets', true);
		$this->addContainer('cssBlocks', true);
		$this->addContainer('scripts', true);
		$this->addContainer('scriptBlocks', true);
		$this->addContainer('scriptBlocksBottom', true);
		
		$this->core_container_keys = array_keys($this->containers);
	}

	/**
	 * Magic method getter
	 * 
	 * Set methods and rules for retrieving properties
	 * 
	 * Usage: $document->property;
	 *  - returns $document->getProperty() if the method exists
	 *    and the property is not a container
	 *  - returns the container if it is not a core container
	 *  
	 * @param string $var
	 * @return mixed|NULL
	 */
	public function __get($var)
	{
		$method = 'get' . ucfirst($var);
		if(method_exists($this, $method))
			return $this->$method();
		
		if(array_key_exists($var, $this->containers) 
			&& $this->containers[$var] instanceof documentContainer
			&& !array_key_exists($var, $this->core_container_keys)
			)
			return $this->containers[$var];
		
		return null;
	}
	
	/**
	 * Magic method setter
	 * 
	 * Set methods and rules for setting properties
	 * 
	 * Usage: $document->property = 'some value';
	 *  - calls $document->setProperty() if the method exists
	 * 
	 * @param string $var
	 * @param mixed $value
	 * @return document
	 */
	public function __set($property, $value)
	{
		$method = 'set' . ucfirst($property);
		if(method_exists($this, $method))
			$this->$method($value);

		return $this;
	}
	
	/**
	 * Abstract rendering method
	 * 
	 * Usage: $document->render('title');
	 *  - calls $document->renderTitle() if the method exists
	 * 
	 * @param string $property
	 * @return mixed|NULL
	 */
	public function render($property=null)
	{
		if(empty($property))
			return null;
		
		$method = 'render' . ucfirst($property);
		if(method_exists($this, $method))
			return $this->$method();
	}
	
	/**
	 * Add an item to the end of a container
	 * 
	 * @param string $key
	 * @param mixed $value
	 * @return document
	 */
	public function append($key, $value=null)
	{
		if(!$this->contains($key))
			$this->showError("can't append a container with key $key");
	
		if(!$this->containers[$key] instanceof documentContainer)
			$this->showError("can't append a container that isn't an documentContainer");
	
		$this->containers[$key]->append($value);
		
		return $this;
	}
	
	/**
	 * Add an item to the beginning of a container
	 * 
	 * @param string $key
	 * @param mixed $value
	 * @return document
	 */
	public function prepend($key, $value=null)
	{
		if(!$this->contains($key))
			$this->showError("can't prepend a container that doesn't exist");
	
		if(!$this->containers[$key] instanceof documentContainer)
			$this->showError("can't prepend a container that isn't an documentContainer");
	
		$this->containers[$key]->prepend($value);
		
		return $this;
	}
	
	/**
	 * Empty a container and set it's initial contents.
	 * 
	 * @param string $key
	 * @param mixed $value
	 * @return document
	 */
	public function set($key, $value=null)
	{
		if(!$this->contains($key))
			$this->showError("can't set a value for a container with key $key");
	
		if($this->containers[$key] instanceof documentContainer)
			$this->containers[$key]->set($value);
		else
			$this->containers[$key] = $value;
	
		return $this;
	}
	
	/**
	 * Add a container to the collection
	 * 
	 * @param string $key
	 * @param bollean $arrayObject if true add an ArrayObject
	 * @return document
	 */
	public function addContainer($key=null, $arrayObject=false)
	{
		if(null === $key)
			$this->showError("can't create a container without a key");
	
		if(isset($this->containers[$key]))
			$this->showError("can't create a container that already exists ($key)");
	
		if(true === $arrayObject)
			$this->containers[$key] = new documentContainer();
		else
			$this->containers[$key] = null;
	
		return $this;
	}
	
	/**
	 * Remove a container from the collection
	 * 
	 * @param string $key
	 * @return document
	 */
	public function removeContainer($key=null)
	{
		if(null === $key)
			$this->showError("can't remove a container without a key");
	
		if($this->contains($key) && !in_array($key, $this->core_container_keys))
			unset($this->containers[$key]);
		else
			$this->showError("can't remove a container with key $key");
		
		return $this;
	}
	
	/**
	 * Query containers for key exists
	 * 
	 * @param string $key
	 * @return boolean true if container key exists
	 */
	public function contains($key=null)
	{
		if(null === $key)
			$this->showError("can't query document with key $key");
	
		return array_key_exists($key, $this->containers);
	}
	
	/**
	 * Return a container as an array
	 * 
	 * @param string $key
	 * @return array
	 */
	public function toArray($key=null)
	{
		if(!$this->contains($key))
			$this->showError("can't use toArry with a container named $key");
	
		if($this->containers[$key] instanceof documentContainer)
			return (array) $this->containers[$key];
	
		if(is_array($this->containers[$key]))
			return $this->containers[$key];
	
		if(is_object($this->containers[$key]))
			return (array) $this->containers[$key];
	
		return array($this->containers[$key]);
	}
	
	/**
	 * Is document type xhtml?
	 * 
	 * @return boolean
	 */
	public function doctypeIsXhtml()
	{
		return (stristr($this->doctype, 'xhtml') ? true : false);
	}

	/**
	 * Set or reset the initial value of the title
	 * 
	 * @param string $value
	 * @return document
	 */
	public function setTitle($value)
	{
		if (!empty($value))
			$this->set('title', $value);
		
		return $this;
	}
	
	/**
	 * Add to the beginning of the title
	 * 
	 * @param string $value
	 * @return document
	 */
	public function prependTitle($value)
	{
		if (!empty($value))
			$this->prepend('title', $value);
		
		return $this;
	}
	
	/**
	 * Add to the end of the title
	 * 
	 * @param string $value
	 * @return document
	 */
	public function appendTitle($value)
	{
		if (!empty($value))
			$this->append('title', $value);
		
		return $this;
	}
	
	/**
	 * Add a meta to the beginning of the container
	 * 
	 * @param string $name
	 * @param string $content
	 * @return document
	 */
	public function prependMeta($name=null, $content=null)
	{
		return $this->addMeta($name, $content, true);
	}
	
	/**
	 * Add a meta to the end of the container
	 * 
	 * @param string $name
	 * @param string $content
	 * @return document
	 */
	public function appendMeta($name=null, $content=null)
	{
		return $this->addMeta($name, $content);
	}
	
	/**
	 * Add a meta to the container
	 * 
	 * @param string $name
	 * @param string $content
	 * @param boolean $prepend if true add to beginning of container
	 * @return document
	 */
	public function addMeta($name=null, $content=null, $prepend=false)
	{
		if(!empty($name))
		{
			$attrs = array();
			if($name == 'charset' || $name == 'http-equiv')
				$attrs = array('name'=>$name);
			elseif(!empty($content))
			{
				$attrs['name'] = $name;
				$attrs['content'] = $content;
			}
			if(!empty($attrs))
			{
				if(true === $prepend)
					$this->prepend('metas', $attrs);
				else
					$this->append('metas', $attrs);
			}
		}
		
		return $this;
	}
	
	/**
	 * Add a stylesheet to the end of the container
	 * 
	 * @param string $href
	 * @param string $media
	 * @return document
	 */
	public function appendStyleSheet($href=null, $media=null)
	{
		return $this->addStylesheet($href, $media);
	}
	
	/**
	 * Add a stylesheet to the beginning of the container
	 * 
	 * @param string $href
	 * @param string $media
	 * @return document
	 */
	public function prependStyleSheet($href=null, $media=null)
	{
		return $this->addStylesheet($href, $media, true);
	}
	
	/**
	 * Add a stylesheet to the the container
	 * 
	 * @param string $href
	 * @param string $media
	 * @param boolean $prepend if true add to beginning of container
	 * @return document
	 */
	public function addStylesheet($href=null, $media=null, $prepend=false)
	{
		if(!empty($href))
		{
			if(!empty($media))
			{
				$attrs = array();
				$attrs['href'] = $href;
				$attrs['media'] = $media;
			} else
				$attrs = $href;
			
			if(true === $prepend)
				$this->prepend('stylesheets', $attrs);
			else
				$this->append('stylesheets', $attrs);
		}
		
		return $this;
	}
	
	/**
	 * Add a javascript file to the end of the scripts container
	 * 
	 * @param string $src
	 * @param boolean $defer
	 * @param boolean $async
	 * @param boolean $prepend if true add to beginning of container
	 * @return document
	 */
	public function addJavascript($src=null, $defer=false, $async=false, $prepend=false)
	{
		return $this->addScript($src, 'text/javascript', $defer, $async);
	}
	
	/**
	 * Add a javascript file to the end of the scripts container
	 * 
	 * @param string $src
	 * @param boolean $defer
	 * @param boolean $async
	 * @return document
	 */
	public function appendJavascript($src=null, $defer=false, $async=false)
	{
		return $this->addScript($src, 'text/javascript', $defer, $async);
	}
	
	/**
	 * Add a javascript file to the beginning of the scripts container
	 * 
	 * @param string $src
	 * @param boolean $defer
	 * @param boolean $async
	 * @return document
	 */
	public function prependJavascript($src=null, $defer=false, $async=false)
	{
		return $this->addScript($src, 'text/javascript', $defer, $async, true);
	}
	
	/**
	 * Add a script file to the end of the scripts container
	 * 
	 * @param string $src
	 * @param string $type
	 * @param boolean $defer
	 * @param boolean $async
	 * @return document
	 */
	public function appendScript($src=null, $type='text/javascript', $defer=false, $async=false)
	{
		return $this->addScript($src, $type, $defer, $async);
	}
	
	/**
	 * Add a script file to the beginning of the scripts container
	 * 
	 * @param string $src
	 * @param string $type
	 * @param boolean $defer
	 * @param boolean $async
	 * @return document
	 */
	public function prependScript($src=null, $type='text/javascript', $defer=false, $async=false)
	{
		return $this->addScript($src, $type, $defer, $async, true);
	}
	
	/**
	 * Add a script file to the scripts container
	 * 
	 * @param string $src
	 * @param string $type
	 * @param boolean $defer
	 * @param boolean $async
	 * @param boolean $prepend if true add to beginning of container
	 * @return document
	 */
	public function addScript($src=null, $type='text/javascript', $defer=false, $async=false, $prepend=false)
	{
		if(empty($src) || empty($type) || !is_bool($defer) || !is_bool($async))
			return $this;
		
		if(true === $prepend)
			$this->prepend('scripts', array('src'=>$src, 'type'=>$type, 'defer'=>$defer, 'async'=>$async));
		else
			$this->append('scripts', array('src'=>$src, 'type'=>$type, 'defer'=>$defer, 'async'=>$async));
		
		return $this;
	}
	
	/**
	 * Add javascript code to the beginning of the script blocks container
	 * 
	 * @param string $content
	 * @return document
	 */
	public function prependJavascriptBlock($content)
	{
		return $this->addScriptBlock($content, 'text/javascript', true);
	}
	
	/**
	 * Add javascript code to the end of the script blocks container
	 * 
	 * @param string $content
	 * @return document
	 */
	public function appendJavascriptBlock($content)
	{
		return $this->addScriptBlock($content, 'text/javascript');
	}
	
	/**
	 * Add javascript code to the script blocks container
	 * 
	 * @param string $content
	 * @param boolean $prepend if true add to beginning of container
	 * @return document
	 */
	public function addJavascriptBlock($content, $prepend=false)
	{
		return $this->addScriptBlock($content, 'text/javascript', $prepend);
	}
	
	/**
	 * Add code to the script blocks container
	 * 
	 * @param string $content
	 * @param string $type
	 * @param boolean $prepend if true add to beginning of container
	 * @return document
	 */
	public function addScriptBlock($content=null, $type='text/javascript', $prepend=false)
	{
		if(empty($content))
			return $this;

		if(true === $prepend)
			$this->prepend('scriptBlocks', array('content'=>$content, 'type'=>$type));
		else
			$this->append('scriptBlocks', array('content'=>$content, 'type'=>$type));
		
		return $this;
	}
		
	/**
	 * Add javascript code to the beginning of the bottom script blocks container
	 * 
	 * @param string $content
	 * @return document
	 */
	public function prependJavascriptBlockBottom($content)
	{
		return $this->addScriptBlockBottom($content, 'text/javascript', true);
	}
	
	/**
	 * Add javascript code to the end of the bottom script blocks container
	 * 
	 * @param string $content
	 * @return document
	 */
	public function appendJavascriptBlockBottom($content)
	{
		return $this->addScriptBlockBottom($content, 'text/javascript');
	}
	
	/**
	 * Add javascript code to the bottom script blocks container
	 * 
	 * @param string $content
	 * @param boolean $prepend if true add to beginning of container
	 * @return document
	 */
	public function addJavascriptBlockBottom($content, $prepend=false)
	{
		return $this->addScriptBlockBottom($content, 'text/javascript', $prepend);
	}
	
	/**
	 * Add code to the bottom script blocks container
	 * 
	 * @param string $content
	 * @param string $type
	 * @param boolean $prepend if true add to beginning of container
	 * @return document
	 */
	public function addScriptBlockBottom($content=null, $type='text/javascript', $prepend=false)
	{
		if(empty($content))
			return $this;
	
		if(true === $prepend)
			$this->prepend('scriptBlocksBottom', array('content'=>$content, 'type'=>$type));
		else
			$this->append('scriptBlocksBottom', array('content'=>$content, 'type'=>$type));
	
		return $this;
	}
	
	/**
	 * Add to the beginning of keywords
	 * 
	 * @param string $keywords
	 * @return document
	 */
	public function prependKeywords($keywords=null)
	{
		return $this->addKeywords($keywords, true);
	}
	
	/**
	 * Add to the end of keywords
	 * 
	 * @param string $keywords
	 * @return document
	 */
	public function appendKeywords($keywords=null)
	{
		return $this->addKeywords($keywords);
	}
	
	/**
	 * Add to keywords
	 * 
	 * @param string $keywords
	 * @param boolean $prepend if true add to beginning of keywords
	 * @return document
	 */
	public function addKeywords($keywords=null, $prepend = false)
	{
		if(!empty($keywords))
			if(true === $prepend)
				$this->keywords = $keywords . ', ' . $this->keywords;
			else
				$this->keywords = $this->keywords . ', ' . $keywords;
				
		return $this;
	}
	
	/**
	 * Get a list of document types
	 * 
	 * @return array
	 */
	protected function doctypes()
	{
		return
			array(
				'xhtml11'=>'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">',
				'xhtml1-strict'=>'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">',
				'xhtml1-trans'=>'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">',
				'xhtml1-frame'=>'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">',
				'html5'=>'<!DOCTYPE html>',
				'html4-strict'=>'<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">',
				'html4-trans'=>'<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">',
				'html4-frame'=>'<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">',
		);
	}
	
	/**
	 * Set the end of line character(s)
	 * 
	 * @param string $value
	 * @return document
	 */
	public function setEol($value=null)
	{
		switch ($value)
		{
			case 'unix':
				$this->eol = "\12";
				break;
			case 'mac':
				$this->eol = "\15";
				break;
			case 'win':
				$this->eol = "\15\12";
				break;
			default:
			$this->eol = $value;
		}
		
		return $this;
	}

	/**
	 * Get a list of valid languages
	 * 
	 * @return array
	 */
	protected function validLanguages()
	{
		return
			array('aa', 'ab', 'af', 'ak', 'sq', 'am', 'ar', 'an', 'hy', 'as',
			'av', 'ae', 'ay', 'az', 'ba', 'bm', 'eu', 'be', 'bn', 'bh', 'bi', 
			'bs', 'br', 'bg', 'my', 'ca', 'ch', 'ce', 'zh', 'cu', 'cv', 'kw', 
			'co', 'cr', 'cs', 'da', 'dv', 'nl', 'dz', 'en', 'eo', 'et', 'ee', 
			'fo', 'fj', 'fi', 'fr', 'fy', 'ff', 'ka', 'de', 'gd', 'ga', 'gl', 
			'gv', 'el', 'gn', 'gu', 'ht', 'ha', 'he', 'hz', 'hi', 'ho', 'hr', 
			'hu', 'ig', 'is', 'io', 'ii', 'iu', 'ie', 'ia', 'id', 'ik', 'it', 
			'jv', 'ja', 'kl', 'kn', 'ks', 'kr', 'kk', 'km', 'ki', 'rw', 'ky', 
			'kv', 'kg', 'ko', 'kj', 'ku', 'lo', 'la', 'lv', 'li', 'ln', 'lt', 
			'lb', 'lu', 'lg', 'mk', 'mh', 'ml', 'mi', 'mr', 'ms', 'mg', 'mt', 
			'mn', 'na', 'nv', 'nr', 'nd', 'ng', 'ne', 'nn', 'nb', 'no', 'ny', 
			'oc', 'oj', 'or', 'om', 'os', 'pa', 'fa', 'pi', 'pl', 'pt', 'ps', 
			'qu', 'rm', 'ro', 'rn', 'ru', 'sg', 'sa', 'si', 'sk', 'sl', 'se', 
			'sm', 'sn', 'sd', 'so', 'st', 'es', 'sc', 'sr', 'ss', 'su', 'sw', 
			'sv', 'ty', 'ta', 'tt', 'te', 'tg', 'tl', 'th', 'bo', 'ti', 'to', 
			'tn', 'ts', 'tk', 'tr', 'tw', 'ug', 'uk', 'ur', 'uz', 've', 'vi', 
			'vo', 'cy', 'wa', 'wo', 'xh', 'yi', 'yo', 'za', 'zu'
		);
	}
}

/**
 * Document Class
 * 
 * Contains rendering methods
 *
 * @package Document Model
 */
class document extends abstractDocument
{
	/**
	 * Set a document type then render the DTD
	 * 
	 * @param string $doctype
	 * @return string
	 */
	public function doctype($doctype=null)
	{
		$this->doctype = $doctype;
		
		return $this->renderDoctype();
	}
	
	/**
	 * Render the DTD
	 * 
	 * @return string
	 */
	protected function renderDoctype()
	{
		$doctypes = $this->doctypes();
		if(!empty($this->doctype) && array_key_exists($this->doctype, $doctypes))
			return $doctypes[$this->doctype] . $this->eol;
		
		return null;
	}
	
	/**
	 * Render a character set meta tag
	 * 
	 * @return string
	 */
	protected function renderCharset()
	{
		$charset = $this->charset ? $this->charset : 'UTF-8';
		
		$html = $this->tab . "<meta http-equiv=\"Content-type\" content=\"text/html;charset=$charset\">" . $this->eol;
		
		return $html;
	}
	
	/**
	 * Render a title tag
	 * 
	 * @return string
	 */
	protected function renderTitle()
	{
		$charset = $this->charset ? $this->charset : 'UTF-8';
		$title = implode($this->separator, (array) $this->containers['title']);
		
		$html = $this->tab . '<title>' . htmlspecialchars($title, ENT_COMPAT, $charset) . '</title>' . $this->eol;
		
		return $html;
	}
	
	/**
	 * Render meta tags for all metas in the container
	 * 
	 * @return string
	 */
	public function renderMetas()
	{
		$html = '';
		$metas = (array) $this->containers['metas'];
		foreach($metas as $meta)
		{
			if(is_array($meta) && 
				isset($meta['name']) && 
				!empty($meta['name'])
				)
			{
				if ($meta['name'] == 'charset' || $meta['name'] == 'http-equiv' && !empty($this->charset))
				{
					$charset = $this->charset ? $this->charset : 'UTF-8';
					$html .= $this->tab . '<meta http-equiv="Content-type" content="text/html;charset=' . htmlspecialchars($charset).'" />' . $this->eol;
				}
				elseif (isset($meta['content']) && !empty($meta['content']))
				{
					$html .= $this->tab . '<meta name="' . $meta['name'] . '" content="' . htmlspecialchars($meta['content']) . '" />' . $this->eol;
				}
			}						
		}
		
		return $html;
	}
	
	/**
	 * Render link tags for all the stylesheets in the container
	 *
	 * @return string
	 */
	protected function renderStylesheets()
	{
		$html = '';
		
		$stylesheets = $this->toArray('stylesheets');
		if(empty($stylesheets))
			return $html;
		
		foreach($stylesheets as $stylesheet)
		{
			$media = '';
			if(is_array($stylesheet))
			{
				if(!array_key_exists('href', $stylesheet))
					continue;
				
				$href = $stylesheet['href'];
				
				if(array_key_exists('media', $stylesheet) && !empty($stylesheet['media']))
					$media = ' media="'.$stylesheet['media'].'"';
			} else
				$href = $stylesheet;
			
			$html .= $this->tab . '<link rel="stylesheet" type="text/css" href="' . $href . '"' . $media . ' />' . $this->eol;
		}
		
		return $html;
	}
	
	/**
	 * Render script tags for all the files in the container
	 *
	 * @return string
	 */
	public function renderJavascript()
	{
		return $this->renderScripts();
	}
	
	/**
	 * Render script tags for all the files in the container
	 *
	 * @return string
	 */
	public function renderScripts()
	{
		$html = null;
		$scripts = (array) $this->containers['scripts'];
		foreach ($scripts as $script)
		{
			$html .= $this->tab . '<script src="' . $script['src'] . '"';
			if (!is_null($script['type']))
			{
				$html .= ' type="' . $script['type'] . '"';
			}
			if ($script['defer'])
			{
				$html .= ' defer="defer"';
			}
			if ($script['async'])
			{
				$html .= ' async="async"';
			}
			$html .= '></script>' . $this->eol;
		}
		
		return $html;
	}
	
	/**
	 * Render script blocks for each item in the bottom script blocks container
	 * 
	 * @return string
	 */
	public function renderJavascriptblocksbottom()
	{
		return $this->renderScriptblocksbottom();
	}
		
	/**
	 * Render script blocks for each item in the bottom script blocks container
	 * 
	 * @return string
	 */
	public function renderScriptblocksbottom()
	{
		$html = '';
		$scripts = (array) $this->containers['scriptBlocksBottom'];
		foreach ($scripts as $script)
		{
			$html .= $this->tab . '<script type="' . $script['type'] . '">' . $this->eol;
			$html .= $script['content'] . $this->eol;
			$html .= $this->tab . '</script>' . $this->eol;
		}
		
		return $html;
	}
		
	/**
	 * Render script blocks for each item in the script blocks container
	 * 
	 * @return string
	 */
	public function renderJavascriptblocks()
	{
		return $this->renderScriptblocks();
	}
	
	/**
	 * Render script blocks for each item in the script blocks container
	 * 
	 * @return string
	 */
	public function renderScriptblocks()
	{
		$html = '';
		$scripts = (array) $this->containers['scriptBlocks'];
		foreach ($scripts as $script)
		{
			$html .= $this->tab . '<script type="' . $script['type'] . '">' . $this->eol
				. $script['content'] . $this->eol
				. $this->tab . '</script>' . $this->eol;
		}
		
		return $html;
	}
	
	/**
	 * Render a meta keywords tag
	 * 
	 * @return string
	 */
	public function renderKeywords()
	{
		if(empty($this->keywords))
			return null;
		
		$html = $this->tab . '<meta name="keywords" content="' 
			. htmlspecialchars($this->keywords) . '" />' . $this->eol;
		
		return $html;
	}
	
	/**
	 * Render a meta description tag
	 * 
	 * @return string
	 */
	public function renderDescription()
	{
		if(empty($this->description))
			return null;
		
		$html = $this->tab . '<meta name="description" content="' 
			. htmlspecialchars($this->description) . '" />' . $this->eol;
		
		return $html;
	}
	
	/**
	 * Render an opening html tag
	 * 
	 * @return string
	 */
	protected function renderHtmlopen()
	{
		$html = '<html';
		
		$isXhtml = $this->doctypeIsXhtml();
		if (true === $isXhtml)
			$html .= ' xmlns="http://www.w3.org/1999/xhtml"';
		
        $lang = '';
        if (!empty($this->language))
        {
            $parts = explode('-', $this->language);
            $language = strtolower($parts[0]);
            if (in_array($language, $this->validLanguages()))
            {
                $lang = ' lang="'.$language.'"';
                if(true === $isXhtml)
                    $lang = ' xml:lang="'.$language.'"';
            }
        }
        $html .= $lang;
        
        $direction = htmlspecialchars($this->direction);
        if(!empty($direction))
        	$html .= " dir=\"$direction\"";
                
        $html .= ">" . $this->eol;
        
        return $html;
	}
	
	/**
	 * Render a closing html tag
	 * 
	 * @return string
	 */
		protected function renderHtmlclose()
	{
		return '</html>';
	}
	
	/**
	 * Render an error message and exit
	 * 
	 * @param string $message
	 * @param integer $status_code
	 * @param string $heading
	 */
	protected function showError($message, $status_code = 500, $heading = 'An Error Was Encountered')
	{
		if(function_exists('show_error'))
		{
			show_error($message, $status_code = 500, $heading = 'An Error Was Encountered');
			exit;
		}
		set_status_header($status_code);
?>
	<html>
		<head>
			<title>Error</title>
		</head>
		<body>
			<h1><?php echo $heading; ?></h1>
			<p><?php echo $message; ?></p>
		</body>
	</html>
<?php
		exit;
	}
		
}
/**
* Set HTTP Status Header
* derived from CodeIgniter 
* 
* @access	public
* @param	int		the status code
* @param	string
* @return	void
*/
if ( ! function_exists('set_status_header'))
{
	function set_status_header($code = 200, $text = '')
	{
		$stati = array(
		200	=> 'OK',
		201	=> 'Created',
		202	=> 'Accepted',
		203	=> 'Non-Authoritative Information',
		204	=> 'No Content',
		205	=> 'Reset Content',
		206	=> 'Partial Content',

		300	=> 'Multiple Choices',
		301	=> 'Moved Permanently',
		302	=> 'Found',
		304	=> 'Not Modified',
		305	=> 'Use Proxy',
		307	=> 'Temporary Redirect',

		400	=> 'Bad Request',
		401	=> 'Unauthorized',
		403	=> 'Forbidden',
		404	=> 'Not Found',
		405	=> 'Method Not Allowed',
		406	=> 'Not Acceptable',
		407	=> 'Proxy Authentication Required',
		408	=> 'Request Timeout',
		409	=> 'Conflict',
		410	=> 'Gone',
		411	=> 'Length Required',
		412	=> 'Precondition Failed',
		413	=> 'Request Entity Too Large',
		414	=> 'Request-URI Too Long',
		415	=> 'Unsupported Media Type',
		416	=> 'Requested Range Not Satisfiable',
		417	=> 'Expectation Failed',

		500	=> 'Internal Server Error',
		501	=> 'Not Implemented',
		502	=> 'Bad Gateway',
		503	=> 'Service Unavailable',
		504	=> 'Gateway Timeout',
		505	=> 'HTTP Version Not Supported'
		);

		// modified from the original
		if ($code == '' OR ! is_numeric($code))
		{
			$code = 500;
			$text = $stati[500];
		}

		$server_protocol = (isset($_SERVER['SERVER_PROTOCOL'])) ? $_SERVER['SERVER_PROTOCOL'] : FALSE;

		if (substr(php_sapi_name(), 0, 3) == 'cgi')
		{
			header("Status: {$code} {$text}", TRUE);
		}
		elseif ($server_protocol == 'HTTP/1.1' OR $server_protocol == 'HTTP/1.0')
		{
			header($server_protocol." {$code} {$text}", TRUE, $code);
		}
		else
		{
			header("HTTP/1.1 {$code} {$text}", TRUE, $code);
		}
	}
}