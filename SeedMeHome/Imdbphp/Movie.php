<?php
/**
 * A class which provides all the functions to interact with the 
 * movie/series features of the IMDb iPhone API.
 * 
 * @package Imdbphp
 * @class Imdbphp_Movie
 * @extends Imdbphp_Base
 * @author http://code.google.com/p/imdb-php/
 * @license    http://www.opensource.org/licenses/bsd-license.php     New BSD License
 * @version 0.9.1
 */

require_once (dirname(__FILE__)."/Base.php");

class Imdbphp_Movie extends Imdbphp_Base {
	/**
	 * A title ID (tt0000000)
	 * @var string
	 */
	protected $_titleId;
	####################################################
	# OVERRIDDEN METHODS                               #
	####################################################
	/**
	 * Constructor method.
	 * 
	 * @param  string    $url	A IMDb URL (e.g. www.imdb.com/title/tt0499549/, tt0499549)
	 * @return Imdbphp_Movie
	 */
	public function __construct($url) 
	{	
		if (!$url) {
			throw new Exception (__METHOD__ . ': A IMDb URL is required.');
		}
		else {
		    $titleId = $this->getTitleIdFromUrl($url);
		    $this->_titleId = $titleId;
		    // call the parent constructor
		    parent::__construct();
		}
	}
	
	####################################################
	# PUBLIC METHODS                                   #
	####################################################
	/**
	 * Get a parsed title ID from an IMDb URL.
	 * 
	 *
	 * @param  string $url 	an IMDb URL.
	 * @return string Returns parsed title ID.
	 */
	public function getTitleIdFromUrl($url)
	{
	    	if (preg_match("/tt[0-9]{7}/i", $url, $matches)) 
	    	{
	            return $matches[0];
		    }
			else{
			    throw new Exception (__METHOD__ . ': Invalid title ID.');
			}
	}
	/**
	 * Return the current title ID. 
	 *
	 * @return string	Returns current title ID
	 */
	public function getTitleId()
	{
	    return $this->_titleId;
	}
	/**
	 * Get the maindetails for a movie/series formatted as a JSON string.
	 * 
	 * @return string  			JSON formatted string.
	 */
	public function getMainDetails()
	{
		$arg['tconst'] = $this->getTitleId();
		return $this->makeRequest('/title/maindetails', $arg);
	}
	
	/**
	 * Get the photo urls for a movie/series formatted as a JSON string.
	 *
	 * @return string  			JSON formatted string.
	 */
	public function getPhotos()
	{
		$arg['tconst'] = $this->getTitleId();
		return $this->makeRequest('/title/photos', $arg);
	}
	
	/**
	 * Get the plot summary for a movie/series formatted as a JSON string.
	 * 
	 * @return string  			JSON formatted string.
	 */
	public function getPlotSummary()
	{
		$arg['tconst'] = $this->getTitleId();
		return $this->makeRequest('/title/plot', $arg);
	}
	
	/**
	 * Get the synopsis for a movie/series formatted as a JSON string.
	 *
	 * @return string  JSON formatted string.
	 */
	public function getSynopsis()
	{
		$arg['tconst'] = $this->getTitleId();
		return $this->makeRequest('/title/synopsis', $arg);
	}
	
	/**
	 * Get the complete cast & crew list for a movie/series formatted as a JSON string.
	 * 
	 * @return string  			JSON formatted string.
	 */
	public function getAllCast()
	{
		$arg['tconst'] = $this->getTitleId();
		return $this->makeRequest('/title/fullcredits', $arg);
	}
	
	/**
	 * Get the external review url list for a movie/series formatted as a JSON string.
	 * 
	 * @return string  			JSON formatted string.
	 */
	public function getExternalReviews()
	{
		$arg['tconst'] = $this->getTitleId();
		return $this->makeRequest('/title/external_reviews', $arg);
	}
	
	/**
	 * Get the user review url list for a movie/series formatted as a JSON string.
	 * 
	 * @return string  			JSON formatted string.
	 */
	public function getUserReviews()
	{
		$arg['tconst'] = $this->getTitleId();
		return $this->makeRequest('/title/usercomments', $arg);
	}
	
	/**
	 * Get the parental guide for a movie/series formatted as a JSON string.
	 * 
	 * @return string  			JSON formatted string.
	 */
	public function getParentalGuide()
	{
		$arg['tconst'] = $this->getTitleId();
		return $this->makeRequest('/title/parentalguide', $arg);
	}
	
	/**
	 * Get the trivia for a movie/series formatted as a JSON string.
	 * 
	 * @return string  			JSON formatted string.
	 */
	public function getTrivia()
	{
		$arg['tconst'] = $this->getTitleId();
		return $this->makeRequest('/title/trivia', $arg);
	}
	
	/**
	 * Get the quotes for a movie/series formatted as a JSON string.
	 * 
	 * @return string  			JSON formatted string.
	 */
	public function getQuotes()
	{
		$arg['tconst'] = $this->getTitleId();
		return $this->makeRequest('/title/quotes', $arg);
	}
	
	/**
	 * Get the goofs for a movie/series formatted as a JSON string.
	 * 
	 * @return string  			JSON formatted string.
	 */
	public function getGoofs()
	{
		$arg['tconst'] = $this->getTitleId();
		return $this->makeRequest('/title/goofs', $arg);
	}
	
	/**
	 * Get a episode list sorted by season for a specified series formatted as a JSON string.
	 * 
	 * @return string  			JSON formatted string.
	 */
	public function getEpisodesBySeason()
	{
		$arg['tconst'] = $this->getTitleId();
		return $this->makeRequest('/title/episodes', $arg);
	}
}
