<?php
/**
 *
 * License, TERMS and CONDITIONS
 *
 * This software is lisensed under the GNU LESSER GENERAL PUBLIC LICENSE (LGPL) version 3
 * Please read the license here : http://www.gnu.org/licenses/lgpl-3.0.txt
 *
 *  Redistribution and use in source and binary forms, with or without
 *  modification, are permitted provided that the following conditions are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 * 3. The name of the author may not be used to endorse or promote products
 *    derived from this software without specific prior written permission.
 *
 * ATTRIBUTION REQUIRED
 * 4. All web pages generated by the use of this software, or at least
 * 	  the page that lists the recent questions (usually home page) must include
 *    a link to the http://www.lampcms.com and text of the link must indicate that
 *    the website's Questions/Answers functionality is powered by lampcms.com
 *    An example of acceptable link would be "Powered by <a href="http://www.lampcms.com">LampCMS</a>"
 *    The location of the link is not important, it can be in the footer of the page
 *    but it must not be hidden by style attibutes
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR "AS IS" AND ANY EXPRESS OR IMPLIED
 * WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE FREEBSD PROJECT OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF
 * THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This product includes GeoLite data created by MaxMind,
 *  available from http://www.maxmind.com/
 *
 *
 * @author     Dmitri Snytkine <cms@lampcms.com>
 * @copyright  2005-2011 (or current year) ExamNotes.net inc.
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt GNU LESSER GENERAL PUBLIC LICENSE (LGPL) version 3
 * @link       http://www.lampcms.com   Lampcms.com project
 * @version    Release: @package_version@
 *
 *
 */


namespace Lampcms;

const JS_MIN_ID = '051220112';

const LF = "\n";
const CR = "\r";
const CRLF = "\r\n";
const DS = DIRECTORY_SEPARATOR;
const HR = '<hr/>';
const LINE = "\n---------------------------------------------------------------\n";
const BR = '<br/>';
const LB = "\n<br/>";



/**
 * name of directory where the index.php
 * is located. This is NOT a full path, just a directory name!
 * Default is www and should not be changed unless
 * you must have specific name of your root www dir
 * maybe a web host requires this directory
 * to be named 'htdocs' or something else, then you would
 * copy everything from 'www' to this 'htdocs' and then
 * put 'htdocs' as a value of WWW_DIR here
 */
const WWW_DIR = 'www';


/**
 * These constants should not be changed
 * by user. These represent the basic
 * layout of the CMS and there
 * is no good reason to change them.
 *
 * The things that can possibly change are in
 * the !config.ini file, for example
 * AVATAR_IMG_SITE and IMG_SITE
 * can changed because they can be hosted on a
 * remote server somewhere, but these relative paths
 * shoud be the same even if avatars and images
 * are hosted on a different remote servers
 *
 */
const thum = 'thum';

const tiny = 'tiny';

const work = 'work';

const sqr = 'sqr';

const orig = 'orig';

const att = 'att';

const CODING_EMAILS = 'us-ascii';

const SUFFIX_PRIVATE = '-private';

const SMILIES_PATH = '/images/smiles';

const DIR_TEMPLATES = 'templates';

const PATH_WWW_CSS = '/w/u/ucss/';

const PATH_WWW_IMG_AVATAR = '/w/img/avatar/';

const PATH_WWW_IMG_AVATAR_SQUARE = '/w/img/avatar/sqr/';

const PATH_WWW_IMG_AVATAR_TINY = '/w/img/avatar/tiny/';

const PATH_WWW_IMG_WORK = '/w/img/work/';

const PATH_WWW_IMG_THUM = '/w/img/thum/';

const PATH_WWW_IMG_SQR = '/w/img/sqr/';

const ATTACHMENTS_DIR_WWW = '/w/attachments/';

const ATT_DIR_WWW = '/w/att/';

const PATH_WWW_IMG_MOBILE = '/w/img/tiny/';

const DIR_XXX = 'xxx';

/**
 * If server does not have fastcgi_finish_request function
 * then a special NO_FFR flag is defined
 * in which case we execute this callable
 * function now,
 * otherwise defer execution as a shutdown function
 *
 * @param unknown_type $callable
 *
 */
function runLater(\Closure $callable){
	if(!is_callable($callable)){
		throw new Exception('param passed to runLater must be a callable function. Was: '.\gettype($callable));
	}

	register_shutdown_function($callable);
	/*if(defined('NO_FFR')){
		$callable();
	} else {
		register_shutdown_function($callable);
	}*/
}

/**
 * Array of stopwords
 * These words will be excluded
 * from tokenizer result
 *
 * @var array
 */
function getStopwords(){
	return array(
	"a","ah","an","and","are","aren","arent","as","at","b","be","but","by","c","cant","co","d","did","didnt","do","dont","e","eg","et","f","ff","for","g","go","he","hes","hi","i","if","ill","in","is","isnt","it","itd","its","ive","j","k","l","ll","m","me","mr","mrs","n","na","nd","no","o","of","oh","or","p","pp","q","qv","r","rd","re","s","shes","so","t","th","that","thatll","thats","thatve","the","them","theyd","this","those","til","to","too","ts","u","un","v","ve","vs","w","wasnt","we","were","wont","wouldnt","x","y","z"
	);
}


/**
 * Array of reserved accounts
 * User will not be allowed to register with
 * these accounts
 * @var array
 */
function getReservedNames(){
	return array(
'user',
'lists',
'groups',
'forums',
'forum',
'news',
'events',
'event',
'ext',
'feeds',
'blog',
'blogs',
'archive',
'account', 
'member', 
'admin', 
'moderator', 
'administrator',
'support',
'www',
'email',
'chat',
'save',
'www2',
'dev',
'titan',
'starfire',
'ftp',
'webdav',
'webmail',
'dev2',
'pear',
'abuse',
'tos',
'stream',
'helix',
'mail');
}

/**
 *
 * Prepare email headers
 * @param array $aHeaders in the header name/value format
 */
function prepareHeaders(array $aHeaders){
	$ret = '';
	foreach($aHeaders as $key=>$val){
		$ret .= "$key: $val \n";
	}

	return $ret;
}

/**
 * Check to see if User $user is owner of Resource $resource
 * @param Resource $resource
 * @param User $user
 *
 * @return bool true if User is owner of Resource
 */
function isOwner(User $user, \Lampcms\Interfaces\LampcmsResource $resource ){
	$uid = $user->getUid();

	return (($uid > 0 )  && $uid === $resource->getOwnerId());
}

/**
 * Base class for all custom objects
 * well, not really all, but
 * many of them, especially
 * the String and Array objects
 *
 * @Important Always include this file, it contains
 * several classes that we always need
 *
 * @author Dmitri Snytkine
 *
 */
class LampcmsObject implements Interfaces\LampcmsObject
{
	/**
	 * Every LampcmsObject has Registry object
	 *
	 * @var unknown_type
	 */
	protected $oRegistry;

	/**
	 * Default constructor
	 *
	 * @todo We really MUST require all LampcmsObjects to
	 * follow the same constructor patters where it only accepts
	 * Registry object.
	 * But requiring it by making this method 'final'
	 * will make this object a lot less flexible.
	 * So, for now it is up to the concrete class to make
	 * their own constructor
	 * BUT, this is IMPORTANT: a concrete class
	 * should alwasy take the oRegistry as the first param
	 * and make all other params optional (have default values)
	 *
	 * @param object Registry $oRegistry
	 */
	/*public function __construct(Registry $oRegistry){
		$this->oRegistry = $oRegistry;
		}*/

	/**
	 * Every LampcmsObject can be easily created via factory
	 * and it does not have to have its own factory
	 * or constructor
	 *
	 * @param Registry $oRegistry
	 */
	public static function factory(Registry $oRegistry){
		return new static($oRegistry);
	}

	
	/**
	 * Get unique hash code for the object
	 * This code uniquely identifies an object,
	 * even if 2 objects are of the same class
	 * and have exactly the same properties, they still
	 * are uniquely identified by php
	 *
	 * @return string
	 */
	public function hashCode(){
		return spl_object_hash($this);
	}

	
	/**
	 * Getter of the class name
	 * @return string the class name of this object
	 */
	public function getClass(){
		return get_class($this);
	}

	
	/**
	 * Outputs the name and uniqe code of this object
	 * @return string
	 */
	public function __toString(){
		return 'object of type: '.$this->getClass().' hashCode: '.$this->hashCode();
	}
	
	/**
	 * Getter for $this->oRegistry
	 * 
	 * @return object of type Registry
	 */
	public function getRegistry(){
		return $this->oRegistry;
	}

}



/**
 * Array object
 * whith few extra methods
 *
 * @author Dmitri Snytkine
 *
 */
class LampcmsArray extends \ArrayObject implements \Serializable, Interfaces\LampcmsObject
{

	public function __isset($name){
		return $this->offsetExists($name);
	}

	public function __unset($name){
		return $this->offsetUnset($name);
	}

	
	/**
	 * Some functions must just return
	 * result of calling a functions
	 * for example array_key_exists() should return the result
	 * of calling this function on our array
	 *
	 * Some functions should return array
	 *
	 * Some functions should modify our array (exchangeArray())
	 * and return $this
	 *
	 * Some functions should return array as returned by a function
	 * for example array_keys should return array of keys from our array
	 *
	 * @param $name
	 * @param $arguments
	 * @return unknown_type
	 */
	public function __call($name, $arguments){
		/**
		 * These functions don't return anything
		 * they modify current array
		 * @var unknown_type
		 */
		$aPhpFunctions = array('sort', 'merge', 'array_change_key_case', 'shuffle');

		/**
		 * These function return result of
		 * function call and don't require
		 * any params
		 */
		$aReturnFunctions = array('array_keys', 'array_flip', 'array_rand', 'array_values', 'end', 'in_array', 'array_unique');


	}

	
	/**
	 * (non-PHPdoc)
	 * @see ArrayObject::serialize()
	 */
	public function serialize(){
		$a = $this->getArrayCopy();

		return serialize($a);
	}

	
	/**
	 * (non-PHPdoc)
	 * @see ArrayObject::unserialize()
	 */
	public function unserialize($serialized){
		$a = unserialize($serialized);
		$this->exchangeArray($a);
	}

	
	/**
	 * Merges the input array with existing
	 * array. But instead of using array_merge,
	 * it uses the offsetSet() from the foreach loop
	 * This is because the other way to do this is
	 * probably even less efficient - (get
	 * array copy, then merge then exchangeArray)
	 *
	 * Values from input array override values
	 * in existing array if keys are the same.
	 *
	 * @param array $a
	 * @return object $this
	 */
	public function addArray(array $a){
		foreach($a as $key => $val){
			$this->offsetSet($key, $val);
		}

		return $this;
	}


	/**
	 * Merge the array represented in the object
	 * with the input array and return the result array
	 * @param mixed $arr array or ArrayObject object
	 * @return array a result array
	 *
	 * @throws InvalidArgumentException if argument is not an array and not ArrayObject object
	 */
	public function getMerged($arr){
		if (is_array($arr)) {
			return \array_merge($this->getArrayCopy(), $arr);
		} elseif (\is_object($arr)) {
			if ($arr instanceof ArrayObject) {
				return \array_merge($this->getArrayCopy(), $arr->getArrayCopy());
			}

			throw new \InvalidArgumentException('getMerged argumet object MUST be of type ArrayObject');
		}

		throw new \InvalidArgumentException('getMerged argument can only be array or object of type ArrayObject');
	}


	/**
	 * Get unique hash code for the object
	 * This code uniquely identifies an object,
	 * even if 2 objects are of the same class
	 * and have exactly the same properties, they still
	 * are uniquely identified by php
	 *
	 * @return string
	 */
	public function hashCode(){
		return spl_object_hash($this);
	}

	
	/**
	 * Getter of the class name
	 * @return string the class name of this object
	 */
	public function getClass(){
		return get_class($this);
	}


	/**
	 * (non-PHPdoc)
	 * @see Lampcms\Interfaces.LampcmsObject::__toString()
	 */
	public function __toString(){
		return 'object of type: '.$this->getClass().' hashCode: '.$this->hashCode().' with array: '.print_r($this->getArrayCopy(), true);
	}

}


/**
 *
 */
class ArrayDefaults extends LampcmsArray
{

	const DEFAULT_VAL = null;

	protected $defaultValue = self::DEFAULT_VAL;

	/**
	 * Constructor
	 * 
	 * @param array $a underlying array represented by this object
	 * @param string $defaultVal value to return in case
	 * the element does not exist in array
	 */
	public function __construct(array $a = array(), $defaultVal = null){
		parent::__construct($a);
		$this->defaultValue = $defaultVal;
	}

	
	/**
	 * Redefine offsetExists to allways return true
	 * this way a request for $obj['blabla']
	 * will NOT raise error or warning if blabla does
	 * not exist.
	 *
	 * @param strint $name
	 *
	 * @return true
	 */
	public function offsetExists($name){
		return true;
	}

	
	/**
	 * (non-PHPdoc)
	 * @see Lampcms.LampcmsArray::__isset()
	 */
	public function __isset($name){
		return $this->checkOffset($name);
	}

	
	/**
	 * This checks wheather index really exists
	 * since we can no longer rely on the
	 * offsetExists() in this object,
	 * we are asking a parent object
	 * 
	 * It ONLY works properly
	 * with this object and does not work
	 * in sub-class because in sub-class parent
	 * becomes THIS class and calling offsetExists
	 * on THIS class always returns true!
	 * 
	 * Ideally this function should be eliminated
	 *
	 * @param string $name
	 * @return bool
	 */
	public final function checkOffset($name){
		return parent::offsetExists($name);
	}

	
	/**
	 * Redefine offsetGet to return defaultValue
	 * if index $name does not actually exists.
	 * This way the $obj['blabla'] will return
	 * the value of $this->defaultValue
	 * instead of raising error
	 *
	 * @param string $name
	 * @return unknown
	 */
	public function offsetGet($name){
		if (parent::offsetExists($name)) {

			return parent::offsetGet($name);
		}

		return $this->defaultValue;
	}

	
	/**
	 * Setter for $this->defaultValue
	 *
	 * @param mixed $val
	 * @return object $this
	 */
	public function setDefaultValue($val){
		$this->defaultValue = $val;

		return $this;
	}

	
	/**
	 * Getter method for $this->defaultValue
	 * this is not very usefull, usually only used
	 * when you want to see what the defaultValue is
	 * (mostly during debugging)
	 *
	 * @return value of $this->defaultValue
	 */
	public function getDefaultValue(){
		return $this->defaultValue;
	}

	
	/**
	 * Resets the value of $this->defaultValue
	 * to value of DEFAULT_VAL constant
	 *
	 * @return object $this
	 */
	public function resetDefaultValue(){
		$this->defaultValue = self::DEFAULT_VAL;

		return $this;
	}

	
	/**
	 * This method lets you get undefined array keys as
	 * object properties
	 * for example if 'gagaga' key does not exist,
	 * $obj->gagaga
	 * will not raise errors and instead return
	 * $this->defaultValue
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function __get($name){
		return $this->offsetGet($name);
	}


	/**
	 * (non-PHPdoc)
	 * @see Lampcms.LampcmsArray::serialize()
	 */
	public function serialize(){
		return serialize(array('array' => $this->getArrayCopy(), 'default' => $this->defaultValue));
	}

	
	/**
	 * (non-PHPdoc)
	 * @see Lampcms.LampcmsArray::unserialize()
	 */
	public function unserialize($serialized){
		$a = unserialize($serialized);
		$this->exchangeArray($a['array']);
		$this->defaultValue = $a['default'];
	}

}

