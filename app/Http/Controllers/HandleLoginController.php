<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HandleLoginController extends Controller
{
    public function handleLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'current-password' => ['required'],
        ]);

        $password = $credentials['current-password'];
        unset($credentials['current-password']);
        $credentials['password'] = $password;

        if (auth()->attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->intended('admin/');
        }

        return response()->json(['error' => 'Invalid credentials'], 401);
    }
}
