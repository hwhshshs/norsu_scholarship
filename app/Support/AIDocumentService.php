<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIDocumentService
{
    /**
     * Analyze a document (Image or PDF) using Gemini AI.
     */
    public static function analyzeBillingDocument($filePath, $mimeType)
    {
        $apiKey = env('GEMINI_API_KEY');

        if (!$apiKey) {
            return [
                'success' => false,
                'error' => 'Gemini API Key is missing. Please add GEMINI_API_KEY to your .env file.',
            ];
        }

        try {
            $fileData = base64_encode(file_get_contents($filePath));
            
            $prompt = "Act as an expert data extractor. Analyze this billing document and extract all scholarship records. 
                      Return a JSON array of objects with these keys: 
                      'program' (e.g. CHED, TDP-TES), 
                      'semester' (e.g. 1st, 2nd), 
                      'academic_year' (e.g. 2024-2025), 
                      'paid' (amount), 
                      'scholar_count' (number of students),
                      'submitdate' (YYYY-MM-DD),
                      'student_id' (if available),
                      'full_name' (if available).
                      ONLY return the JSON array, no extra text.";

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                            [
                                'inline_data' => [
                                    'mime_type' => $mimeType,
                                    'data' => $fileData
                                ]
                            ]
                        ]
                    ]
                ]
            ]);

            if ($response->successful()) {
                $result = $response->json();
                $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? '[]';
                
                // Clean up markdown code blocks if any
                $text = preg_replace('/```json|```/', '', $text);
                
                $data = json_decode(trim($text), true);
                
                return [
                    'success' => true,
                    'rows' => is_array($data) ? $data : [],
                ];
            }

            return [
                'success' => false,
                'error' => 'AI Analysis failed: ' . ($response->json()['error']['message'] ?? 'Unknown error'),
            ];

        } catch (\Exception $e) {
            Log::error('AI Analysis Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'An error occurred during analysis: ' . $e->getMessage(),
            ];
        }
    }
}
