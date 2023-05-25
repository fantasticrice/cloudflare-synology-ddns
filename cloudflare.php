#!/usr/bin/php -d open_basedir=/usr/syno/bin/ddns
<?php

static API_BASE_URI = 'https://api.cloudflare.com/client/v4';
static RECORD_TYPE = 'A';

class Record
{
	public $content;
	public $name;
	public $type = RECORD_TYPE;

	public function __construct($hostname, $ip)
	{
		$this->content = $ip;
		$this->name = $hostname;
	}
}

class DnsRecordsApi
{
	private $endpoint;
	private $headers = ['Content-Type: application/json'];

	public function __construct($zone, $token)
	{
		$this->endpoint = sprintf(API_BASE_URI . '/zones/%s/dns_records', $zone);
		$this->headers[] = 'Authorization: Bearer ' . $token;
	}

	private function request($method, $params)
	{
		$ch = curl_init();
		if ($method === 'GET') {
			$url = $this->endpoint . '?' . http_build_query($params));
		} else {
			$url = $this->endpoint;
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
		}
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$response = curl_exec($ch);
		return json_decode($response);
	}	

	public function get($hostname)
	{
		$params = ['type' => RECORD_TYPE, 'name' => $hostname]
		return $this->request('GET', $params);
	}

	public function put($record)
	{
		return $this->request('PUT', $record);
	}

	public function post($record)
	{
		return $this->request('POST', $record);
	}
}	


//
// Validate Input
//

if ($argc !== 5) {
	exit('badparam');
}

list($cmd, $zone, $token, $hostname, $ip) = $argv;

if (strpos($hostname, '.') === false) {
	exit('badparam');
}

if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
	exit('badparam');
}

//
// Main
//

$api = new DnsRecordsApi($zone, $token);
$records = $api->get($hostname);

if (!empty($records->errors)) {
	exit('badauth');
}

if (!empty($records->result)) {
	$record = $records->result[0];
	if ($record->content === $ip) {
		exit('nochg');
	}
	$record->content = $ip;
	$result = $api->post($record);
} else {
	$record = new Record($hostname, $ip);
	$result = $api->put($record);
}

exit($result->success? 'good' : 'badagent');
