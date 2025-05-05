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
            'answer' => 'required',
        ]);

        try {
            // Get user answer data
            $userAnswer = $request->input('answer');
            $userAnswerGuid = $request->input('user_answer_guid');
            $questionGuid = $request->input('question_guid');

            // Find AI answers for the same question
            $aiAnswers = DB::table('answer_llm')->join('questions', 'answer_llm.question_guid', '=', 'questions.guid')->where('questions.guid', $questionGuid)->select('answer_llm.guid as ai_answer_guid', 'answer_llm.answer')->get();

            if ($aiAnswers->isEmpty()) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'No AI answers found for comparison',
                    ],
                    404,
                );
            }

            // Initialize empty array to store all plagiarism results
            $plagiarismResults = [];
            $highestSimilarity = 0;
            $bestMatch = null;

            // Check plagiarism against each AI answer
            foreach ($aiAnswers as $aiAnswer) {
                // Calculate plagiarism using your Python service
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
                    'levenshtein_similarity' => $plagiarismData['levenshtein_similarity'],
                    'ngram_similarity' => $plagiarismData['ngram_similarity'],
                    'thresholds' => json_encode($plagiarismData['thresholds']),
                    'method_weights' => json_encode($plagiarismData['method_weights']),
                    'detected_strategies' => json_encode($plagiarismData['detected_strategies']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Store detailed results in plagiarism_details table if you need them
                if (!empty($plagiarismData['sentence_results'])) {
                    foreach ($plagiarismData['sentence_results'] as $sentenceResult) {
                        DB::table('plagiarism_details')->insert([
                            'guid' => (string) Str::uuid(),
                            'plagiarism_guid' => $guid,
                            'student_text' => $sentenceResult['student_text'],
                            'best_match' => $sentenceResult['best_match'] ?? null,
                            'is_plagiarized' => $sentenceResult['is_plagiarized'],
                            'weighted_score' => $sentenceResult['weighted_score'],
                            'individual_scores' => json_encode($sentenceResult['individual_scores'] ?? []),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                // Track the highest similarity match
                if ($plagiarismData['overall_percentage'] > $highestSimilarity) {
                    $highestSimilarity = $plagiarismData['overall_percentage'];
                    $bestMatch = [
                        'guid' => $guid,
                        'ai_answer_guid' => $aiAnswer->ai_answer_guid,
                        'overall_percentage' => $plagiarismData['overall_percentage'],
                        'detected_strategies' => $plagiarismData['detected_strategies'],
                        'sentence_results' => $plagiarismData['sentence_results'] ?? [],
                        'scores' => [
                            'cosine' => $plagiarismData['cosine_similarity'],
                            'jaccard' => $plagiarismData['jaccard_similarity'],
                            'bert' => $plagiarismData['bert_similarity'],
                            'levenshtein' => $plagiarismData['levenshtein_similarity'],
                            'ngram' => $plagiarismData['ngram_similarity'],
                        ],
                    ];
                }

                // Add to results array
                $plagiarismResults[] = [
                    'guid' => $guid,
                    'ai_answer_guid' => $aiAnswer->ai_answer_guid,
                    'overall_percentage' => $plagiarismData['overall_percentage'],
                    'thresholds' => $plagiarismData['thresholds'],
                    'method_weights' => $plagiarismData['method_weights'],
                    'detected_strategies' => $plagiarismData['detected_strategies'],
                    'scores' => [
                        'cosine' => $plagiarismData['cosine_similarity'],
                        'jaccard' => $plagiarismData['jaccard_similarity'],
                        'bert' => $plagiarismData['bert_similarity'],
                        'levenshtein' => $plagiarismData['levenshtein_similarity'],
                        'ngram' => $plagiarismData['ngram_similarity'],
                    ],
                ];
            }

            // Return success response with all plagiarism results
            return response()->json([
                'success' => true,
                'message' => 'Plagiarism check completed',
                'results' => $plagiarismResults,
                'best_match' => $bestMatch,
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Error checking plagiarism: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    
    private function calculatePlagiarism($userAnswer, $aiAnswer)
    {
        try {
            // Assuming your Python API is available at this endpoint
            $response = Http::timeout(300)->post(env('FLASK_API_URL') . '/checkPlagiarism', [
                'user_answer' => $userAnswer,
                'ai_answer' => $aiAnswer,
            ]);

            if (!$response->successful()) {
                throw new \Exception('Plagiarism service unavailable');
            }

            $data = $response->json();

            // Extract the relevant data from the response
            return [
                'overall_percentage' => $data['overall_percentage'],
                'cosine_similarity' => $data['overall_similarities']['cosine'],
                'jaccard_similarity' => $data['overall_similarities']['jaccard'],
                'bert_similarity' => $data['overall_similarities']['bert'],
                'levenshtein_similarity' => $data['overall_similarities']['levenshtein'],
                'ngram_similarity' => $data['overall_similarities']['ngram'],
                'detected_strategies' => $data['detected_strategies'],
                'sentence_results' => $data['sentence_results'] ?? [],
                'thresholds' => $data['thresholds'],
    'method_weights' => $data['method_weights'],
            ];
        } catch (\Exception $e) {
            throw new \Exception('Error calculating plagiarism: ' . $e->getMessage());
        }
    }
}
