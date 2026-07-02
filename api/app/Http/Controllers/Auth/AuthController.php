<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\UserAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Sanctum API bearer-token authentication (§5.5).
 *
 * Login issues a personal access token that clients send as
 * `Authorization: Bearer <token>`. There is no stateful SPA session: the backend
 * never relies on a browser session to remember the user. The authenticatable
 * model is App\Models\UserAccount (table user_accounts), not the framework
 * default `users` [05_system-design.md §4; 02_business-rules.md BR-018].
 */
class AuthController extends Controller
{
    /**
     * Log in with email + password and, for an active account, issue a bearer token.
     *
     * The plain-text token is returned exactly once; the client stores it and
     * sends it as `Authorization: Bearer <token>` on later requests. Bad
     * credentials and inactive accounts fail identically and create no token
     * [03_use-cases.md UC-00; 02_business-rules.md BR-018].
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $account = UserAccount::query()
            ->where('email', $credentials['email'])
            ->first();

        // Fail closed and uniformly: unknown email, wrong password, or an inactive
        // account all produce the same failure and never mint a token.
        // Authentication denial is 401 (not a 422 validation error); the response
        // never reveals which part failed nor that an account exists but is inactive
        // [docs/conventions.md API error responses — 401; 02_business-rules.md BR-018].
        if ($account === null
            || ! Hash::check($credentials['password'], (string) $account->password)
            || ! $account->isActive()
        ) {
            return response()->json([
                'message' => 'The provided credentials are incorrect.',
            ], 401);
        }

        $token = $account->createToken('api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $account,
        ]);
    }

    /**
     * Return the currently authenticated account (bearer token required).
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }

    /**
     * Revoke the access token used for the current request.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }
}
