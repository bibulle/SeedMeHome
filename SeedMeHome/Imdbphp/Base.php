<?php
/**
 * The base class to interact with the IMDb iPhone API.
 * 
 * @package Imdbphp
 * @class Imdbphp_Base
 * @author http://code.google.com/p/imdb-php/
 * @license    http://www.opensource.org/licenses/bsd-license.php     New BSD License
 * @version 0.9.1
 */
class Imdbphp_Base {
	/**#@+
	 *
	 * @var string
	 */
	/**
	 * Hardcoded Api Version.
	 */
	protected $_api = "v1";
	/**
	 * Hardcoded Application Identification.
	 */
	protected $_appId = "iphone1_1";
	/**
	 * Hardcoded Private Api Key which is needed to sign the requests.
	 */
	protected $_apiKey = "2wex6aeu6a8q9e49k7sfvufd6rhh0n";
	/**
	 * Hardcoded API Host.
	 */
	protected $_host = "app.imdb.com";
	/**
	 * Hardcoded API Policy.
	 */
	protected $_apiPolicy = "app1_1";
	/**
	 * Locale.
	 * Default is en_US.
	 * Possible Locales are: en_US, de_DE, fr_FR, pt_PT, it_IT.
	 */
	protected $_locale = "en_US";
	/**#@-*/
	
	####################################################
	# OVERRIDDEN METHODS                               #
	####################################################
	/**
	 * Constructor method.
	 * 
	 * @param  string     $locale 		(Optional) Localization Parameter in the Format en_US.
	 * @return Imdbphp_Base
	 */
	public function __construct() 
	{	
		// sets the default timezon so no php warning appears
		date_default_timezone_set('America/New_York');
		
		if (! $this->statusCheck())
		{
			throw new Exception ( 'statusCheck(): Something is not ok. Use getHello() for more information.' );
		}
	}
	
	####################################################
	# PUBLIC METHODS                                   #
	####################################################
	/**
	 * Resets the current locale. 
	 *
	 * @param string $locale 		(Optional) Localization Parameter in the Format en_US.
	 */
	public function setLocale($locale = null) 
	{
		if($locale)
		{
			if (! preg_match("/^[a-z]{2}(((-|_)[A-Z]{2}){1,2})?$/", $locale)) 
			{
				throw new Exception ( __METHOD__ . ': Format should be xx_XX. For example en_US.' );
			} else {
				$this->_locale = ( string ) $locale;
			}		
		}
		else
		{
		    $this->_locale = 'en_US';
		}
	}
	/**
	 * Return the current locale. 
	 *
	 * @return string	Returns current locale
	 */
	public function getLocale()
	{
	    return $this->_locale;
	}
	/**
	 * Checks the availability of the IMDb iPhone API. 
	 *
	 * @return bool Returns true if everything is alright, false if something is wrong.
	 */
	public function statusCheck() 
	{
		if ( function_exists('json_decode') )
		{
			$arg['date'] = $date;
			$arg['location'] = $location;
			$arg['app_version'] = "1.1";
			$arg['count'] = "1";
			$arg['device_model'] = "iPhone";
			$arg['system_name'] = "iPhone OS";
			$arg['system_version'] = "3.1.2";
			$json = json_decode($this->makeRequest('/hello', $arg));
			return (strcmp( $json->{'data'}->{'status'}, "ok" ) == 0) ? true : false;
		} else {
			throw new Exception ( __METHOD__ . ": There is no json_decode() present. Check your php installation." );
		}
	}
		
	###########################################################
	# PROTECTED METHOD                                        #
	###########################################################
	/**
	 * Makes the Request
	 * 
	 * This internal method is called by every function which makes an request to the IMDb iPhone API.
	 * It also checks if the request was successfull by text decoding the answer. The json_decode is required
	 * in order to test.
	 * 
	 * @param  string $function   				The function which the request is regarded to.
	 * @param  string $arguments    			The additional arguments for the functions.
	 * @return string 							Returns the content as a JSON formatted string.
	 */
	protected function makeRequest($function, $arguments = null)
	{
		if (! function_exists('json_decode') )
		{
			throw new Exception ( "makeRequest(): There is no json_decode() present. Check your php installation." );
		} else if( !$function ) {
			throw new Exception ( "makeRequest(): There is no function or argument present." );
		} else {
			$parameter = $this->createParameter($arguments);
    		$baseUrl = $this->createBaseUrl($function, $parameter);
    		$signedUrl = $this->createSignedUrl($baseUrl);
    		$json = file_get_contents($signedUrl,0,null,null);
    		if(! $json )
    		{
    			throw new Exception ( __METHOD__ . ": There is a problem with file_get_contents()." );
    		} else if( ! json_decode($json) ) {
    			throw new Exception ( __METHOD__ . ": There is a problem in the json string." );
    		} else {
    			return $json;
    		}
		}
	}
	
	/**
	 * Creates the parameter for the base url.
	 * 
	 * This internal method is called by makeRequest() in order to create all parameter and sort them.
	 * 
	 * @param  string $arguments    (Optional)	The additional arguments for the functions.
	 * @return array 							Returns the parameter as a key-sorted array.
	 */
	protected function createParameter($arguments = null) {
		$parameter['api'] = $this->_api;
		$parameter['appid'] = $this->_appId;
		$parameter['device'] = sha1(microtime(true));
		$parameter['locale'] = $this->_locale;
		$parameter['timestamp'] = time();
		if(is_array($arguments))
		{
			$parameter = array_merge($parameter, $arguments);
			ksort($parameter);
		}
		return $parameter;
	}
	
	/**
	 * Creates the base url with everything but the signature.
	 * 
	 * This internal method is called by makeRequest() in order to make the base url.
	 * 
	 * @param  string $function 	The function which the request is regarded to.
	 * @param  string $parameter	The parameter for the url.
	 * @return string 				Returns the base url ready to sign.
	 */
	protected function createBaseUrl($function, $parameter) {
		if( ! $function || ! $parameter )
		{
			throw new Exception ( __METHOD__ . ": Function and parameter are required." );
		} else {
			$baseUrl = 			'http://'
								. $this->_host
								. $function
								. '?';
			foreach($parameter as $key => $value)
			{
				$baseUrl .= $key . '=' . $value . '&';
			}
			
			$baseUrl		.= 'sig='
							. $this->_apiPolicy;
			return $baseUrl;
		}
	}
	
	/**
	 * Creates the signed url.
	 * 
	 * It makes the sha1 hash of the base url and appends it to the base url.
	 * This internal method is called by makeRequest() in order to sign the base url with the sha1.
	 * 
	 * @param  string $baseUrl	The base url which is to be signed.
	 * @return string 			Returns the complete signed url.
	 */
	protected function createSignedUrl($baseUrl) {
		if( ! $baseUrl )
		{
			throw new Exception ( __METHOD__ . ": The base url is required." );
		} else {
			return $baseUrl . '-' . hash_hmac('sha1', $baseUrl, $this->_apiKey);
		}
	}
}
