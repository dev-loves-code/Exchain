<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PDFChatbotService;

class ChatController extends Controller
{
    private PDFChatbotService $bot;

    public function __construct()
    {
        $this->bot = new PDFChatbotService();
    }

   public function ask(Request $request)
{
    $request->validate([
        'question' => 'required|string|max:1000'
    ]);

    $question = trim($request->input('question'));
    
    if (empty($question)) {
        return response()->json([
            'reply' => 'Please provide a question.',
            'status' => 'error'
        ], 400);
    }

    $lowerQuestion = strtolower($question);
    
    $greetingPatterns = [
        '/\b(hi|hello|hey|hola|greetings|howdy|yo)\b/i',
        '/good\s+(morning|afternoon|evening|day)/i',
        '/what\'?s\s+up/i',
        '/how\s+are\s+you/i',
        '/how\s+is\s+it\s+going/i'
    ];

    $farewellPatterns = [
    '/\b(thanks|thank you|thankyou|thx|ty|appreciate it|cheers|thank u|thanx|tysm)\b/i',
    '/bye|goodbye|see you|farewell|cya|see ya/i',
    '/have a (good|nice|great|wonderful) (day|night|evening|one)/i',
    '/much appreciated/i',
    '/you\'?re (the best|awesome|amazing|great)/i'
];

    foreach ($greetingPatterns as $pattern) {
        if (preg_match($pattern, $lowerQuestion)) {
            return response()->json([
                'reply' => "Hello! 👋 I'm here to help you with questions about money transfers, services, and payments. What would you like to know?",
                'status' => 'success'
            ]);
        }
    }

    foreach ($farewellPatterns as $pattern) {
        if (preg_match($pattern, $lowerQuestion)) {
            return response()->json([
                'reply' => "You're welcome! 😊 If you have any more questions about our services, feel free to ask!",
                'status' => 'success'
            ]);
        }
    }

    $simpleQuestions = [
        '/how are you/i' => "I'm doing great! Ready to help you with any questions about our money transfer services. What can I assist you with?",
        '/who are you/i' => "I'm your PayOne assistant! I can help you with questions about money transfers, payment services, fees, and more.",
        '/what can you do/i' => "I can help you with: money transfer questions, service fees, transfer speeds, payment methods, and general information about PayOne services!",
        '/help/i' => "I can help you with questions about: transfer services, fees, processing times, payment options, and account-related queries. What specific information do you need?"
    ];

    foreach ($simpleQuestions as $pattern => $response) {
        if (preg_match($pattern, $lowerQuestion)) {
            return response()->json([
                'reply' => $response,
                'status' => 'success'
            ]);
        }
    }

    $answer = $this->bot->ask($question);

    return response()->json([
        'reply' => $answer,
        'status' => 'success'
    ]);
}
}