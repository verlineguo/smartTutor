<?php

namespace App\Http\Controllers;

use App\Models\AnswerPDF;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\AnswerUser;
use App\Models\Gram;
use App\Models\PageNoun;
use App\Models\Plagiarism;
use App\Models\Topic;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AssignmentController extends Controller
{
    

    public function getAvailableLanguages($topicGuid)
    {
        // Ambil bahasa unik dari tabel pertanyaan berdasarkan topik
        $languages = Question::where('topic_guid', $topicGuid)->select('language')->distinct()->pluck('language');

        return response()->json(['data' => $languages]);
    }

    public function getAnswerPdf($questionGuid)
    {
        try {
            $pdfAnswer = AnswerPDF::where('question_guid', $questionGuid)->first();

            if (!$pdfAnswer) {
                return response()->json(['error' => 'PDF answer not found'], 404);
            }

            return response()->json(
                [
                    'status' => true,
                    'data' => [
                        'guid' => $pdfAnswer->guid,
                        'question_guid' => $pdfAnswer->question_guid,
                        'answer' => $pdfAnswer->answer,
                        'combined_score' => $pdfAnswer->combined_score,
                        'qa_score' => $pdfAnswer->qa_score,
                        'retrieval_score' => $pdfAnswer->retrieval_score,
                        'created_at' => $pdfAnswer->created_at,
                        'updated_at' => $pdfAnswer->updated_at,
                    ],
                ],
                200,
            );
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getPlagiarismAnalysis($userAnswerGuid)
    {
        try {
            $userAnswer = AnswerUser::where('guid', $userAnswerGuid)->first();

            if (!$userAnswer) {
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => 'User answer not found',
                    ],
                    404,
                );
            }

            $plagiarismData = Plagiarism::where('user_answer_guid', $userAnswerGuid)
                ->join('answer_llm', 'plagiarisme.ai_answer_guid', '=', 'answer_llm.guid')
                ->get(['plagiarisme.*', 'answer_llm.source']);

            $result = [
                'user_answer' => $userAnswer,
                'plagiarism_analysis' => [],
            ];

            foreach ($plagiarismData as $item) {
                $result['plagiarism_analysis'][$item->source] = [
                    'cosine_similarity' => $item->cosine,
                    'jaccard_similarity' => $item->jaccard,
                    'bert_score' => $item->bert,
                    'average' => ($item->cosine + $item->jaccard + $item->bert) / 3,
                ];
            }

            return response()->json([
                'status' => 'success',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ],
                500,
            );
        }
    }

    public function getUserProgress($userId, $topicGuid)
    {
        try {
            // Get questions for this topic
            $topicQuestions = Question::where('topic_guid', $topicGuid)->pluck('guid');

            if ($topicQuestions->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No questions found for this topic',
                ]);
            }

            // Find the most recent answer by this user
            $latestAnswer = AnswerUser::where('user_id', $userId)
                ->whereIn('question_guid', $topicQuestions)
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$latestAnswer) {
                return response()->json([
                    'success' => false,
                    'message' => 'No previous progress found',
                ]);
            }

            // Get the question to determine language and level
            $question = Question::where('guid', $latestAnswer->question_guid)->first();

            // Return language, last page, and level information
            return response()->json([
                'success' => true,
                'data' => [
                    'language' => $question->language,
                    'lastPage' => $latestAnswer->page,
                    'currentLevel' => $question->category ?? 'remembering',
                    'correctStreak' => $latestAnswer->streak ?? 0
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Error retrieving progress: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    // Get history of answers for a user and topic
    public function getHistory($userId, $topicGuid)
    {
        try {
            $history = AnswerUser::with([
                'question' => function ($query) use ($topicGuid) {
                    $query->where('topic_guid', $topicGuid)->select('guid', 'question_fix', 'category');
                },
            ])
                ->where('user_id', $userId)
                ->whereHas('question', function ($query) use ($topicGuid) {
                    $query->where('topic_guid', $topicGuid);
                })
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($item) {
                    return [
                        'guid' => $item->guid,
                        'answer' => $item->answer,
                        'question_guid' => $item->question_guid,
                        'page' => $item->page,
                        'cosine_similarity' => $item->cosine_similarity,
                        'created_at' => $item->created_at,
                        'question' => $item->question->question_fix ?? null,
                        'category' => $item->question->category ?? null,
                        'streak' => $item->streak ?? 0,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $history,
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Failed to retrieve history: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    // Submit and evaluate an answer
    public function submitAnswer(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|string',
            'question_guid' => 'required|string',
            'answer' => 'required|string',
            'topic_guid' => 'required|string',
            'current_level' => 'required|string',
            'correct_streak' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ],
                422,
            );
        }

        try {
            // Get the question details
            $question = Question::where('guid', $request->question_guid)->first();
            if (!$question) {
                return response()->json(
                    [
                        'status' => false,
                        'message' => 'Question not found',
                    ],
                    404,
                );
            }

            // Store user answer
            $userAnswer = new AnswerUser();
            $userAnswer->guid = (string) Str::uuid();
            $userAnswer->user_id = $request->user_id;
            $userAnswer->question_guid = $request->question_guid;
            $userAnswer->answer = $request->answer;
            $userAnswer->current_level = $request->current_level;
            $userAnswer->streak = $request->correct_streak;
            $userAnswer->save();

       
            // Evaluate the answer
            $evaluationResult = $this->evaluateAnswer([
                'reference_answer' => $question->answer_fix,
                'user_answer' => $request->answer,
                'current_level' => $request->current_level,
            ]);
    

            $isCorrect = $evaluationResult['is_correct'] ?? false;
            $currentStreak = $request->correct_streak;
            $currentLevel = $request->current_level;
            
            // Handle streak and level progression
            if ($isCorrect) {
                $currentStreak++;
                // Check if should level up
                if ($currentStreak >= 4) {
                    $levels = ["remembering", "understanding", "applying", "analyzing"];
                    $currentIndex = array_search($currentLevel, $levels);
                    
                    if ($currentIndex < count($levels) - 1) {
                        $currentLevel = $levels[$currentIndex + 1];
                        $currentStreak = 0;
                    }
                }
            } else {
                // Decrease streak on failure
                $currentStreak = max(0, $currentStreak - 1);
                
                // Drop level if streak is zero and not at lowest level
                if ($currentStreak == 0 && $currentLevel !== "remembering") {
                    $levels = ["remembering", "understanding", "applying", "analyzing"];
                    $currentIndex = array_search($currentLevel, $levels);
                    $currentLevel = $levels[max(0, $currentIndex - 1)];
                }
            }
            
            
            // Update user answer with evaluation results
            $userAnswer->update([
                'is_correct' => $evaluationResult['is_correct'],
                'streak' => $currentStreak,
                'evaluation_scores' => $evaluationResult['combined_score'],
            ]);

            

            // Get next question based on user's new level
            $nextQuestion = $this->getNextQuestion(
                $request->user_id, 
                $request->topic_guid, 
                $question->language, 
                $currentLevel
            );

            return response()->json([
                'status' => 'success',
                'is_correct' => $evaluationResult['is_correct'],
                'new_streak' => $currentStreak,
                'new_level' => $currentLevel,
                'nextQuestion' => $nextQuestion ? $nextQuestion->question_fix : null,
                'nextQuestionGuid' => $nextQuestion ? $nextQuestion->guid : null,
                'evaluation' => $evaluationResult,
                'data' => [
                    'user_answer_guid' => $userAnswer->guid,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error processing submission: ' . $e->getMessage());

            return response()->json(
                [
                    'status' => false,
                    'message' => 'Error processing submission',
                    'error' => $e->getMessage(),
                ],
                500,
            );
        }
    }

    
    protected function evaluateAnswer($data)
    {
        try {
            $evaluationResponse = Http::timeout(60)
                ->post(env('FLASK_API_URL') . '/evaluate', [
                    'reference_answer' => $data['reference_answer'],
                    'user_answer' => $data['user_answer'],
                    'current_level' => $data['current_level'],
                ]);

            if ($evaluationResponse->failed()) {
                Log::error('Flask API Evaluation Error', [
                    'status' => $evaluationResponse->status(),
                    'response' => $evaluationResponse->body(),
                ]);
                
                return [
                    'is_correct' => false,
                    'score' => 0,
                    'feedback' => 'Error evaluating answer'
                ];
            }

            return $evaluationResponse->json();
        } catch (\Exception $e) {
            Log::error('Evaluation Error', [
                'message' => $e->getMessage(),
            ]);

            return [
                'is_correct' => false,
                'score' => 0,
                'feedback' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    // Get next question based on student's level
    protected function getNextQuestion($userId, $topicGuid, $language, $level)
    {
        // Find questions already answered by this user
        $answeredQuestions = AnswerUser::where('answer_user.user_id', $userId)
            ->join('questions', 'answer_user.question_guid', '=', 'questions.guid')
            ->where('questions.topic_guid', $topicGuid)
            ->where('questions.category', $level)
            ->pluck('questions.guid')
            ->toArray();
            
        // Get questions that match the current level and haven't been answered yet
        $availableQuestions = Question::where('topic_guid', $topicGuid)
            ->where('language', $language)
            ->where('category', $level)
            ->whereNotIn('guid', $answeredQuestions)
            ->get();
            
        if ($availableQuestions->isEmpty()) {
            // If no new questions available, recycle old ones
            $availableQuestions = Question::where('topic_guid', $topicGuid)
                ->where('language', $language)
                ->where('category', $level)
                ->get();
        }
        
        // Return a random question from available ones
        if ($availableQuestions->isNotEmpty()) {
            return $availableQuestions->random();
        }
        
        return null;
    }
    
    



}
