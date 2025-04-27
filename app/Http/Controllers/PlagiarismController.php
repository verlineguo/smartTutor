<?php

namespace App\Http\Controllers;

use App\Models\AnswerLLM;
use App\Models\ChatHistory;
use App\Models\Plagiarism;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PlagiarismController extends Controller
{


    public function checkPlagiarism(Request $request)
    {
        set_time_limit(3000);
        $request->validate([
            'user_id' => 'required',
            'question_guid' => 'required',
            'topic_guid' => 'required',
            'user_answer_guid' => 'required',
            'answer' => 'required'
        ]);

        try {
            // Get user answer data
            $userAnswer = $request->input('answer');
            $userAnswerGuid = $request->input('user_answer_guid');
            $questionGuid = $request->input('question_guid');
            
            // Find AI answers for the same question
            $aiAnswers = DB::table('answer_llm')
                ->join('questions', 'answer_llm.question_guid', '=', 'questions.guid')
                ->where('questions.guid', $questionGuid)
                ->select('answer_llm.guid as ai_answer_guid', 'answer_llm.answer')
                ->get();
            
            if ($aiAnswers->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No AI answers found for comparison'
                ], 404);
            }

            // Initialize empty array to store all plagiarism results
            $plagiarismResults = [];

            // Check plagiarism against each AI answer
            foreach ($aiAnswers as $aiAnswer) {
                // Calculate plagiarism using your API
                $plagiarismData = $this->calculatePlagiarism($userAnswer, $aiAnswer->answer);
                
                // Store each result in the database
                $guid = (string) Str::uuid();
                
                DB::table('plagiarism')->insert([
                    'guid' => $guid,
                    'user_answer_guid' => $userAnswerGuid,
                    'ai_answer_guid' => $aiAnswer->ai_answer_guid,
                    'cosine_similarity' => $plagiarismData['cosine_similarity'],
                    'jaccard_similarity' => $plagiarismData['jaccard_similarity'],
                    'bert_score' => $plagiarismData['bert_similarity'],
                    // 'highlighted_text' => $plagiarismData['highlighted_text'],
                    // 'sequence_matching' => json_encode([
                    //     'highlighted_text' => $plagiarismData['highlighted_text'],
                    //     'pattern_matches' => $plagiarismData['multi_pattern_matches']
                    // ]),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                // Add to results array
                $plagiarismResults[] = [
                    'guid' => $guid,
                    'ai_answer_guid' => $aiAnswer->ai_answer_guid,
                    'scores' => [
                        'cosine' => $plagiarismData['cosine_similarity'],
                        'jaccard' => $plagiarismData['jaccard_similarity'],
                        'bert' => $plagiarismData['bert_similarity']
                    ]
                ];
            }

            // Return success response with all plagiarism results
            return response()->json([
                'success' => true,
                'message' => 'Plagiarism check completed',
                'results' => $plagiarismResults
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error checking plagiarism: ' . $e->getMessage()
            ], 500);
        }
        

    }
    private function calculatePlagiarism($userAnswer, $aiAnswer)
    {
        try {
         
            // Assuming your Python API is available at this endpoint
            $response = Http::post(env('FLASK_API_URL') . '/checkPlagiarism', [

                'user_answer' => $userAnswer,
                'ai_answer' => $aiAnswer
            ]);

            if ($response->successful()) {
                return $response->json();
            } else {
                // Return default values if API call fails
                return [
                    'cosine_similarity' => 0,
                    'jaccard_similarity' => 0,
                    'bert_similarity' => 0,
                    // 'highlighted_text' => '',
                    // 'multi_pattern_matches' => []
                ];
            }
        } catch (\Exception $e) {
            // Log error and return default values
            Log::error('Plagiarism API error: ' . $e->getMessage());
            return [
                'cosine_similarity' => 0,
                'jaccard_similarity' => 0,
                'bert_similarity' => 0,
                // 'highlighted_text' => '',
                // 'multi_pattern_matches' => []
            ];
        }
    }
}
