<?php
/**
 * Created by PhpStorm.
 * Date: 2022-08-01
 * Time: 22:17
 */

namespace App\Services;

use App\Constants\Message;
use App\Jobs\SendVerificationEmail;
use App\Repositories\UserPasswordResetRepository;
use App\Repositories\UserRepository;
use App\Models\UserModel;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\JWTAuth;

class UserAuthService
{
    const TYPE_LOGIN = 'TYPE_LOGIN';
    const TYPE_LOGIN_TEMP = 'TYPE_LOGIN_TEMP';

    private $jwt;
    private $userRepository;
    private $userPasswordResetRepository;

    public function __construct(JWTAuth $jwt, UserRepository $userRepository, UserPasswordResetRepository $userPasswordResetRepository)
    {
        $this->jwt = $jwt;
        $this->userRepository = $userRepository;
        $this->userPasswordResetRepository = $userPasswordResetRepository;
    }

    public function makeTokenInvalid(string $token){
        $this->jwt->setToken($token)->invalidate();
    }

    /***
     * register
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     * @since: 2022/07/25 23:13
     */
    public function doRegister(array $args): UserModel
    {
        try {
            $user = new UserModel();
            $user->username = $args['username'];
            $user->name = $args['name'];
            $user->email = $args['email'];
            $user->password = Hash::make($args['password']);
            $user->phone_number = $args['phone_number'];
            $user->email_token = base64_encode('TOKEN:' . $args['email']);

            if (!$user->save()) {
                throw new \Exception('shopbe_failure_created_user');
            }
        } catch (\Exception $e) {
            if ($e instanceof QueryException && (int)$e->getCode() === 23000) {
                throw new \Exception('shopbe_user_duplicated_email');
            }
            throw $e;
        }

        // send email to confirm here.
        dispatch(new SendVerificationEmail($user));

        return $user;
    }

    /***
     * doVerifyEmail
     *
     * @param string $token
     *
     * @return bool
     * @throws \Exception
     * @since: 2022/08/02 22:33
     */
    public function doVerifyEmail(string $token): bool
    {
        $user = $this->userRepository->findWhere(['email_token' => $token])->first();
        if($user->exists){
            $user->verified = TRUE;
            $user->email_token = NULL;
            return $user->save();
        }

        throw new \Exception('shopbe_user_not_found');
    }

    /***
     * doLogin
     *
     * @param string $email
     * @param string $password
     *
     * @return array
     * @throws JWTException
     * @since: 2022/08/02 21:47
     */
    public function doLogin(string $email, string $password): array
    {
        try {
            $user = $this->userRepository->findWhere(['email' => $email])->first();
            if(!($user && $user->exists && in_array(['1', '2'], $user->role))){
                throw new \Exception('shopbe_must_verify_email');
            }

            $reset = $this->userPasswordResetRepository->findWhere([
                ['email', '=', $email],
                ['created_at', '>', Carbon::now()->subMinute(config('auth.reminder.expire', 5))->format("Y-m-d H:i:s")]
            ])->first();

            if(!!$reset){
                if(Hash::check($password, $reset->token)){
                    $token = Hash::make(Str::random());
                    $user->login_token = $token;
                    $user->save();

                    return [self::TYPE_LOGIN_TEMP, $token];
                }

                throw new \Exception(Message::ERR_SHOPBE_WRONG_INFORMATION);
            }
            else{
                $jwtAttempt = compact('email', 'password');
                if (!$token = $this->jwt->attempt($jwtAttempt)) {
                    throw new \Exception('user_not_found');
                }

                $user->auth_token = $token;
                $user->save();

                return [self::TYPE_LOGIN, $token];
            }
        } catch (JWTException $e) {
            if ($e instanceof JWTException){
                throw new \Exception('failed_to_create_token');
            }
            throw $e;
        }
    }

    /***
     * doLogout
     *
     * @param string $token
     *
     * @throws \Exception
     * @since: 2022/08/02 21:56
     */
    public function doLogout(string $token)
    {
        $user = $this->userRepository->findWhere(['auth_token' => $token])->first();
        if (!$user || !$user->exists) {
            throw new \Exception('shopbe_user_not_found');
        }

        $user->auth_token = NULL;
        if (!$user->save()){
            throw new \Exception('shopbe_user_system_error');
        }

        $this->makeTokenInvalid($token);
    }
}
