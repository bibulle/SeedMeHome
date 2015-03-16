<?php
/**
 * A class which provides all the functions to interact with the 
 * person features of the IMDb iPhone API.
 * 
 * @package Imdbphp
 * @class Imdbphp_Person
 * @extends Imdbphp_Base
 * @author http://code.google.com/p/imdb-php/
 * @license    http://www.opensource.org/licenses/bsd-license.php     New BSD License
 * @version 0.9.1
 */

require_once (dirname(__FILE__)."/Base.php");

class Imdbphp_Person extends Imdbphp_Base {
    /**
	 * A Person ID (nm0000000)
	 * @var string
	 */
	protected $_personId;
	####################################################
	# OVERRIDDEN METHODS                               #
	####################################################
	/**
	 * Constructor method.
	 * 
	 * @param  string       $url	A IMDb URL (e.g. www.imdb.com/name/nm0000116/, nm0000116)
	 * @return Imdbphp_Person
	 */
	public function __construct($url) 
	{	
		if (!$url) {
			throw new Exception (__METHOD__ . ': A IMDb URL is required.');
		}
		else {
		    $personId = $this->getPersonIdFromUrl($url);
		    $this->_personId = $personId;
		    // call the parent constructor
		    parent::__construct();
		}
	}
	
	####################################################
	# PUBLIC METHODS                                   #
	####################################################
	/**
	 * Get a parsed person ID from an IMDb URL.
	 * 
	 *
	 * @param  string $url 	an IMDb URL.
	 * @return string Returns parsed title ID.
	 */
	public function getPersonIdFromUrl($url)
	{
	    	if (preg_match("/nm[0-9]{7}/i", $url, $matches)) 
	    	{
	            return $matches[0];
		    }
			else{
			    throw new Exception (__METHOD__ . ': Invalid person ID.');
			}
	}
	/**
	 * Return the current person ID. 
	 *
	 * @return string	Returns current person ID
	 */
	public function getPersonId()
	{
	    return $this->_personId;
	}
	/**
	 * Get a persons maindetails formatted as a JSON string.
	 * 
	 * @return string  			JSON formatted string.
	 */
	public function getMainDetails()
	{
		$arg['nconst'] = $this->getPersonId();
		return $this->makeRequest('/name/maindetails', $arg);
	}
	
	/**
	 * Get a persons photo urls formatted as a JSON string.
	 * 
	 * @return string  			JSON formatted string.
	 */
	public function getPhotos()
	{
		$arg['nconst'] = $this->getPersonId();
		return $this->makeRequest('/name/photos', $arg);
	}
	
	/**
	 * Get a persons filmography formatted as a JSON string.
	 * 
	 * @return string  			JSON formatted string.
	 */
	public function getFilmography()
	{
		$arg['nconst'] = $this->getPersonId();
		return $this->makeRequest('/name/filmography', $arg);
	}
	
	/**
	 * Get a persons trivia formatted as a JSON string.
	 * 
	 * @return string  			JSON formatted string.
	 */
	public function getTrivia()
	{
		$arg['nconst'] = $this->getPersonId();
		return $this->makeRequest('/name/trivia', $arg);
	}
	
	/**
	 * Get a persons quotes formatted as a JSON string.
	 * 
	 * @return string  			JSON formatted string.
	 */
	public function getQuotes()
	{
		$arg['nconst'] = $this->getPersonId();
		return $this->makeRequest('/name/quotes', $arg);
	}
}
