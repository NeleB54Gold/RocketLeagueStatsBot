<?php

class RocketLeagueTracker {
	# Unofficial API Endpoint [https://rocketleague.tracker.network/]
	public $endpoint = 'https://api.tracker.gg/api/v2/rocket-league/standard';
	# API Token
	private $token = '';
	# Cache time
	public $cache_time = 60 * 60 * 2;
	# Request timeout
	public $r_timeout = 5;
	# Database class
	private $db = [];
	# Supported platforms
	private $platforms = ['epic', 'steam', 'xbl', 'psn', 'switch'];
	
	# Set configs
	public function __construct ($db = []) {
		if (is_a($db, 'Database') && $db->configs['redis']['status']) $this->db = $db;
	}
	
	# Get player stats from every platform
	public function getPlayer (string $tag, string $platform = null) {
		if (!in_array($platform, $this->platforms)) $platform = 'epic';
		return $this->request('profile/' . strtolower($platform) . '/' . $tag);
	}
	
	# Custom API requests
	public function request (string $src = '') {
		if (!isset($this->curl))	$this->curl = curl_init();
		$url = $this->endpoint . '/' . $src;
		if (is_a($db, 'Database') && $this->db->configs['redis']['status']) {
			$cache = $this->db->rget($url);
			if ($r = json_decode($cache, true)) return $r;
		}
		curl_setopt_array($this->curl, [
			CURLOPT_URL				=> $url,
			CURLOPT_HEADER			=> true,
			CURLOPT_HTTPHEADER		=> [
				'Accept: application/json',
				'TRN-Api-Key: ' . $this->token
			],
			CURLOPT_TIMEOUT			=> $this->r_timeout,
			CURLOPT_RETURNTRANSFER	=> true
		]);
		$output = curl_exec($this->curl);
		if ($json_output = json_decode($output, 1)) {
			if (is_a($this->db, 'Database') && $this->db->configs['redis']['status']) {
				$r = $this->db->rset($url, json_encode($json_output), $this->cache_time);
			}
			return $json_output;
		}
		if ($output) return $output;
		if ($error = curl_error($this->curl)) return ['ok' => false, 'error_code' => 500, 'description' => 'CURL Error: ' . $error, 'url' => $url];
	}
}

?>