<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Melihovv\Base64ImageDecoder\Base64ImageDecoder;
use Tymon\JWTAuth\Exceptions\JWTException as ExceptionsJWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->all();

        $validateData = Validator::Make($data, [
            'name' => 'required|string',
            'email' => 'required|email:dns|unique:users',
            'password' => 'required|string:min:6',
            'pin' => 'required|digits:6',
        ]);

        if ($validateData->fails()) {
            return response()->json(['errors' => $validateData->messages()], 400);
        }

        DB::beginTransaction();
        try {
            $profilePicture = null;
            $ktp = null;

            if ($request->profile_picture) {
                $profilePicture = $this->uploadImage($request->profile_picture);
            }

            if ($request->ktp) {
                $ktp = $this->uploadImage($request->ktp);
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'username' => $request->email,
                'password' => bcrypt($request->password),
                'profile_picture' => $profilePicture,
                'ktp' => $ktp,
                'verified' => ($ktp) ? true : false,
            ]);

            Wallet::create([
                'user_id' => $user->id,
                'balance' => 0,
                'pin' => $request->pin,
                'card_number' => $this->generateCardNumber(16),
            ]);
            DB::commit();

            $token = JWTAuth::attempt(['email' => $request->email, 'password' => $request->password]);

            $userResponse = getUser($request->email);
            $userResponse->token = $token;
            $userResponse->token_expires_in = auth()->factory()->getTTL() * 60;
            $userResponse->token_type = 'bearer';

            return response()->json($userResponse);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        $validateData = Validator::Make($credentials, [
            'email' => 'required|email:dns',
            'password' => 'required|string:min:6',
        ]);

        if ($validateData->fails()) {
            return response()->json(['errors' => $validateData->messages()], 400);
        }

        try {
            $token = JWTAuth::attempt($credentials);

            if (!$token) {
                return response()->json(['message' => 'Login credentials are invalid']);
            }

            $userResponse = getUser($request->email);
            $userResponse->token = $token;
            $userResponse->token_expires_in = auth()->factory()->getTTL() * 60;
            $userResponse->token_type = 'bearer';

            return response()->json($userResponse);
        } catch (ExceptionsJWTException $th) {
            return response()->json(['message' => $th->getMessage()]);
        }
    }

    private function generateCardNumber($length)
    {
        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= mt_rand(0, 9);
        }

        $wallet = Wallet::where('card_number', $result)->exists();

        if ($wallet) {
            return $this->generateCardNumber($length);
        }

        return $result;
    }

    private function uploadImage($base64Image)
    {
        $decoder = new Base64ImageDecoder($base64Image, $allowedFormats = ['jpeg', 'png', 'jpg']);

        $decodedContent = $decoder->getDecodedContent();
        $format = $decoder->getFormat();
        $image = Str::random(10) . '.' . $format;
        Storage::disk('public')->put($image, $decodedContent);

        return $image;
    }

    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Log out success']);
    }
}
