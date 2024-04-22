<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Twilio\Rest\Client;
use App\Models\User;
use App\VerificationCode;

class RegisterController extends Controller
{

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => 0,
                'data' => [],
                'message' => $validator->errors()->first()
            ]);
        }
		
		if(isset($request->name)){
			$user = User::where('phone', "LIKE","%".$request->phone."%")->first();
			if($user){
			  return response()->json([
                'success' => 0,
                'data' => [],
                'message' => "Phone Already Exists!"
            ]);
			}
			$user = new User();
			$user->name = $request->name;
			$user->phone = $request->phone;
			//$user->token = $user->createToken('personal access token');

			$user->save();
			
			
			$message = "Hello from Twilio WhatsApp API in Laravel! 🚀";

			

			try {
			$twilio = new Client("ACee3f000a088bc43718654e8fd99d08aa", "c7023effb37c702e259ffb07a6b096b6");
			$code = rand(0000,9999);
			//$code = 1111;
			
				
			$twilio->messages->create("whatsapp:+964".$request->phone, // $receiverphone
						 [
							 "from" => "whatsapp:+9647735004555", //$sendernumber
							 "body" => "*{$code}* هو رمز التحقق الخاص بك. للحفاظ على أمانك، تجنب مشاركة هذا الرمز."
						 ]
			);
			$vers = VerificationCode::where("phone", "LIKE","%".$request->phone."%")->get();
			foreach($vers as $v){
				$v->delete();
			}
			$ver = new VerificationCode();
			$ver->phone = $request->phone;
			$ver->code = $code;
			$ver->save();
			$user->token = $user->createToken('personal access token');
				
				// return response()->json(['message' => 'WhatsApp message sent successfully']);
			} catch (\Exception $e) {
				//return response()->json(['error' => $e->getMessage()], 500);
			}
			
			return response()->json([
				'success' => 1,
				'message' => 'Registered successfully.',
				'data' => $user
            ]);

		}
		
        $user = \App\Models\User::where('phone',"LIKE","%".$request->phone."%")->first();
		
        if (!$user) {
            return response()->json([
                'success' => 0,
                'data' => null,
                'message' => 'Credentials not Found!.',
            ]);
        }
		$user->avatar = "users/default.png";	
		$user->cover = "users/cover.jpg";	
			


			try {
				$twilio = new Client("ACee3f000a088bc43718654e8fd99d08aa", "c7023effb37c702e259ffb07a6b096b6");
				$code = 0;
				if($user->phone == "7517684714"){
					$code = 1111;
				}
				else{
					$code = rand(0000,9999);
				}
			//$code = 1111;
				
			$twilio->messages->create("whatsapp:+964".$request->phone, // $receiverphone
						 [
							 "from" => "whatsapp:+9647735004555", //$sendernumber
							 "body" => "*{$code}* هو رمز التحقق الخاص بك. للحفاظ على أمانك، تجنب مشاركة هذا الرمز."
						 ]
			);
			$vers = VerificationCode::where("phone","+964".$request->phone)->get();
			foreach($vers as $v){
				$v->delete();
			}
			$ver = new VerificationCode();
			$ver->phone = $request->phone;
			$ver->code = $code;
			$ver->save();
			$user->token = $user->createToken('personal access token');
				return response()->json([
				'success' => 1,
				'message' => 'Code sent using whatsapp!.',
				'data' => $user
            ]);
			} catch (\Exception $e) {
				//return response()->json(['error' => $e->getMessage()], 500);
			}
        

            $user->createToken('personal access token');
			$user->save();
            return response()->json([
                'success' => 1,
                'message' => 'User login successfully.',
                'data' => $user
            ]);

        

    }
}
