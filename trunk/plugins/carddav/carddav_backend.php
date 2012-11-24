<?php

/**
 * CardDAV PHP
 *
 * simple CardDAV query
 * --------------------
 * $carddav = new carddav_backend('https://davical.example.com/user/contacts/');
 * $carddav->set_auth('username', 'password');
 * echo $carddav->get();
 *
 *
 * Simple vCard query
 * ------------------
 * $carddav = new carddav_backend('https://davical.example.com/user/contacts/');
 * $carddav->set_auth('username', 'password');
 * echo $carddav->get_vcard('0126FFB4-2EB74D0A-302EA17F');
 *
 *
 * XML vCard query
 * ------------------
 * $carddav = new carddav_backend('https://davical.example.com/user/contacts/');
 * $carddav->set_auth('username', 'password');
 * echo $carddav->get_xml_vcard('0126FFB4-2EB74D0A-302EA17F');
 *
 *
 * Check CardDAV server connection
 * -------------------------------
 * $carddav = new carddav_backend('https://davical.example.com/user/contacts/');
 * $carddav->set_auth('username', 'password');
 * var_dump($carddav->check_connection());
 *
 *
 * CardDAV delete query
 * --------------------
 * $carddav = new carddav_backend('https://davical.example.com/user/contacts/');
 * $carddav->set_auth('username', 'password');
 * $carddav->delete('0126FFB4-2EB74D0A-302EA17F');
 *
 *
 * CardDAV add query
 * --------------------
 * $vcard = 'BEGIN:VCARD
 * VERSION:3.0
 * UID:1f5ea45f-b28a-4b96-25as-ed4f10edf57b
 * FN:Christian Putzke
 * N:Christian;Putzke;;;
 * EMAIL;TYPE=OTHER:christian.putzke@graviox.de
 * END:VCARD';
 *
 * $carddav = new carddav_backend('https://davical.example.com/user/contacts/');
 * $carddav->set_auth('username', 'password');
 * $vcard_id = $carddav->add($vcard);
 *
 *
 * CardDAV update query
 * --------------------
 * $vcard = 'BEGIN:VCARD
 * VERSION:3.0
 * UID:1f5ea45f-b28a-4b96-25as-ed4f10edf57b
 * FN:Christian Putzke
 * N:Christian;Putzke;;;
 * EMAIL;TYPE=OTHER:christian.putzke@graviox.de
 * END:VCARD';
 *
 * $carddav = new carddav_backend('https://davical.example.com/user/contacts/');
 * $carddav->set_auth('username', 'password');
 * $carddav->update($vcard, '0126FFB4-2EB74D0A-302EA17F');
 *
 *
 * CardDAV server list
 * -------------------
 * DAViCal: https://example.com/{resource|principal|username}/{collection}/
 * Apple Addressbook Server: https://example.com/addressbooks/users/{resource|principal|username}/{collection}/
 * memotoo: https://sync.memotoo.com/cardDAV/
 * SabreDAV: https://example.com/addressbooks/{resource|principal|username}/{collection}/
 * ownCloud: https://example.com/apps/contacts/carddav.php/addressbooks/{resource|principal|username}/{collection}/
 * SOGo: http://sogo-demo.inverse.ca/SOGo/dav/{resource|principal|username}/Contacts/{collection}/
 *
 *
 * @author Christian Putzke <christian.putzke@graviox.de>
 * @copyright Christian Putzke @ Graviox Studios
 * @link http://www.graviox.de/
 * @link https://twitter.com/graviox/
 * @since 20.07.2011
 * @version 0.5.1
 * @license http://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 *
 */

class carddav_backend
{
	/**
	 * CardDAV PHP Version
	 *
	 * @constant string
	 */
	const VERSION = '5.0 (Windows NT 6.1; WOW64; rv:10.0.8) Gecko/20121005 Thunderbird/10.0.8 Lightning/1.2.1';//1.0';

	/**
	 * User agent displayed in http requests
	 *
	 * @constant string
	 */
	const USERAGENT = 'Mozilla/';//CardDAV PHP/';

	/**
	 * CardDAV server url
	 *
	 * @var string
	 */
	private $url = null;

	/**
	 * CardDAV server url_parts
	 *
	 * @var array
	 */
	private $url_parts = null;

	/**
	 * Custom headers
	 *
	 * @var mixed
	 */
  private $headers = null;

	/**
	 * Authentication string
	 *
	 * @var string
	 */
	private $auth = null;
	
	/**
	 * Authentication Method string
	 *
	 * @var array
	 */
	private $auth_method = array();

	/**
	* Authentication: username
	*
	* @var string
	*/
	private $username = null;

	/**
	* Authentication: password
	*
	* @var string
	*/
	private $password = null;

	/**
	 * Characters used for vCard id generation
	 *
	 * @var array
	 */
	private $vcard_id_chars = array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 'A', 'B', 'C', 'D', 'E', 'F');

	/**
	 * Failsafe Id
	 *
	 * @var boolean
	 */
  private $failsafe_id = null;

	/**
	 * Current id for the running request
	 *
	 * @var string
	 */
  private $current_id = null;

	/**
	 * CardDAV server connection (curl handle)
	 *
	 * @var resource
	 */
	private $curl;
	
	/**
	 * File extension
	 *
	 * @var string
	 */
	private $ext = '.vcf';

	/**
	 * Sleep after PUT request instead of waiting for response
	 *
	 * @var integer
	 */
	private $sleep = null;

	/**
	 * Constructor
	 * Sets the CardDAV server url
	 *
	 * @param	string	$url	CardDAV server url
	 * @param string  $ext  vCard file extension
	 * @param boolean $wait 
	 * @return	void
	 */
	public function __construct($url = null, $ext = 'vcf', $sleep = null, $failsafe_id = true)
	{
		if ($url !== null)
		{
			$this->set_url($url);
			$this->sleep = $sleep;
			$this->failsafe_id = $failsafe_id;
			if(class_exists('carddav_plus')){
			  $this->ext = carddav_plus::carddav_ext($url, $ext);
			}
		  else{
		    $this->ext = '.' . $ext;
		  }
		}
	}

	/**
	* Sets the CardDAV server url
	*
	* @param	string	$url	CardDAV server url
	* @return	void
	*/
	public function set_url($url)
	{
		$this->url = $url;

		if (substr($this->url, -1, 1) !== '/')
		{
			$this->url = $this->url . '/';
		}

		$this->url_parts = parse_url($this->url);
	}

	/**
	 * Sets authentication string
	 *
	 * @param	string	$username	CardDAV server username
	 * @param	string	$password	CardDAV server password
	 * @return	void
	 */
	public function set_auth($username, $password)
	{
		$this->username = $username;
		$this->password = $password;
		$this->auth = $username . ':' . $password;
	}

	/**
	 * Gets propfind XML response from the CardDAV server
	 *
	 * @param	boolean	$include_vcards		vCards include vCards in the response (simplified only)
	 * @param	boolean	$raw				Get response raw or simplified
	 * @return	string						Raw or simplified XML response
	 */
	public function get($include_vcards = true, $raw = false)
	{
    $content = '<?xml version="1.0" encoding="utf-8" ?><D:sync-collection xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:carddav"><D:sync-token></D:sync-token><D:prop><D:getcontenttype/><D:getetag/><D:allprop/><C:address-data><C:allprop/></C:address-data></D:prop><C:filter/></D:sync-collection>';
    $content_type = 'application/xml';
    $response = $this->query($this->url, 'REPORT', $content, $content_type);
		
    if(!$response){
      $response = $this->query($this->url, 'PROPFIND');
    }
		if ($response === false || $raw === true)
		{
			return $response;
		}
		else
		{
			return $this->simplify($response, $include_vcards);
		}
	}

	/**
	* Gets a clean vCard from the CardDAV server
	*
	* @param	string	$vcard_id	vCard id on the CardDAV server
	* @return	string				vCard (text/vcard)
	*/
	public function get_vcard($vcard_id)
	{
		$vcard_id = str_replace($this->ext, null, $vcard_id);
		return $this->query($this->url . $vcard_id . $this->ext, 'GET');
	}

	/**
	 * Gets a vCard + XML from the CardDAV Server
	 *
	 * @param	string		$vcard_id	vCard id on the CardDAV Server
	 * @param	boolean		$raw		Get response raw or simplified
	 * @return	string					Raw or simplified vCard (text/xml)
	 */
	public function get_xml_vcard($vcard_id, $raw = false)
	{
		$vcard_id = str_replace($this->ext, null, $vcard_id);

		$xml = new XMLWriter();
		$xml->openMemory();
		$xml->setIndent(4);
		$xml->startDocument('1.0', 'utf-8');
			$xml->startElement('C:addressbook-multiget');
				$xml->writeAttribute('xmlns:D', 'DAV:');
				$xml->writeAttribute('xmlns:C', 'urn:ietf:params:xml:ns:carddav');
				$xml->startElement('D:prop');
					$xml->writeElement('D:getetag');
					$xml->writeElement('D:getlastmodified');
				$xml->endElement();
				$xml->writeElement('D:href', $this->url_parts['path'] . $vcard_id . $this->ext);
			$xml->endElement();
		$xml->endDocument();
		$response = $this->query($this->url, 'REPORT', $xml->outputMemory(), 'text/xml');
		if ($raw === true || $response === false)
		{
			return false;
		}
		else
		{
			return $this->simplify($response, true);
		}
	}
	
	/**
	* Gets Collection (returns best estimation, not failsafe)
	*
	* @param string
	* @return array
	*/
	public function get_collection($url)
	{
    $temp = parse_url($url);
    $port = $temp['port'];
    if(!$port){
      $port = '';
    }
    else{
      $port = ':' . $port;
    }
    $host = $temp['scheme'] . '://' . $temp['host'] . $port;
    $this->headers = array('Depth: 1');
    $response = $this->query($url, 'PROPFIND', null, null, false);
    $temp = explode("\r\n\r\n<?xml", $response);
    $xml = '<?xml' . $temp[1];
    $p = xml_parser_create();
    xml_parse_into_struct($p, $xml, $vals, $index);
    xml_parser_free($p);
    $collection = array();
    $i = -1;
    foreach($vals as $elem){
      if($elem['tag'] == 'D:HREF' || $elem['tag'] == 'HREF'){
        $i++;
        if($i > 0 && strlen($elem['value']) > strlen($base)){
          $e = $elem['value'];
          if(stripos($e, '/.out') === false && stripos($e, '/calendar') === false){
            $collection[] = $host . $e;
          }
        }
        else{
          $base = $elem['value'];
        }
      }
    }
    return $collection;
	}

	/**
	* Checks if the CardDAV server is reachable
	*
	* @return	boolean
	*/
	public function check_connection()
	{
		return $this->query($this->url, 'OPTIONS', null, null, true);
	}

	/**
	 * Cleans the vCard
	 *
	 * @param	string	$vcard	vCard
	 * @return	string	$vcard	vCard
	 */
	private function clean_vcard($vcard)
	{
		$vcard = str_replace("\t", null, $vcard);

		return $vcard;
	}

	/**
	 * Deletes an entry from the CardDAV server
	 *
	 * @param	string	$vcard_id	vCard id on the CardDAV server
	 * @return	boolean
	 */
	public function delete($vcard_id)
	{
		return $this->query($this->url . $vcard_id . $this->ext, 'DELETE', null, null, true);
	}

	/**
	 * Adds an entry to the CardDAV server
	 *
	 * @param	string	$vcard	vCard
	 * @return	string			The new vCard id
	 */
	public function add($vcard)
	{
		$vcard_id = $this->generate_vcard_id();
		$vcard = $this->clean_vcard($vcard);
		if(stripos($vcard, "\nUID:") === false){
		  $vcard = str_replace("\nEND:VCARD","\nUID:" . $vcard_id . "\r\nEND:VCARD", $vcard);
		}
		if ($this->query($this->url . $vcard_id . $this->ext, 'PUT', $vcard, 'text/vcard', true) === true)
		{
			return $vcard_id;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Updates an entry to the CardDAV server
	 *
	 * @param	string	$vcard		vCard
	 * @param	string	$vcard_id	vCard id on the CardDAV server
	 * @return	boolean
	 */
	public function update($vcard, $vcard_id)
	{
		$vcard_id = str_replace($this->ext, null, $vcard_id);
		$vcard = $this->clean_vcard($vcard);

		return $this->query($this->url . $vcard_id . $this->ext, 'PUT', $vcard, 'text/vcard', true);
	}

	/**
	 * Simplify CardDAV XML response
	 *
	 * @param	string	$response			CardDAV XML response
	 * @param	boolean	$include_vcards		Include vCards or not
	 * @return	string						Simplified CardDAV XML response
	 */
	private function simplify($response, $include_vcards = true)
	{
		$response = $this->clean_response($response);
		$xml = new SimpleXMLElement($response);

		$simplified_xml = new XMLWriter();
		$simplified_xml->openMemory();
		$simplified_xml->setIndent(4);

		$simplified_xml->startDocument('1.0', 'utf-8');
			$simplified_xml->startElement('response');

				foreach ($xml->response as $response)
				{
					if (preg_match('/vcard/', $response->propstat->prop->getcontenttype) || preg_match('/vcf/', $response->href))
					{
						$id = basename($response->href);
						$id = str_replace($this->ext, null, $id);

						if (!empty($id))
						{
							$simplified_xml->startElement('element');
								$simplified_xml->writeElement('id', $id);
								$simplified_xml->writeElement('etag', str_replace('"', null, $response->propstat->prop->getetag));
								$simplified_xml->writeElement('last_modified', $response->propstat->prop->getlastmodified);

								if ($include_vcards === true)
								{
									$simplified_xml->writeElement('vcard', $this->get_vcard($id));
								}
							$simplified_xml->endElement();
						}
					}
					else if (preg_match('/unix-directory/', $response->propstat->prop->getcontenttype))
					{
						if (isset($response->propstat->prop->href))
						{
							$href = $response->propstat->prop->href;
						}
						else if (isset($response->href))
						{
							$href = $response->href;
						}
						else
						{
							$href = null;
						}

						$url = str_replace($this->url_parts['path'], null, $this->url) . $href;
						$simplified_xml->startElement('addressbook_element');
							$simplified_xml->writeElement('display_name', $response->propstat->prop->displayname);
							$simplified_xml->writeElement('url', $url);
							$simplified_xml->writeElement('last_modified', $response->propstat->prop->getlastmodified);
						$simplified_xml->endElement();
					}
				}

		$simplified_xml->endElement();
		$simplified_xml->endDocument();

		return $simplified_xml->outputMemory();
	}

	/**
	 * Cleans CardDAV XML response
	 *
	 * @param	string	$response	CardDAV XML response
	 * @return	string	$response	Cleaned CardDAV XML response
	 */
	private function clean_response($response)
	{
		$response = utf8_encode($response);
		$response = str_replace('D:', null, $response);
		$response = str_replace('d:', null, $response);
		$response = str_replace('C:', null, $response);
		$response = str_replace('c:', null, $response);

		return $response;
	}
	
	/**
	 * Get specific response header
	 *
	 * @return string
	 */
  private function extractCustomHeader($start, $end, $header)
  {
    $pattern = '/'. $start .'(.*?)'. $end .'/';
    if (preg_match($pattern, $header, $result)) {
      return $result[1];
    }
    else {
      return false;
    }
  }

	/**
	 * Curl initialization
	 *
	 * @return void
	 */
	public function curl_init($header = false)
	{
		if (empty($this->curl))
		{
			$this->curl = curl_init();
			curl_setopt($this->curl, CURLOPT_HEADER, $header);
			curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($this->curl, CURLOPT_USERAGENT, self::USERAGENT.self::VERSION);

			if ($this->auth !== null)
			{
				curl_setopt($this->curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
				curl_setopt($this->curl, CURLOPT_USERPWD, $this->auth);
				curl_setopt($this->curl, CURLOPT_HEADER, true);
			}
		}
		else{
		  curl_setopt($this->curl, CURLOPT_HEADER, $header);
		}
	}

	/**
	 * Queries the CardDAV server via curl and returns the response
	 *
	 * @param	string	$url				CardDAV server URL
	 * @param	string	$method				HTTP-Method like (OPTIONS, GET, HEAD, POST, PUT, DELETE, TRACE, COPY, MOVE)
	 * @param	string	$content			Content for CardDAV queries
	 * @param	string	$content_type		Set content type
	 * @param	boolean	$return_boolean		Return just a boolean
	 * @return	string						CardDAV XML response
	 */
	private function query($url, $method, $content = null, $content_type = null, $return_boolean = false)
	{
    if ( preg_match( '#^(https?)://([a-z0-9.-]+)(:([0-9]+))?(/.*)$#', $url, $matches ) ) {
      $host = $matches[2];
      $file = $matches[5];
       if ( $matches[1] == 'https' ) {
        $protocol = 'ssl';
        $port = 443;
      }
      else {
        $protocol = 'tcp';
        $port = 80;
      }
      if ( $matches[4] != '' ) {
        $port = intval($matches[4]);
      }
    }
		if ($method == 'OPTIONS'){
		  $header = true;
		}
		else if ($this->auth && $method == 'PUT' && class_exists('carddav_plus')){
      $auth = $this->auth_method[$protocol . $host . $port];
      $username = $this->username;
      $password = $this->password;
      $sleep = $this->sleep;
      $current_id = $this->current_id;
      return carddav_plus::carddav_put(
        $protocol,
        $host,
        $port,
        $username,
        $password,
        $auth,
        self::USERAGENT.self::VERSION,
        $method,
        $file,
        $content_type,
        $content,
        $return_boolean,
        $sleep,
        $current_id
      );
		}
		else{
		  $header = false;
		}
		$this->curl_init($header);
		curl_setopt($this->curl, CURLOPT_URL, $url);
		curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $method);
		curl_setopt($this->curl, CURLOPT_TIMEOUT, 30);
		
		if ($content !== null)
		{
			curl_setopt($this->curl, CURLOPT_POST, true);
			curl_setopt($this->curl, CURLOPT_POSTFIELDS, $content);
		}
		else
		{
			curl_setopt($this->curl, CURLOPT_POST, false);
			curl_setopt($this->curl, CURLOPT_POSTFIELDS, null);
		}
		if ($content_type !== null)
		{
			curl_setopt($this->curl, CURLOPT_HTTPHEADER, array('Content-type: '.$content_type, 'Content-Length: ' . strlen($content)));
		}
		else if(is_array($this->headers))
		{
		  curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->headers);
		}
		else
		{
			curl_setopt($this->curl, CURLOPT_HTTPHEADER, array());
		}
    $start = time();
		$return = curl_exec($this->curl);
    if(class_exists('rcmail') && rcmail::get_instance()->config->get('carddav_debug', false)){
      write_log('CardDAV-timeline', "$method $url $content_type $return_boolean");
      write_log('CardDAV-timeline', time() - $start);
      write_log('CardDAV-timeline', $return);
    }
    if($header){
      $header = explode("\r\n\r\n", $return);
      $authline = $this->extractCustomHeader('WWW-Authenticate: ', '\n', $header[0]);
     if($authline && strtolower(substr($authline, 0, strlen('Digest'))) == 'digest'){
        $this->auth_method[$protocol . $host . $port] = 'Digest';
      }
      else{
        $this->auth_method[$protocol . $host . $port] = 'Basic';
      }
    }
    
		$http_code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);

		if (in_array($http_code, array(200, 207)))
		{
			return ($return_boolean === true ? true : $return);
		}
		else if ($return_boolean === true && in_array($http_code, array(201, 204, 503)))
		{
			return true;
		}
		else if (in_array($http_code, array(401)))
		{
			return false;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Returns a valid and unused vCard id
	 *
	 * @return	string	Valid vCard id
	 */
	private function generate_vcard_id()
	{
		$id = null;

		for ($number = 0; $number <= 25; $number ++)
		{
			if ($number == 8 || $number == 17)
			{
				$id .= '-';
			}
			else
			{
				$id .= $this->vcard_id_chars[mt_rand(0, (count($this->vcard_id_chars) - 1))];
			}
		}
		
		$this->current_id = $id;
		
		if(!$this->failsafe_id){
		  return $id;
		}
		$carddav = new carddav_backend($this->url);
		$carddav->set_auth($this->username, $this->password);

		if ($carddav->query($this->url . $id . $this->ext, 'GET', null, null, true))
		{
			return $this->generate_vcard_id();
		}
		else
		{
			return $id;
		}
	}

	/**
	 * Destructor
	 * Close curl connection if it's open
	 *
	 * @return	void
	 */
	public function __destruct()
	{
		if (!empty($this->curl))
		{
			curl_close($this->curl);
		}
	}
}
