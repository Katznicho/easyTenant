<?php

namespace App\Http\Controllers;

use App\Traits\MessageTrait;
use App\Traits\ResponseTrait;
use App\Traits\SessionTrait;
use App\Traits\UserTrait;
use Illuminate\Http\Request;

class UssdController extends Controller
{
    use MessageTrait, UserTrait, ResponseTrait, SessionTrait;

    public function process(Request $request)
    {
        try {

            $user = $this->checkIfUserExists($request->phoneNumber);
            if (!$user) {
                $this->getLastUserSession($request, "00");
                return $this->writeResponse("Your account does not exits", true);
            } else {
                if ($request->text == "") {
                    $details =  $this->getUserRentalDetails($request->phoneNumber);
                    return $this->welcomeUser($request, $details->name);
                } else {
                    $last_response =  $this->getLastUserSession($request->phoneNumber);
                    switch ($last_response->last_user_code) {
                        case '00':
                            if ($request->text == "1") {
                                return $this->selectMode($request);
                            } elseif ($request->text == "2") {
                                $this->storeUserSession($request, "Announcements");
                                $this->sendMessage($request->phoneNumber, "Announcements", "Rent increament starts on 01/01/2024", "Bank Payments are now allowed", "Renovations to commence next year ");
                                return $this->writeResponse("Latest Announcements have been sent to your phone number and email", true);
                            } elseif ($request->text == "3") {
                                return $this->myAccount($request);
                            } else if ($request->text == "4") {
                                $this->storeUserSession($request, "Old Pin");
                                return $this->writeResponse("Enter your pin", false);
                            } elseif ($request->text == "5") {
                                //make a call
                                $curl = curl_init();

                                curl_setopt_array($curl, array(
                                    CURLOPT_URL => 'https://voice.africastalking.com/call',
                                    CURLOPT_RETURNTRANSFER => true,
                                    CURLOPT_ENCODING => '',
                                    CURLOPT_MAXREDIRS => 10,
                                    CURLOPT_TIMEOUT => 0,
                                    CURLOPT_FOLLOWLOCATION => true,
                                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                    CURLOPT_CUSTOMREQUEST => 'POST',
                                    CURLOPT_POSTFIELDS => 'username=katznicho&to=%2B256763425870&from=%2B256200600614',
                                    CURLOPT_HTTPHEADER => array(
                                        'Accept: application/json',
                                        'Content-Type: application/x-www-form-urlencoded',
                                        'apiKey: 3b452fdc67868a7692aa9ecc947b082d385426f982cbde4cbd03044a16886348'
                                    ),
                                ));

                                $response = curl_exec($curl);
                                return $this->writeResponse("You will receive a phone call shortly", true);
                            } else {
                                return $this->writeResponse("We did not understand your request 00", true);
                            }
                            break;
                        case "Account":
                            if ($request->text == "3*1") {
                                $this->storeUserSession($request, "Balance");
                                return $this->writeResponse("Enter your pin", false);
                            } elseif ($request->text == "3*2") {
                                $this->storeUserSession($request, "Deposit");
                                return $this->writeResponse("Enter your amount", false);
                            } elseif ($request->text == "3*3") {
                                $this->storeUserSession($request, "Account Information");
                                return $this->writeResponse("Enter your pin", false);
                            } elseif ($request->text == "3*4") {
                                return $this->paymentOptions($request);
                            } else {
                                return $this->writeResponse("We did not understand your request", true);
                            }
                            break;

                        case "Deposit":
                            $amount = explode("*", $request->text);
                            $amount = $amount[2];
                            $this->deposit($request->phoneNumber, $amount);
                            $userDetails =  $this->getUserDetails($request->phoneNumber);
                            $message = "Hello $userDetails->name, you have deposited UGX $amount on your account";
                            $this->sendMessage($request->phoneNumber, $message);
                            return $this->writeResponse("You have deposited UGX $amount on your account", true);
                            break;
                        case "Account Information":
                            $userDetails =  $this->getUserDetails($request->phoneNumber);
                            $rentalDetails =  $this->getUserRentalDetails($request->phoneNumber);
                            //generate message including account balance , rental name and phone number
                            $message = "Hello $userDetails->name, your account balance is UGX $userDetails->account. Your rental name is $rentalDetails->name and phone number is $request->phoneNumber";
                            // $this->sendMessage($request->phoneNumber, $message);
                            return $this->writeResponse("Your account balance is UGX $userDetails->account. Your rental name is $rentalDetails->name and phone number is $request->phoneNumber", true);
                            break;
                            // $message = "Hello $userDetails->name, your account balance is UGX $userDetails->account";
                            // $this->sendMessage($request->phoneNumber, $message);
                            return $this->writeResponse("Your account balance is UGX $userDetails->account", true);
                            break;
                        case "Payment":
                            if ($request->text == "3*4*1") {
                                $this->storeUserSession($request, "PaymentPin");
                                return $this->writeResponse("Enter your pin", false);
                            } elseif ($request->text == "3*4*2") {
                                $this->storeUserSession($request, "Push Payment");
                                return $this->writeResponse("A push notification has been sent to complete the payment", true);
                            } else {
                                return $this->writeResponse("We did not understand your request", true);
                            }
                            break;
                        case "PaymentPin":
                            //check pin
                            $pin = $request->text;
                            $actualPin =  explode("*", $pin)[3];
                            $bol =  $this->checkPin($actualPin, $request->phoneNumber);
                            if ($bol) {
                                return $this->writeResponse("Your payment has been completed", true);
                            } else {
                                return $this->writeResponse("You entered an invalid pin", true);
                            }


                        case "Balance":
                            $pin =  explode("*", $request->text);
                            $pin = $pin[2];
                            $pinRes = $this->checkPin($pin, $request->phoneNumber);
                            if ($pinRes) {
                                $bal = $this->getAccountBalance($request->phoneNumber);
                                return $this->writeResponse("Your account balance is $bal", true);
                            } else {
                                return $this->writeResponse("You entered an invalid pin", true);
                            }
                        case 'value':
                            # code...
                            break;
                        case "Old Pin":
                            //extract  pin
                            $pin = $request->text;
                            $actualPin =  explode("*", $request->text);
                            $pin = $actualPin[1];
                            $checkPin = $this->checkPin($pin, $request->phoneNumber);
                            if ($checkPin) {
                                $this->storeUserSession($request, "Reset Pin");
                                return $this->writeResponse("Enter new pin", false);
                            } else {
                                return $this->writeResponse("You entered an invalid pin", true);
                            }
                            break;
                        case "Reset Pin":
                            $pin = $request->text;
                            $actualPin =  explode("*", $request->text);
                            $pin = $actualPin[2];
                            $checkPin = $this->updatePin($pin, $request->phoneNumber);
                            if ($checkPin) {
                                return $this->writeResponse("Pin reset successfully", true);
                            } else {
                                return $this->writeResponse("You entered an invalid pin", true);
                            }
                            break;
                        case "Mode":
                            if ($request->text == "1*1") {
                                $this->storeUserSession($request, "Screen");
                                return $this->writeResponse("You dont have any transactions yet", true);
                            } elseif ($request->text == "1*2") {
                                $this->storeUserSession($request, "Message");
                                $details = $this->getUserDetails($request->phoneNumber);
                                $message = " Hello $details->name You dont any  transactions yet";
                                $this->sendMessage($request->phoneNumber, $message);
                                return $this->writeResponse("Please check your messages", true);
                            } else {
                                return $this->writeResponse("We did not understand your request", true);
                            }
                            break;

                        default:
                            # code...
                            break;
                    }
                }
            }
        } catch (\Throwable $th) {
            //throw $th;
            return $this->writeResponse($th->getMessage(), true);
        }
    }

    private function welcomeUser(Request $request, string $name)
    {
        $response  = "Welcome to $name:\n";
        $response .= "1. Transactions\n";
        $response .= "2. Announcements\n";
        $response .= "3. My Account\n";
        $response .= "4. Reset Pin\n";
        $response .= "5. Help\n";

        //store user session
        $this->storeUserSession($request, "00");

        return $this->writeResponse($response, false);
    }

    private  function myAccount(Request $request)
    {
        $response = "My Account\n";
        $response .= "1. My Balance\n";
        $response .= "2. Deposit\n";
        $response .= "3. Account Information\n";
        $response .= "4. Make Payment\n";
        //store user session
        $this->storeUserSession($request, "Account");
        return $this->writeResponse($response, false);
    }

    private function enterPinBalance(Request $request)
    {
        //store user session
        $this->storeUserSession($request, "Balance");
        return $this->writeResponse("Enter Pin ", false);
    }

    private function selectMode(Request $request)
    {
        //mode can be message or on screen
        $response = "Select Mode\n";
        $response .= "1. On Screen\n";
        $response .= "2. Message";
        $this->storeUserSession($request, "Mode");
        return $this->writeResponse($response, false);
    }

    private function paymentOptions(Request $request)
    {
        $response = "Payment Options\n";
        $response .= "1. My Account\n";
        $response .= "2. Mobile Money\n";
        $this->storeUserSession($request, "Payment");
        return $this->writeResponse($response, false);
    }

    public function handleCall()
    {
        // Forward by dialing customer service numbers and record the conversation
        // Compose the response
        $response  = '<?xml version="1.0" encoding="UTF-8"?>';
        $response .= '<Response>';
        $response .= '<Dial record="true" sequential="true" phoneNumbers="+256759983853" />';
        $response .= '</Response>';
        return $response;
        // Return the response
    }
}
