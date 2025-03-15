<?php

namespace App\Http\ApiControllers;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AccountAuthController extends Controller
{
    public function register(Request $request)
    {
        $this->validate($request, [
            'email' => ["required", "email", "unique:accounts,email"],
            'phone' => ["nullable", "phone", "unique:accounts,phone"],
            'name' => ["nullable", 'string'],
            'password' => ["required", "min:8", "confirmed"],
        ]);

        // Create new account
        $account = new Account();
        $account->name = $request->name;
        $account->email = $request->email;
        $account->phone = $request->phone;
        $account->password = app('hash')->make($request->password);
        $account->save();


        if ($tenant = $request->get('tenant')) {
            $name = $request->get('tenant')['name'] ?? null;
            $tenant = $account->tenants()->create(["name" => $name]);
        }

        return response()->json(
            array_merge(
                $this->responseLogin($account, $request),
                ["tenant" => $tenant?->toArray()]
            )
        );;
    }

    public function login (Request $request)
    {
        $this->validate($request, [
            'username' => 'required',
            'password' => 'required',
        ]);

        /** @var \App\Models\Account $account */
        $account = Account::findUsername($request->username);

        if (! $account || ! Hash::check($request->password, $account->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ])->status(422);
        }

        return response()->json(
            $this->responseLogin($account, $request)
        );

    }

    protected function responseLogin($account,Request $request)
    {
        /** @var \Laravel\Sanctum\NewAccessToken $token */
        $token = $account->createTokenApp($request);

        return [
            "type" => "Bearer",
            "token" => $token->plainTextToken,
            "expires_in" => ($token->accessToken->expires_at)?->timestamp,
        ];
    }

    public function fetch (Request $request)
    {
        $account = $request->user();
        return new Response($account);
    }

    public function forgotPassword (Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::broker('accounts')->sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
                    ? response()->json(['message' => __($status)])
                    : response()->json(['message' => __($status)], 406);
    }

    public function resetPassword (Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::broker('accounts')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (Account $account, string $password) {
                $account->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(str()->random(60));

                $account->save();

                // event(new PasswordReset($account));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => __($status)])
            : response()->json(['message' => __($status)], 406);
    }
}
