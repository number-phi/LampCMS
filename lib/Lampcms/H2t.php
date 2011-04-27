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


/**
 * Class to perform html to plaintext
 * transformation
 *
 * Use it like this:
 *
 * $oHTML2TEXT = H2t::factory($inputHTML);
 * echo $oHTML; // will output formatted plaintext
 *
 * or to get the result as a string call getText() method
 *
 * $oHTML2TEXT = H2t::factory($inputHTML);
 * $text = $oHTML->getText(); // will return formatted plaintext
 *
 *
 *
 * @author Dmitri Snytkine
 *
 */
class H2t
{
	/**
	 * Configuration array
	 * for the html_tidy
	 *
	 * @var array
	 */
	protected $aTidyConfig = array(
			'clean'         => true,
            'indent'         => true,
            'output-xhtml'   => true,
			'wrap' => 0);

	/**
	 * Object of type DOMDocument
	 * that holds the input html
	 *
	 * @var object
	 */
	protected $oInputDom;

	
	/**
	 * Object of type XSLProcessor
	 *
	 * @var object
	 */
	protected $oXSL;

	
	/**
	 * Location of template file
	 * must point to plaintext.xsl file
	 *
	 * @var string
	 */
	protected $templateFile = 'plaintext.xsl';

	
	/**
	 * Constructor, cannot be called
	 * directly, only via factory!
	 *
	 * @return void
	 */
	protected function __construct(){
		if(!extension_loaded('xsl')){
			
			throw new Exception('php_xsl extension not loaded. If you are on Windows, please uncomment ;extension=php_xsl.dll in your php.ini');
		}
		
		$this->oInputDom = new \DOMDocument();
		$this->oInputDom->recover = true;
		$this->makeXslProcessor();
	}

	
	/**
	 * Factory method to make the object,
	 * load html string
	 * object will be ready, can just
	 * call echo $obj to get result of transformation
	 *
	 * @param string $sHtml input html string
	 * @return object of this class
	 */
	public static function factory($sHtml){
		$o = new self();
		$o->loadHtml($sHtml);

		return $o;
	}

	
	/**
	 * Loads the input html string
	 * into the DOMDocument object
	 * if tidy is available it will first
	 * run the input through tidy
	 * to fix the html string (in case it's not a valid html)
	 *
	 * @important the DOMDocument
	 * expects the html to be in ISO-8859-1 charset
	 * but will use the meta tag
	 * <META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=utf-8">
	 *
	 * So if your html string is in utf-8 format, then it must contain this header
	 * and like this:
	 *
	 * <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN"
	 "http://www.w3.org/TR/REC-html40/loose.dtd">
	 <head>
	 <META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=utf-8">
	 </head>
	 <body><div>'.$sHtml.'</div></body></html>
	 *
	 *OR it must just be in ISO-8859-1 format but then
	 *it MUST NOT contain any Content-Type meta tag
	 * or correctly have the charset set to ISO-8859-1
	 *
	 * @param string $sHtml
	 *
	 * @return object $this
	 *
	 * @throws HTML2TextException if unable to load html string
	 * into DOM
	 */
	public function loadHtml($sHtml){

		if(extension_loaded('tidy')){
			$tidy = new \tidy;
			$tidy->parseString($sHtml, $this->aTidyConfig);
			$tidy->cleanRepair();
			$sHtml = $tidy->value;
		}

		if (false === @$this->oInputDom->loadHTML($sHtml)) {
			throw new HTML2TextException('Invalid input: this is not valid HTML: '.$sHtml);
		}

		$this->oInputDom->normalizeDocument();

		return $this;
	}


	protected function makeXslProcessor(){
		$xsl = new \DOMDocument;
		$tpl = LAMPCMS_PATH.DIRECTORY_SEPARATOR.$this->templateFile;
		if(!is_file($tpl)){
			throw new HTML2TextException('XSL template not found here: '.$tpl);
		}


		if(!$xsl->load($tpl)){
			throw new HTML2TextException('Unable to load xsl template: '.$this->templateFile);
		}

		$this->oXSL = new \XSLTProcessor;
		$this->oXSL->registerPHPFunctions();
		$this->oXSL->importStyleSheet($xsl);

		return $this;
	}


	/**
	 * Performes the transformation
	 * and sets the $this->sOutput
	 * wraps text so that lines
	 * are not wider than 75 chars
	 *
	 * @return string output text
	 * @throws HTML2TextException if unable to transform to text
	 */
	public function getText(){
		if(false === $text = $this->oXSL->transformToXML($this->oInputDom)){
			throw new HTML2TextException('Unable to transform to text');
		}

		return wordwrap($text);
	}

	
	/**
	 * Magic method to enable echoing
	 * of this object
	 *
	 * @return string result of getText()
	 */
	public function __toString(){
		return $this->getText();
	}
}


?>
