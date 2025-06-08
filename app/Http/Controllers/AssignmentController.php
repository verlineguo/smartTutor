<?php

namespace App\Http\Controllers;

use App\Models\AnswerPDF;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\AnswerUser;
use App\Models\Plagiarism;
use App\Models\PlagiarismDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AssignmentController extends Controller
{
    public function getAvailableLanguages($topicGuid)
    {
        // Ambil bahasa unik dari tabel pertanyaan berdasarkan topik
        $languages = Question::where('topic_guid', $topicGuid)->select('language')->distinct()->pluck('language');

        return ResponseController::getResponse(['data' => $languages], 200, 'Language retrieved successfully.');


    }

    public function getAllAnswers($userId, $topicGuid)
    {
        try {
            $answers = AnswerUser::with([
                'question' => function ($query) use ($topicGuid) {
                    $query->where('topic_guid', $topicGuid)->select('guid', 'question_fix', 'category', 'page');
                },
            ])
                ->where('user_id', $userId)
                ->whereHas('question', function ($query) use ($topicGuid) {
                    $query->where('topic_guid', $topicGuid);
                })
                ->orderBy('created_at', 'desc')
                ->get();

            if ($answers->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No answer history found for this user and topic',
                    'data' => [],
                ]);
            }

            // Format data to include question information
            $formattedAnswers = $answers->map(function ($answer) {
                return [
                    'guid' => $answer->guid,
                    'answer' => $answer->answer,
                    'question_guid' => $answer->question_guid,
                    'question' => $answer->question->question_fix ?? 'Question not available',
                    'current_level' => $answer->current_level,
                    'category' => $answer->question->category ?? 'unknown',
                    'page' => $answer->question->page ?? null,
                    'is_correct' => $answer->is_correct,
                    'evaluation_scores' => $answer->evaluation_scores,
                    'lecturer_score' => $answer->lecturer_score,
                    'attempt_number' => $answer->attempt_number,
                    'created_at' => $answer->created_at,
                    'updated_at' => $answer->updated_at,
                ];
            });

            return ResponseController::getResponse(['data' => $formattedAnswers], 200, 'Answer retrieved successfully.');

        } catch (\Exception $e) {
            Log::error('Error retrieving all answers: ' . $e->getMessage());
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Failed to retrieve answer history: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    public function getAnswerPdf($questionGuid)
    {
        try {
            $pdfAnswer = AnswerPDF::where('question_guid', $questionGuid)->first();

            if (!$pdfAnswer) {
                return response()->json(['error' => 'PDF answer not found'], 404);
            }

           

            return ResponseController::getResponse([
                    'status' => true,
                    'data' => [
                        'guid' => $pdfAnswer->guid,
                        'question_guid' => $pdfAnswer->question_guid,
                        'answer' => $pdfAnswer->answer,
                        'combined_score' => $pdfAnswer->combined_score,
                        'qa_score' => $pdfAnswer->qa_score,
                        'retrieval_score' => $pdfAnswer->retrieval_score,
                        'page_references' => $pdfAnswer->page_references,
                        'created_at' => $pdfAnswer->created_at,
                        'updated_at' => $pdfAnswer->updated_at,
                    ],
                ], 200, 'Answer history retrieved successfully.');

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

            
            return ResponseController::getResponse(['data' => $result], 200, 'Plagiarism Analysis retrieved successfully.');

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

    // Get history of answers for a user and topic
    public function getHistory($userId, $topicGuid)
    {
        try {
            $answers = AnswerUser::with([
                'question' => function ($query) use ($topicGuid) {
                    $query->where('topic_guid', $topicGuid)->select('guid', 'question_fix', 'category', 'page');
                },
            ])
                ->where('user_id', $userId)
                ->where('is_correct', 1) // Only get correct answers for main history
                ->whereHas('question', function ($query) use ($topicGuid) {
                    $query->where('topic_guid', $topicGuid);
                })
                ->orderBy('created_at', 'desc')
                ->get();

            if ($answers->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No answer history found for this user and topic',
                    'data' => [],
                ]);
            }

            // Format data to include question information
            $formattedAnswers = $answers->map(function ($answer) {
                return [
                    'guid' => $answer->guid,
                    'answer' => $answer->answer,
                    'question_guid' => $answer->question_guid,
                    'question' => $answer->question->question_fix ?? 'Question not available',
                    'current_level' => $answer->current_level,
                    'category' => $answer->question->category ?? $answer->current_level,
                    'page' => $answer->question->page ?? null,
                    'language' => $answer->question->language ?? 'indonesia',
                    'is_correct' => $answer->is_correct,
                    'evaluation_scores' => $answer->evaluation_scores,
                    'lecturer_score' => $answer->lecturer_score,
                    'attempt_number' => $answer->attempt_number,
                    'created_at' => $answer->created_at,
                    'updated_at' => $answer->updated_at,
                ];
            });

         

            return ResponseController::getResponse(['data' => $formattedAnswers], 200, 'Answer history retrieved successfully.');

        } catch (\Exception $e) {
            Log::error('Error retrieving history: ' . $e->getMessage());
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Failed to retrieve answer history: ' . $e->getMessage(),
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
            'current_level' => 'required|in:remembering,understanding,applying,analyzing',
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

            // Get reference pages for feedback
            $pdfAnswer = AnswerPDF::where('question_guid', $request->question_guid)->first();
            $referencePages = null;
            if ($pdfAnswer && $pdfAnswer->page_references) {
                $referencePages = json_decode($pdfAnswer->page_references, true);
            }

            // Evaluate the answer
            $evaluationResult = $this->evaluateAnswer([
                'reference_answer' => $question->answer_fix,
                'user_answer' => $request->answer,
                'current_level' => $request->current_level,
            ]);

            $combinedScore = $evaluationResult['combined_score'] ?? 0;
            $threshold = $question->threshold ?? 0.5;
            $isCorrect = $combinedScore * 100 >= $threshold;
            
            Log::info($isCorrect);
            
            // FIXED: Set current_level based on the question's category, not progression
            $currentLevel = $question->category;
            $hasCompletedAllLevels = false;

            if ($isCorrect && $currentLevel === 'analyzing') {
                // Check if user has completed all analyzing questions
                $hasCompletedAllLevels = $this->checkAnalyzingLevelCompleted(
                    $request->user_id, 
                    $request->topic_guid,
                    $question->language
                );
            }

            // Save user answer with the question's actual category
            $userAnswer = new AnswerUser();
            $userAnswer->guid = (string) Str::uuid();
            $userAnswer->user_id = $request->user_id;
            $userAnswer->question_guid = $request->question_guid;
            $userAnswer->answer = $request->answer;
            $userAnswer->current_level = $currentLevel; // Use question's category
            $userAnswer->is_correct = $isCorrect;
            $userAnswer->evaluation_scores = $combinedScore;
            $userAnswer->save();

            // Get next question logic - FIXED to use proper level determination
            $nextQuestion = null;
            if ($isCorrect && !$hasCompletedAllLevels) {
                $nextLevelToShow = $this->getNextLevelForUser($request->user_id, $request->topic_guid, $question->language);
                $nextQuestion = $this->getNextQuestion($request->user_id, $request->topic_guid, $question->language, $nextLevelToShow);
            } else if (!$isCorrect) {
                $nextQuestion = $question; // Keep the same question if incorrect
            }
            // If completed all levels, nextQuestion stays null

            $feedback = $this->generateFeedback($combinedScore, $isCorrect, $currentLevel);


            return ResponseController::getResponse(
                [
                'status' => 'success',
                'is_correct' => $isCorrect,
                'new_level' => $this->getNextLevelForUser($request->user_id, $request->topic_guid, $question->language),
                'nextQuestion' => $nextQuestion ? $nextQuestion->question_fix : null,
                'nextQuestionGuid' => $nextQuestion ? $nextQuestion->guid : null,
                'evaluation' => $evaluationResult,
                'reference_pages' => $referencePages,
                'data' => [
                    'user_answer_guid' => $userAnswer->guid,
                ],
                'feedback' => $feedback,
                'has_completed_all_levels' => $hasCompletedAllLevels,
            ], 200, 'Answer Submitted successfully.');


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


    private function generateFeedback($score, $isCorrect, $level)
    {
        if ($isCorrect) {
            if ($score > 0.9) {
                return 'Jawaban kamu sudah sangat baik dan tepat.';
            } elseif ($score > 0.8) {
                return 'Jawaban kamu cukup baik dan sudah benar pada poin-poin utama.';
            } else {
                return 'Jawaban kamu cukup, tapi masih perlu dipahami lebih dalam.';
            }
        } else {
            if ($score > 0.5) {
                return 'Jawaban hampir benar, coba cek lagi materi dan perbaiki.';
            } elseif ($score > 0.3) {
                return 'Jawaban kurang tepat, sebaiknya pelajari ulang materi.';
            } else {
                return 'Jawaban kurang benar, silakan pelajari materi lebih lanjut.';
            }
        }
    }


     protected function getNextLevelForUser($userId, $topicGuid, $language)
    {
        $levels = ['remembering', 'understanding', 'applying', 'analyzing'];
        
        foreach ($levels as $level) {
            // Check if user has correctly answered at least one question in this category
            $hasCompletedLevel = AnswerUser::where('user_id', $userId)
                ->where('is_correct', 1)
                ->whereHas('question', function ($query) use ($topicGuid, $language, $level) {
                    $query->where('topic_guid', $topicGuid)
                          ->where('language', $language)
                          ->where('category', $level);
                })
                ->exists();
            
            if (!$hasCompletedLevel) {
                return $level; // Return the first level they haven't completed
            }
        }
        
        // If all levels completed, return analyzing
        return 'analyzing';
    }

    /**
     * UPDATED METHOD: Check if user has completed all analyzing questions
     */
    protected function checkAnalyzingLevelCompleted($userId, $topicGuid, $language)
    {
        // Get all analyzing questions for this topic
        $analyzingQuestions = Question::where('topic_guid', $topicGuid)
            ->where('language', $language)
            ->where('category', 'analyzing')
            ->pluck('guid')
            ->toArray();

        if (empty($analyzingQuestions)) {
            return true; // If no analyzing questions exist, consider it completed
        }

        // Get correctly answered analyzing questions
        $correctlyAnsweredAnalyzing = AnswerUser::where('user_id', $userId)
            ->whereIn('question_guid', $analyzingQuestions)
            ->where('is_correct', 1)
            ->distinct('question_guid')
            ->pluck('question_guid')
            ->toArray();

        // Check if all analyzing questions have been answered correctly
        return count($correctlyAnsweredAnalyzing) >= count($analyzingQuestions);
    }



    protected function evaluateAnswer($data)
    {
        try {
            $evaluationResponse = Http::timeout(60)->post(env('FLASK_API_URL') . '/evaluate', [
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
                    'combined_score' => 0,
                    'feedback' => 'Error evaluating answer',
                ];
            }

            return $evaluationResponse->json();
        } catch (\Exception $e) {
            Log::error('Evaluation Error', [
                'message' => $e->getMessage(),
            ]);

            return [
                'is_correct' => false,
                'combined_score' => 0,
                'feedback' => 'Error: ' . $e->getMessage(),
            ];
        }
    }

    // Get next question based on student's level
    protected function getNextQuestion($userId, $topicGuid, $language, $level)
    {
        $ongoingQuestion = AnswerUser::where('user_id', $userId)->where('current_level', $level)->where('is_correct', 0)->join('questions', 'answer_user.question_guid', '=', 'questions.guid')->where('questions.topic_guid', $topicGuid)->where('questions.language', $language)->where('questions.category', $level)->orderBy('answer_user.created_at', 'desc')->first();

        if ($ongoingQuestion) {
            // Return the same question they were working on
            return Question::find($ongoingQuestion->question_guid);
        }

        $answeredCorrectlyGuids = AnswerUser::where('user_id', $userId)->where('is_correct', 1)->pluck('question_guid')->toArray();

        $availableQuestions = Question::where('topic_guid', $topicGuid)->where('language', $language)->where('category', $level)->whereNotIn('guid', $answeredCorrectlyGuids)->get();

        if ($availableQuestions->isEmpty()) {
            $availableQuestions = Question::where('topic_guid', $topicGuid)->where('language', $language)->where('category', $level)->get();
        }

        if ($availableQuestions->isNotEmpty()) {
            return $availableQuestions->random();
        }

        return null;
    }

    

    public function resetHistories(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|string',
            'topic_guid' => 'required|string',
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
            $userId = $request->user_id;
            $topicGuid = $request->topic_guid;

            $questionGuids = Question::where('topic_guid', $topicGuid)->pluck('guid')->toArray();

            if (empty($questionGuids)) {
                return response()->json(
                    [
                        'status' => false,
                        'message' => 'No questions found for this topic',
                    ],
                    404,
                );
            }

            DB::beginTransaction();

            $userAnswerGuids = AnswerUser::where('user_id', $userId)->whereIn('question_guid', $questionGuids)->pluck('guid')->toArray();

            if (!empty($userAnswerGuids)) {
                PlagiarismDetail::whereIn('plagiarism_guid', function ($query) use ($userAnswerGuids) {
                    $query->select('guid')->from('plagiarism')->whereIn('user_answer_guid', $userAnswerGuids);
                })->delete();

                Plagiarism::whereIn('user_answer_guid', $userAnswerGuids)->delete();
            }

            $deletedCount = AnswerUser::where('user_id', $userId)->whereIn('question_guid', $questionGuids)->delete();

            DB::commit();

            return response()->json(
                [
                    'status' => true,
                    'message' => 'User histories reset successfully',
                    'data' => [
                        'deleted_answers' => $deletedCount,
                        'user_id' => $userId,
                        'topic_guid' => $topicGuid,
                    ],
                ],
                200,
            );

            //     $hasAnswers = AnswerUser::where('user_id', $userId)
            //     ->whereHas('question', function($query) use ($topicGuid) {
            //         $query->where('topic_guid', $topicGuid);
            //     })
            //     ->exists();

            // if (!$hasAnswers) {
            //     return response()->json([
            //         'status' => false,
            //         'message' => 'No answer history found for this user and topic',
            //     ], 404);
            // }

            // return response()->json([
            //     'status' => true,
            //     'message' => 'User level reset successfully. User will start from remembering level on next question.',
            //     'data' => [
            //         'user_id' => $userId,
            //         'topic_guid' => $topicGuid,
            //         'reset_level' => 'remembering'
            //     ]
            // ], 200);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json(
                [
                    'status' => false,
                    'message' => 'Failed to reset user histories: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }
}

