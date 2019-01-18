<?php


namespace App\Http\Helpers;


use App\smsLog;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Exception;
use Illuminate\Support\Facades\Log;

class SmsHelper
{
    private $gateway = null;

    function __construct($gateway)
    {
        $this->gateway = $gateway;
    }

    public function sendSms($number, $message) {

        try {

            if(!$this->gateway){
                throw new Exception('SMS gateway not defined!');
            }

            // list is here AppHelper::SMS_GATEWAY_LIST
            if($this->gateway->gateway == 1){
                return $this->sendSmsViaBulkSmsRoute($number, $message);
            }
            elseif ($this->gateway->gateway == 2) {
                return $this->sendSmsViaItSolutionbd($number, $message);
            }
            elseif ($this->gateway->gateway == 3) {
                return $this->sendSmsViaZamanIt($number, $message);
            }
            elseif ($this->gateway->gateway == 4) {
                return $this->sendSmsViaMimSms($number, $message);
            }
            elseif ($this->gateway->gateway == 5) {
                return $this->sendSmsViaTwilio($number, $message);
            }
            else {
                // log sms to file
                Log::channel('smsLog')->info("Send new sms to ".$number." and message is:\"".$message."\"");
                return true;
            }

        }
        catch (Exception $e) {
            //write error log
            Log::channel('smsLog')->error($e->getMessage());
        }


        return true;

    }

    private function sendSmsViaBulkSmsRoute($number, $message) {
        try {

            $client = new Client();
            $uri = $this->gateway->api_url."?api_key=".$this->gateway->user."&type=text&contacts=".$number."&senderid=".$this->gateway->sender_id."&msg=".urlencode($message);
            $response = $client->get($uri);
            $status = json_decode($response->getBody());

            $isSuccess = false;
            switch ($status) {
                case "1002":
                    $msg = "Sender Id/Masking Not Found";
                    break;
                case "1003":
                    $msg = "API Not Found";
                    break;
                case "1004":
                    $msg = "SPAM Detected";
                    break;
                case "1005":
                    $msg = "Internal Error";
                    break;
                case "1006":
                    $msg = "Internal Error";
                    break;
                case "1007":
                    $msg = "Balance Insufficient";
                    break;
                case "1008":
                    $msg = "Message is empty";
                    break;
                case "1009":
                    $msg = "Message Type Not Set";
                    break;
                case "1010":
                    $msg = "Invalid User & Password";
                    break;
                case "1011":
                    $msg = "Invalid User Id";
                    break;
                default:
                    $msg = 'SMS SEND';
                    $isSuccess = true;
                    break;
            }

            if($isSuccess) {

                $log = $this->logSmsToDB($number, $message, $msg);
            }
            else{
                Log::channel('smsLog')->warning($msg.". url=".$uri);
            }

            return true;

        } catch (RequestException $e) {
            throw new Exception($e->getMessage());
        }


    }


    private function sendSmsViaItSolutionbd($number, $message) {

        return true;
    }

    private function sendSmsViaZamanIt($number, $message) {

        return true;
    }

    private function sendSmsViaMimSms($number, $message) {

        return true;
    }

    private function sendSmsViaTwilio($number, $message) {

        return true;
    }

    private function logSmsToDB($to, $message, $status){

        return smsLog::create([
            'sender_id' => $this->gateway->sender_id,
            'to' => $to,
            'message' => $message,
            'status' => $status,
        ]);

    }

}