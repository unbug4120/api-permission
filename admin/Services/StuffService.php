<?php
/**
 * Created by PhpStorm.
 * User: Kyron Bao
 * Date: 19-3-25
 * Time: 下午11:15
 */

namespace Admin\Services;

use Admin\Models\Stuff;
use App\Exceptions\Err;
use App\Guards\CookieGuard;
use App\Services\BaseService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;

class StuffService extends BaseService
{

    public $stuff;
    const TOKEN_LENGTH = 8;
    const TOKEN_NAME    = 'admin_token';
    const TOKEN_EXPIRE = 7 * 24 * 60 * 60;

    public function login($params)
    {
        $guard = Auth::guard('admin');

        if ($guard->user()) {
            return $this->outputError(Err::AUTH_LOGGED_IN);
        }

        if ($guard->validate()) {
            $admin_token = Str::random(self::TOKEN_LENGTH);

            $stuff = $guard->user();
            $stuff->admin_token = hash('sha256', $admin_token);
            $stuff->save();

            return $this->outputSuccess($stuff, 'Login done')
                ->withCookie($this->generateCookie($admin_token, self::TOKEN_EXPIRE));
        }
        return $this->register($params);
    }

    public function register($params)
    {
        $admin_token = Str::random(self::TOKEN_LENGTH);

        $params[self::TOKEN_NAME] = hash('sha256', $admin_token);
        $params['password'] = Hash::make($params['password']);

        $stuff = new Stuff();
        $stuff->fillable(array_keys($params));
        $stuff->fill($params);
        $stuff->save();

        return $this->outputSuccess($stuff, 'Register done')
            ->withCookie($this->generateCookie($admin_token, self::TOKEN_EXPIRE));
    }

    public function logout()
    {
        /** @var CookieGuard $guard */
        $guard = Auth::guard('admin');

        $guard->logout();

        return $this->outputSuccess([], 'Logout done')
            ->cookie($this->generateCookie('', 0));
    }

    public function getStuff()
    {
        if ($stuff = Auth::Guard('admin')->user()) {
            return $this->outputSuccess($stuff);
        }
    }


    private function generateCookie($cookie_value, $expire)
    {
        return new Cookie(self::TOKEN_NAME, $cookie_value, time() + $expire);
    }
}