<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\TestEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailTestController extends Controller
{
    public function sendTest(Request $request)
    {
        if (!auth()->user()->hasPermission('roles.update')) {
            abort(403, 'Only administrators can send test emails.');
        }

        $validated = $request->validate([
            'to' => 'required|email',
            'subject' => 'nullable|string|max:255',
            'message' => 'nullable|string|max:1000',
        ]);

        $subject = $validated['subject'] ?? 'Test Email from MetCon Application';
        $message = $validated['message'] ?? 'This is a test email to verify your Postmark email configuration is working correctly.';

        try {
            Mail::to($validated['to'])->send(new TestEmail($subject, $message));

            return response()->json([
                'success' => true,
                'message' => 'Test email sent successfully!',
                'details' => [
                    'to' => $validated['to'],
                    'subject' => $subject,
                    'sent_at' => now()->toIso8601String(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Email test failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to send test email',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getConfig()
    {
        if (!auth()->user()->hasPermission('roles.update')) {
            abort(403, 'Only administrators can view email configuration.');
        }

        return response()->json([
            'mailer' => config('mail.default'),
            'from_address' => config('mail.from.address'),
            'from_name' => config('mail.from.name'),
        ]);
    }
}
