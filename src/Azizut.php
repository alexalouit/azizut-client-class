<?php
/**
 * Azizut class
 * @author: Alexandre Alouit <alexandre.alouit@gmail.com>
 */

namespace Azizut;

class Azizut {

	/**
	 * Timeout for complete connection in seconds
	 * @type: int
	 */
	private $timeout = 6;

	/**
	 * Timeout for first byte connection in seconds
	 * @type: int
	 */
	private $connectionTimeout = 4;

	/**
	 * Delay when we have many links in micro secondes
	 * @type: int
	 */
	private $delay = 100000;

	private $server = NULL;
	private $username = NULL;
	private $password = NULL;
	public $content = "";
	public $url = "";
	public $shorturl = "";
	public $link = "";
	private $query = "";
	private $action = "";
	private $params = "";
	public $response = "";
	public $valid = FALSE;
	public $stats = FALSE;
	public $start = NULL;
	public $limit = NULL;


	/**
	 * @params: server (string), username (string), password (string)
	 */
	public function __construct($server, $username, $password) {
		if(!function_exists('json_decode')) {
			die("PECL json required.");
		}

		$this->params = new \stdClass;
		$this->query = new \stdClass;
		$this->query->access = new \stdClass;
		$this->server = $server;
		$this->username = $username;
		$this->password = $password;
	}

	/**
	 * Make a call
	 * @return: response(object)
	 */
	private function talker() {
		if(!empty($this->params)) {
			$this->query->access->username = $this->username;
			$this->query->access->password = $this->password;
			$this->query->action = $this->action;
			$this->query->params = $this->params;
			$query = json_encode($this->query);

			if(!function_exists('curl_init')) {
				// using php
				$opts = array('http' =>
					array(
						'method'  => 'POST',
						'header'  => 'Content-type: application/json\r\nContent-Length: ' . strlen($query) . '\r\n',
						'content' => $query,
						'timeout' => $this->timeout
					)
				);

				$context  = stream_context_create($opts);
				$raw_response = file_get_contents($this->server, false, $context);

			} else {
				// using curl
				$buffer = curl_init();
				curl_setopt($buffer, CURLOPT_URL, $this->server);
				curl_setopt($buffer, CURLOPT_CONNECTTIMEOUT, $this->connectionTimeout);
				curl_setopt($buffer, CURLOPT_TIMEOUT, $this->timeout);
				curl_setopt($buffer, CURLOPT_HEADER, 0);
				curl_setopt($buffer, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($buffer, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Content-Length: " . strlen($query)));
				curl_setopt($buffer, CURLOPT_POST, 1);
				curl_setopt($buffer, CURLOPT_POSTFIELDS, $query);

				$data = curl_exec($buffer);
				curl_close($buffer);

			}

			$this->response = json_decode($data);

			$this->verify();
			return $this->response;
		}
	}

	/**
	 * Check return request is valid and job is done
	 * @return: (bool)
	 */
	private function verify() {
		if(!empty($this->response->statusCode)
			&& ($this->response->statusCode == 200
			OR $this->response->statusCode == 202)) {
			$this->valid = TRUE;

			return TRUE;
		} else {
			$this->valid = FALSE;

			return FALSE;
		}
	}

	/**
	 * Search link in content
	 * @params: content (string)
	 * @return: founds (array)
	 */
	private function search($data) {
		preg_match_all("_(^|[\s.:;?\-\]<\(])(https?://[-\w;/?:@&=+$\|\_.!~*\|'()\[\]%#,☺]+[\w/#](\(\))?)(?=$|[\s',\|\(\).:;?\-\[\]>\)])_i", $data, $return);
		return array_map('trim', $return[0]);
	}

	/**
	 * Replace link in content
	 * @params: content (string), toReplace (array), byReplace (array)
	 * @return: content (sring)
	 */
	private function replace($content, $toReplace, $byReplace) {
		return str_replace($toReplace, $byReplace, $content);
	}

	/**
	 * Shorten for content (with multiple links)
	 * Replace all links in current content
	 * @params: text with url (string)
	 * @return: text with shortening url (string)
	 */
	public function content($data=NULL) {
		if(empty($data)) {
			$data = $this->content;
		}

		foreach($this->search($data) as $key => $toReplace) {
			$this->insert($toReplace);
			$data = $this->replace($data, $toReplace, $this->link);

			if(!is_null($this->delay)) {
				usleep($this->delay);
			}
		}

		$this->content = $data;
		return $this->content;
	}
	/**
	 * Shortener by link
	 * @return: url shortening (string)
	 */
	public function insert($url=NULL) {
		if(!empty($url)) {
			$this->url = $url;
		}

		// prevent bad return
		$this->shorturl = $this->url;
		$this->link = $this->url;

		$this->action = "insert";
		$this->params->url = $this->url;
		$this->talker();

		if($this->valid && !empty($this->response->data->shorturl)) {
			$this->shorturl = $this->response->data->shorturl;
			$this->link = $this->response->data->link;
		}

		return $this->shorturl;
	}

	/**
	 * Update a link
	 * @return:
	 */
	public function update() {
// TODO
	}

	/**
	 * delete a link
	 * @return: (bool)
	 */
	public function delete($shorturl=NULL, $url=NULL) {
		if(!empty($shorturl)) {
			$this->shorturl = $shorturl;
		}
		if(!empty($url)) {
			$this->url = $url;
		}

		$this->action = "delete";
		$this->params->url = $this->url;
		$this->params->shorturl = $this->shorturl;
		$this->talker();
		if($this->valid) {

			return TRUE;
		} else {

			return FALSE;
		}
	}

	/**
	 * Get link/links list with stats or not, with pagination or not
	 * @return: response (string)
	 */
	public function get($shorturl=NULL, $url=NULL, $stats=FALSE, $start=NULL, $limit=NULL) {
		if(!empty($shorturl)) {
			$this->shorturl = $shorturl;
		}
		if(!empty($url) && $url) {
			$this->url = $url;
		}
		if(!empty($stats) && $stats) {
			$this->params->stats = TRUE;
		}
		if(!empty($start) && is_int($start)) {
			$this->params->start = $start;
		}
		if(!empty($limit) && is_int($limit)) {
			$this->params->limit = $limit;
		}

		$this->action = "get";
		// send all data, server do sorting
		$this->params->url = $this->url;
		$this->params->shorturl = $this->shorturl;
		$this->params->stats = $this->stats;
		$this->params->start = $this->start;
		$this->params->limit = $this->limit;
		$this->talker();

		if($this->valid) {
			return $this->response->data;
		}

	}



}
?>
