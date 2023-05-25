#!/usr/bin/php -d open_basedir=/usr/syno/bin/ddns
<?php

const API_RECORD_TYPE = 'A';
const API_BASE_URL = 'https://api.cloudflare.com/client/v4';

class CloudflareClient
{
	private $hostname;
	private $zone;
	private $token;

	public function __construct($hostname, $zone, $token)
	{
		$this->hostname = $hostname;
		$this->zone = $zone;
		$this->token = $token;
	}	

	private function request($method = 'GET', $params = null)
	{
		$ch = curl_init();
		$url = sprintf(API_BASE_URL . '/zones/%s/dns_records', $this->zone);
		$headers = ['Content-Type: application/json', 'Authorization: Bearer ' . $this->token];

		if ($method === 'GET') {
			$params = ['type' => API_RECORD_TYPE, 'name' => $this->hostname];
			$url .= '?' . http_build_query($params);
		} else {
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
		}
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$response = curl_exec($ch);
		return json_decode($response);
	}	

	public function getRecords()
	{
		return $this->request();
	}

	public function createRecord($ip)
	{
		$record = ['content' => $ip, 'name' => $this->hostname, 'type' => API_RECORD_TYPE];
		return $this->request('PUT', $record);
	}

	public function updateRecord($record)
	{
		return $this->request('POST', $record);
	}
}	

if ($argc !== 5) {
	echo 'badparam';
	exit();
}

list($cmd, $zone, $token, $hostname, $ip) = $argv;

if (strpos($hostname, '.') === false) {
	echo 'badparam';
	exit();
}

if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
	echo 'badparam';
	exit();
}

$cf = new CloudflareClient($hostname, $zone, $token);
$records = $cf->getRecords();
var_dump($records);
exit();

if (!empty($records->errors)) {
	echo 'badauth';
	exit();
}

if (!empty($records->result)) {
	$record = $records->result[0];
	if ($record->content === $ip) {
		echo 'nochg';
		exit();
	}
	$record->content = $ip;
	$result = $cf->updateRecord($record);
} else {
	$result = $cf->createRecord($ip);
}

echo $result->success? 'good' : 'badagent';