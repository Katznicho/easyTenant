<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

trait UserTrait
{
    public function checkPin(string $pin, string $phoneNumber)
    {
        //check user pin
        $getUser = DB::table('users')->where('phone_number', $phoneNumber)->first();
        $hashedPin = $getUser->pin;
        $pin = str_replace(" ", "", $pin);
        if (Hash::check($pin, $hashedPin)) {
            return true;
        } else {
            return false;
        }
    }

    public function updatePin(string $pin, string $phoneNumber)
    {
        //remove any spaces
        $pin = str_replace(" ", "", $pin);
        //print_r($pin);
        //update user pin
        $hashedPin = Hash::make($pin);
        //print_r($hashedPin);
        DB::table('users')->where('phone_number', $phoneNumber)->update(['pin' => $hashedPin]);
        return true;
    }


    public function getAccountBalance(string $phoneNumber)
    {
        //get user account balance
        $getUser = DB::table('users')->where('phone_number', $phoneNumber)->first();
        return  "UGX " . "" . $getUser->account;
    }

    public function checkIfUserExists(string $phoneNumber)
    {
        //check if user exists
        $getUser = DB::table('users')->where('phone_number', $phoneNumber)->first();
        if ($getUser) {
            return true;
        } else {
            return false;
        }
    }

    //get user details
    public function getUserDetails(string $phoneNumber)
    {
        return DB::table('users')->where('phone_number', $phoneNumber)->first();
    }

    //get user community details
    public function getUserRentalDetails(string $phoneNumber)
    {
        $getUser = DB::table('users')->where('phone_number', $phoneNumber)->first();
        return DB::table('rentals')->where('user_id', $getUser->id)->first();
    }


    public function deposit(string $phoneNumber, string $amount)
    {
        $rentalDetails = $this->getUserRentalDetails($phoneNumber);
        //deposit
        $getUser = DB::table('users')->where('phone_number', $phoneNumber)->first();
        //create a  transaction
        DB::table('transactions')->insert([
            'phone_number' => $phoneNumber,
            'amount' => $amount,
            'type' => 'credit',
            'status' => 'completed',
            'description' => 'Deposit',
            'rental_id' => $rentalDetails->id,
            'user_id' => $getUser->id,
            'reference' => Str::uuid()
        ]);
        //update the community account balance
        DB::table('rentals')->where('user_id', $getUser->id)->update(['account' => intval($rentalDetails->account) + intval($amount)]);
        //update user account balance
        DB::table('users')->where('phone_number', $phoneNumber)->update(['account' => intval($getUser->account) + intval($amount)]);
        return true;
    }

    //with draw
    public function withdraw(string $phoneNumber, string $amount)
    {
        //withdraw
        $getUser = DB::table('users')->where('phone_number', $phoneNumber)->first();
        //create a  transaction
        DB::table('transactions')->insert([
            'phone_number' => $phoneNumber,
            'amount' => $amount,
            'type' => 'debit',
            'status' => 'completed',
            'description' => 'Withdrawal',
            'community_id' => $getUser->community_id,
            'user_id' => $getUser->id,
            'reference' => Str::uuid()
        ]);
        //update the community account balance
        DB::table('communities')->where('id', $getUser->community_id)->update(['account_balance' => $getUser->account_balance - $amount]);
        //update user account balance
        DB::table('users')->where('phone_number', $phoneNumber)->update(['account_balance' => $getUser->account_balance - $amount]);
        return true;
    }
}
