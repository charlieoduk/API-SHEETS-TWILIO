<?php

class OrderDeskApiClient
{
	private $store_id;
	private $api_key;
	private $base_url = "https://app.orderdesk.me/api/v2";
	public $last_status_code = "";
	public function __construct($store_id, $api_key) {
		$this->store_id = $store_id;
		$this->api_key = $api_key;
	}
	public function get($url = "", $post = null) {
		return $this->go("GET", $url, $post);
	}
	public function post($url, $post = null) {
		return $this->go("POST", $url, $post);
	}
	public function put($url, $post = null) {
		return $this->go("PUT", $url, $post);
	}
	public function delete($url, $post = null) {
		return $this->go("DELETE", $url, $post);
	}
	public function go($method, $url, $post) {
		if (!is_array($post)) {
			$post = null;
		}
		if (!$url) {
			throw new \Exception("Please enter a destination url");
		}
		$url =  $this->base_url . "/" . $url;
		$headers = $this->getHeaders();
		//GET Override
		if ($method == "GET" && $post !== null) {
			$url .= (strpos($url, "?") === false ? "?" : "") . http_build_query($post);
			$post = "";
		}
		//Setup cURL
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		if ($post) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_USERAGENT,  "orderdesk/orderdesk_client");
		//Send To Order Desk and Parse Response
		$response = trim(curl_exec($ch));
		$info = curl_getinfo($ch);
		$json = json_decode($response, 1);
		if (!is_array($json)) {
			return $response;
		}
		$this->last_status_code = $info['http_code'];
		return $json;
	}
	//Get auth headers for this call
	public function getHeaders() {
		return array(
			"ORDERDESK-STORE-ID: {$this->store_id}",
			"ORDERDESK-API-KEY: {$this->api_key}",
			"Content-Type: application/json",
		);
	}
	//Check Post JSON
	public function validatePostedJson() {
		if (!isset($_POST['order'])) {
			header(':', true, 400);
			die('No Data Found');
		}
		//Check Store ID
		if (!isset($_SERVER['HTTP_X_ORDER_DESK_STORE_ID']) || $_SERVER['HTTP_X_ORDER_DESK_STORE_ID'] != $this->store_id) {
			header(':', true, 403);
			die('Unauthorized Request');
		}
		//Check the Hash
		if (!isset($_SERVER['HTTP_X_ORDER_DESK_HASH']) || hash_hmac('sha256', rawurldecode($_POST['order']), $this->api_key) != $_SERVER['HTTP_X_ORDER_DESK_HASH']) {
			header(':', true, 403);
			die('Unauthorized Request');
		}
		//Check Order Data
		$order = json_decode($_POST['order'], 1);
		if (!is_array($order)) {
			header(':', true, 400);
			die('Invalid Order Data');
		}
	}
}
