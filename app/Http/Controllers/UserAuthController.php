<?php

namespace App\Http\Controllers;

use App\Constants\Message;
use App\Services\UserAuthService;
use Illuminate\Http\Request;

class UserAuthController extends Controller
{
    protected $userAuthService;

    public function __construct(UserAuthService $userAuthService)
    {
        $this->userAuthService = $userAuthService;
    }

    /***
     * login
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     * @since: 2022/07/25 21:57
     */
    public function login(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email|max:255',
            'password' => 'required'
        ]);

        try {
            list($loginType, $token) = $this->userAuthService->doLogin($request->email, $request->password);

            if($loginType === UserAuthService::TYPE_LOGIN_TEMP){
                return response()->json([
                    'success' => TRUE,
                    'message' => 'shopbe_must_update_password',
                    'data' => [
                        'must' => 'SHOPBE_UPDATE_PASSWORD',
                        'token' => $token
                    ]
                ]);
            }
            else {
                return response()->json([
                    'success' => TRUE,
                    'message' => 'shopbe_login_success',
                    'data' => [
                        'token' => $token
                    ]
                ]);
            }
        }
        catch (\Exception $e){
            return response()->json([
                'success' => FALSE,
                'message' => $e->getMessage()
            ]);
        }
    }

    /***
     * logout
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @since: 2022/08/02 21:57
     */
    public function logout(Request $request)
    {
        try {
            $token = substr($request->header('Authorization'), 7);
            $this->userAuthService->doLogout($token);
        }
        catch (\Exception $e){
            return response()->json([
                'success' => FALSE,
                'message' => $e->getMessage()
            ]);
        }

        return response()->json([
            'success' => TRUE,
            'message' => "shopbe_user_logout_success"
        ]);
    }
}
