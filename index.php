<?php
require __DIR__ . "/vendor/autoload.php";

require "GoogleSheetsClient.php";
require "OrderDeskClient.php";
require "Order.php";

use Twilio\Rest\Client as TwilioClient;

$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();


$apiKey    = getenv( "ORDER_DESK_API_KEY" );
$storeId   = getenv( "ORDER_DESK_STORE_ID" );
$orderDesk = new OrderDeskApiClient( $storeId, $apiKey );

$twilioAccountSid = getenv( "TWILIO_SID" );
$twilioAuthToken  = getenv( "TWILIO_TOKEN" );
$twilio           = new TwilioClient( $twilioAccountSid, $twilioAuthToken );

$googleSheets = new GoogleSheetsClient();

$order = new Order( $googleSheets, $orderDesk, $twilio );

$result = $order->getOrders();

echo $result;
