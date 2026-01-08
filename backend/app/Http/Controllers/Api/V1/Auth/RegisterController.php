<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Workspace;
use App\Models\Plan;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:120'],
            'email' => ['required','email','max:190','unique:users,email'],
            'password' => ['required','confirmed', Password::min(8)],
            'workspace_name' => ['nullable','string','max:120'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $workspace = Workspace::create([
            'name' => $data['workspace_name'] ?? ($user->name . "'s workspace"),
            'owner_user_id' => $user->id,
        ]);

        $user->workspace_id = $workspace->id;
        $user->save();

        $freePlan = Plan::query()->where('code', 'free')->firstOrFail();

        Subscription::create([
            'workspace_id' => $workspace->id,
            'plan_id' => $freePlan->id,
            'status' => 'active',
            'current_period_start' => Carbon::now()->startOfMonth(),
            'current_period_end' => Carbon::now()->endOfMonth(),
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
            'workspace' => $workspace,
        ], 201);
    }
}
