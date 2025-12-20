<?php

namespace App\Http\Controllers;

use http\Env\Response;
use Illuminate\Http\Request;

class HandleLoginController extends Controller
{
    public function handleLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (auth()->attempt($credentials)) {
            $request->session()->regenerate();

            return redirect()->intended('dashboard');
        }

        return response()->json(['error' => 'Invalid credentials'], 401);
    }
}
