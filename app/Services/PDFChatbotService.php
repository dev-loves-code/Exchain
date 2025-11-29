<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Smalot\PdfParser\Parser;
use Illuminate\Support\Facades\Log;

class PDFChatbotService
{
    private ?string $pdfText = null;
    private array $chunks = [];
    private bool $pdfLoaded = false;

    public function __construct()
    {
        $this->loadPdfText();
        if ($this->pdfLoaded) {
            $this->makeChunks(2000);
        }
    }

    private function loadPdfText(): void
    {
        $pdfPath = storage_path('app/chatbot/Money Transfer Guide.pdf');

        if (!file_exists($pdfPath)) {
            Log::error("PDF not found at: {$pdfPath}");
            $this->pdfLoaded = false;
            return;
        }

        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($pdfPath);
            $text = $pdf->getText();
            
            if (empty(trim($text))) {
                Log::error("PDF parsed but returned empty text");
                $this->pdfLoaded = false;
                return;
            }

            $this->pdfText = trim(preg_replace('/\s+/', ' ', $text));
            $this->pdfLoaded = true;
            
            Log::info("PDF loaded successfully - Characters: " . strlen($this->pdfText));

        } catch (\Exception $e) {
            Log::error("PDF parsing failed: " . $e->getMessage());
            $this->pdfLoaded = false;
        }
    }

    private function makeChunks(int $size): void
    {
        if (!$this->pdfText) return;

        $len = strlen($this->pdfText);
        for ($i = 0; $i < $len; $i += $size) {
            $this->chunks[] = substr($this->pdfText, $i, $size);
        }
        
        Log::info("Created " . count($this->chunks) . " chunks");
    }

    private function findBestChunk(string $question): ?string
    {
        if (!$this->chunks) return null;

        $words = explode(' ', strtolower($question));
        $bestScore = -1;
        $bestChunk = null;

        foreach ($this->chunks as $chunk) {
            $score = 0;
            $lower = strtolower($chunk);

            foreach ($words as $w) {
                if (strlen($w) <= 3) continue;
                $score += substr_count($lower, $w);
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestChunk = $chunk;
            }
        }

        return $bestChunk ?? $this->chunks[0];
    }

    private function buildPrompt(string $chunk, string $q): string
    {
        return <<<PROMPT
            You are a helpful support chatbot for a money transfer service. 
            Answer the user's question using ONLY the information from the provided document excerpt.
            If the answer cannot be found in the document, say "I don't have information about that in my knowledge base."

            DOCUMENT EXCERPT:
            {$chunk}

            QUESTION: {$q}

            ANSWER:
            PROMPT;
    }

    public function ask(string $question): string
    {
        if (!$this->pdfLoaded) {
            return "PDF knowledge base is not available.";
        }

        if (empty($this->chunks)) {
            return "No content available from PDF.";
        }

        $chunk = $this->findBestChunk($question);
        if (!$chunk) {
            return "Could not find relevant content for your question.";
        }

        $prompt = $this->buildPrompt($chunk, $question);

        $apiKey = env('GEMINI_API_KEY');
        if (!$apiKey) {
            Log::error("GEMINI_API_KEY not found in environment");
            return "API configuration error.";
        }
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$apiKey}";

        $payload = [
            "contents" => [
                [
                    "parts" => [
                        ["text" => $prompt]
                    ]
                ]
            ],
            "generationConfig" => [
                "temperature" => 0.1,
                "maxOutputTokens" => 1000,
            ]
        ];

        try {
            Log::info("Sending request to Gemini API", ['question' => $question]);
            
            $response = Http::timeout(60)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post($url, $payload);

            if (!$response->successful()) {
                Log::error("Gemini API error", [
                    "status" => $response->status(), 
                    "body" => $response->body(),
                    "url" => $url
                ]);
                
                return "Sorry, I'm having trouble connecting to the AI service. Status: " . $response->status();
            }

            $data = $response->json();
            
            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                $answer = trim($data['candidates'][0]['content']['parts'][0]['text']);
                Log::info("Gemini API success", ['answer_length' => strlen($answer)]);
                return $answer;
            } else {
                Log::error("Unexpected response format", ["response" => $data]);
                return "Sorry, I received an unexpected response format.";
            }

        } catch (\Throwable $e) {
            Log::error("Gemini Exception", ["error" => $e->getMessage()]);
            return "Error processing your request: " . $e->getMessage();
        }
    }

    // public function getStatus(): array
    // {
    //     $correctPath = storage_path('app/chatbot/Money Transfer Guide.pdf');
        
    //     return [
    //         'pdf_loaded' => $this->pdfLoaded,
    //         'pdf_path' => $correctPath,
    //         'pdf_exists' => file_exists($correctPath),
    //         'text_length' => $this->pdfText ? strlen($this->pdfText) : 0,
    //         'chunks_count' => count($this->chunks),
    //     ];
    // }
}