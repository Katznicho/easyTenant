<?php

namespace App\Traits;

use App\Models\UssdSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

trait SessionTrait
{
    public function storeUserSession(Request $request,  string $lastUserCode)
    {
        //store user session
        DB::table('ussd_sessions')->insert([
            'phone_number' => $request->phoneNumber,
            'session_id' => $request->sessionId,
            'text' => $request->text,
            'network_code' => $request->networkCode,
            'service_code' => $request->serviceCode,
            'last_user_code' => $lastUserCode
        ]);
        return true;
    }

    //get session details
    public function getLastUserSession(string $phoneNumber)
    {
        $lastUserSession = DB::table('ussd_sessions')
            ->where('phone_number', $phoneNumber)
            ->orderBy('id', 'desc')
            ->first();

        return $lastUserSession;
    }
}
