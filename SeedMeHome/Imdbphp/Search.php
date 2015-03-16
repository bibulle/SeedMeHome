<?php
/**
 * A class which provides all the functions to interact with the 
 * search feature of the IMDb iPhone API.
 * 
 * @package Imdbphp
 * @class Imdbphp_Search
 * @extends Imdbphp_Base
 * @author http://code.google.com/p/imdb-php/
 * @license    http://www.opensource.org/licenses/bsd-license.php     New BSD License
 * @version 0.9.1
 */

require_once (dirname(__FILE__)."/Base.php");

class Imdbphp_Search extends Imdbphp_Base {
	####################################################
	# OVERRIDDEN METHODS                               #
	####################################################
	/**
	 * Constructor method.
	 * 
	 * @return Imdbphp_Search
	 */
	public function __construct() 
	{	
		// call the parent constructor
		parent::__construct();
	}
	
	####################################################
	# PUBLIC METHODS                                   #
	####################################################
	/**
	 * Makes the search request and returns the result 
	 *
	 * @param  string $searchterm 	Search term for which should be searched.
	 * @return string $searchterm 	Returns the search results as a JSON formatted string.
	 */
	public function getSearchResults($searchterm)
	{
		if(!$searchterm)
		{
			throw new Exception (__METHOD__ . ': A searchterm is required.');
		} else {
			$q['q'] = urlencode($searchterm);
			return $this->makeRequest('/find', $q);
		}
	}
}
