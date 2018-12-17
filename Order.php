<?php

class Order
{
   private $googleSheets;
   private $orderDesk;
   private $twilio;

   public function __construct( GoogleSheetsClient $googleSheets, OrderDeskApiClient $orderDesk, Twilio\Rest\Client $twilio )
   {
       $this->googleSheets = $googleSheets;
       $this->orderDesk    = $orderDesk;
       $this->twilio       = $twilio;
   }

   /**
    * Gets orders from OrderDesk
    *
    * @return void
    */
   public function getOrders()
   {
       $response = $this->orderDesk->get( "orders" );
       $orders   = $response[ "orders" ];
       $values   = [];

       if (!empty($orders)) {
            foreach ( $orders as $order ) {

                $value = [
                    $order["shipping"]["first_name"],
                    $order["shipping"]["last_name"],
                    $order["email"],
                    $order["shipping_method"],
                    $order["payment_type"],
                    $order["order_total"]
                ];
    
                array_push( $values, $value );
            }
    
            $this->updateGoogleSheets( $values );
        } else {
            return $response['message'];
        }
   }

   /**
    * Updates Google Sheets
    *
    * @param array $values - values of the fields that need
    * to be updated on Sheets
    *
    * @return void
    */
   public function updateGoogleSheets( $values )
   {
    
       $spreadsheetId = getenv( "SPREADSHEET_ID" );
       $range         = 'Sheet1!A2:F2';

       $response = $this->googleSheets->updateSheet( $values, $range,$spreadsheetId );

       if ( $response->updatedRows ) {
           $this->sendSms( $response->updatedRows );
           return "Congratulations, $response->updatedRows row(s) updated on Google Sheets";
       }
   }

   /**
    * Sends an SMS about the update on Sheets
    *
    * @param integer $numberOfRows - number of rows
    *
    * @return void
    */
   public function sendSms( $numberOfRows )
   {
       $myTwilioNumber = getenv( "TWILIO_NUMBER" );

       $this->twilio->messages->create(
           // Where to send a text message
           "INSERT VERIFIED NUMBER",
           array(
              "from" => $myTwilioNumber,
              "body" => "Hey! $numberOfRows row(s) updated on Google Sheets!"
          )
       );
   }
}
