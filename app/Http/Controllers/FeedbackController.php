<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FeedbackController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'username' => 'nullable|string|max:255',
            'message' => 'required|string|max:2000',
            'type' => 'required|string|in:suggestion,bug'
        ]);

        $feedback = Feedback::create([
            'email' => $validated['email'],
            'username' => $validated['username'],
            'message' => $validated['message'],
            'type' => $validated['type'],
        ]);

        $logMessage = $validated['type'] === 'bug' ? 'Bug report submitted' : 'Suggestion submitted';
        Log::info($logMessage, [
            'feedback_id' => $feedback->id,
            'email' => $feedback->email,
            'type' => $feedback->type
        ]);

        return response()->json(['success' => true]);
    }
}
