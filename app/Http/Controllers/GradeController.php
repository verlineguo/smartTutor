<?php

namespace App\Http\Controllers;

use App\Models\AnswerLLM;
use App\Models\AnswerUser;
use App\Models\Plagiarism;
use App\Models\Question;
use App\Models\Role;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserCourse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class GradeController extends Controller
{
    public function getStudentsByTopic($code, $guid)
    {
        try {
            // Get student role GUID
            $roleGuid = Role::where('role_name', '=', 'student')->value('guid');

            if (!$roleGuid) {
                return response()->json(['message' => 'Student role not found'], 404);
            }

            // Get all students in the course
            $students = UserCourse::where('course_code', $code)
                ->whereHas('user', function ($query) use ($roleGuid) {
                    $query->where('role_guid', $roleGuid);
                })
                ->with(['user:id,name,username,email,role_guid'])
                ->get();

            $totalLevels = Question::where('topic_guid', $guid)->pluck('category')->unique()->count();

            // Process each student to get their progress information
            $result = [];
            foreach ($students as $userCourse) {
                $user = $userCourse->user;

                $answeredLevels = AnswerUser::where('user_id', $user->id)
                    ->where('is_correct', true)
                    ->whereHas('question', function ($query) use ($guid) {
                        $query->where('topic_guid', $guid);
                    })
                    ->select('current_level')
                    ->distinct()
                    ->count();

                // Calculate average score
                $averageScore = AnswerUser::where('user_id', $user->id)
                    ->whereHas('question', function ($query) use ($guid) {
                        $query->where('topic_guid', $guid);
                    })
                    ->avg('evaluation_scores');

                // Get current level (latest one based on created_at)
                $currentLevel = AnswerUser::where('user_id', $user->id)
                    ->whereHas('question', function ($query) use ($guid) {
                        $query->where('topic_guid', $guid);
                    })
                    ->orderByDesc('created_at')
                    ->value('current_level');

                // Format the data
                $result[] = [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'progress' => "$answeredLevels/$totalLevels",
                    'average_score' => $averageScore ? round($averageScore, 1) : null,
                    'current_level' => $currentLevel,
                ];
            }

            return ResponseController::getResponse(['data' => $result], 200, 'Data Mahasiswa diambil.');
        } catch (\Exception $e) {
            Log::error('Error getting students by topic: ' . $e->getMessage());
            return response()->json(['message' => 'Error getting students data', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get detailed answers for a specific student in a topic
     *
     * @param string $code
     * @param string $guid
     * @param string $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStudentAnswerDetails($code, $guid, $userId)
    {
        try {
            // Validate the existence of topic and course
            $topic = Topic::where('guid', $guid)->where('course_code', $code)->first();

            if (!$topic) {
                return response()->json(['message' => 'Topic not found'], 404);
            }

            // Validate user is in the course
            $userCourse = UserCourse::where('course_code', $code)->where('user_id', $userId)->first();

            if (!$userCourse) {
                return response()->json(['message' => 'Student not enrolled in this course'], 404);
            }

            // Get user information
            $user = User::find($userId);
            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }

            // Get answers grouped by Bloom's taxonomy level
            $levels = ['remembering', 'understanding', 'applying', 'analyzing'];
            $result = [];

            foreach ($levels as $level) {
                // Get all questions for this level in this topic
                $questions = Question::where('topic_guid', $guid)->where('category', $level)->get();

                $questionIds = $questions->pluck('guid')->toArray();

                if (empty($questionIds)) {
                    continue;
                }

                $levelData = [
                    'level' => $level,
                    'level_title' => ucfirst($level),
                    'questions' => [],
                ];

                foreach ($questions as $question) {
                    $answers = AnswerUser::where('question_guid', $question->guid)->orderByDesc('created_at')->get();

                    if ($answers->count() > 0) {
                        $latestAnswer = $answers->first();
                        $plagiarismCheck = null;

                        // Get plagiarism check if exists
                        $plagiarism = Plagiarism::whereHas('userAnswer', function ($query) use ($latestAnswer) {
                            $query->where('guid', $latestAnswer->guid);
                        })->first();

                        if ($plagiarism) {
                            $plagiarismCheck = [
                                'cosine_similarity' => $plagiarism->cosine_similarity,
                                'jaccard_similarity' => $plagiarism->jaccard_similarity,
                                'bert_score' => $plagiarism->bert_score,
                                'detected_strategies' => json_decode($plagiarism->detected_strategies),
                            ];
                        }

                        $levelData['questions'][] = [
                            'question_guid' => $question->guid,
                            'question' => $question->question_fix,
                            'answers' => $answers->map(function ($answer, $index) use ($answers) {
                                $total = $answers->count();
                                return [
                                    'answer_guid' => $answer->guid,
                                    'answer_text' => $answer->answer,
                                    'attempt_number' => $total - $index, // Mulai dari max ke kecil
                                    'is_correct' => $answer->is_correct,
                                    'evaluation_score' => $answer->evaluation_scores,
                                    'lecturer_score' => $answer->lecturer_score,
                                    'created_at' => $answer->created_at->format('Y-m-d H:i:s'),
                                ];
                            }),
                            'expected_answer' => $question->answer_fix,
                            'plagiarism_check' => $plagiarismCheck,
                        ];
                    }
                }

                if (!empty($levelData['questions'])) {
                    $result[] = $levelData;
                }
            }

            // Add user profile information
            $profileData = [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
            ];

            // Calculate level progress statistics
            $levelStats = [];
            foreach ($levels as $level) {
                $questions = Question::where('topic_guid', $guid)->where('category', $level)->count();

                $correctAnswers = AnswerUser::whereHas('question', function ($query) use ($guid, $level) {
                    $query->where('topic_guid', $guid)->where('category', $level);
                })
                    ->where('user_id', $userId)
                    ->where('is_correct', true)
                    ->distinct('question_guid')
                    ->count('question_guid');

                $levelStats[$level] = [
                    'total_questions' => $questions,
                    'correct_answers' => $correctAnswers,
                    'percentage' => $questions > 0 ? round(($correctAnswers / $questions) * 100) : 0,
                ];
            }

            // Determine highest achieved level
            $highestLevel = 'None';
            foreach (array_reverse($levels) as $level) {
                $hasCorrectAnswer = AnswerUser::whereHas('question', function ($query) use ($guid, $level) {
                    $query->where('topic_guid', $guid)->where('category', $level);
                })
                    ->where('user_id', $userId)
                    ->where('is_correct', true)
                    ->exists();

                if ($hasCorrectAnswer) {
                    $highestLevel = ucfirst($level);
                    break;
                }
            }

            return ResponseController::getResponse(
                [
                    'profile' => $profileData,
                    'level_stats' => $levelStats,
                    'highest_level' => $highestLevel,
                    'data' => $result,
                ],
                200,
                'Data Mahasiswa berhasil diambil',
            );
        } catch (\Exception $e) {
            Log::error('Error getting student answer details: ' . $e->getMessage());
            return response()->json(['message' => 'Error retrieving answer details', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update lecturer score for a student answer
     */
    public function updateLecturerScore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'answer_guid' => 'required|string|max:36',
            'lecturer_score' => 'required|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        try {
            $answer = AnswerUser::where('guid', $request->answer_guid)->first();

            if (!$answer) {
                return response()->json(['message' => 'Answer not found'], 404);
            }

            $answer->lecturer_score = $request->lecturer_score;
            $answer->save();

            return response()->json(['message' => 'Score updated successfully', 'data' => $answer], 200);
        } catch (\Exception $e) {
            Log::error('Error updating lecturer score: ' . $e->getMessage());
            return response()->json(['message' => 'Error updating score', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Reset student progress for a topic
     */
    public function resetHistories(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|string|max:50',
            'topic_guid' => 'required|string|max:36',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        try {
            // Delete all user answers for this topic
            $deleted = AnswerUser::where('user_id', $request->user_id)
                ->whereHas('question', function ($query) use ($request) {
                    $query->where('topic_guid', $request->topic_guid);
                })
                ->delete();

            return response()->json(
                [
                    'message' => 'Student progress reset successfully',
                    'records_deleted' => $deleted,
                ],
                200,
            );
        } catch (\Exception $e) {
            Log::error('Error resetting student progress: ' . $e->getMessage());
            return response()->json(['message' => 'Error resetting progress', 'error' => $e->getMessage()], 500);
        }
    }

    public function getEvaluationStats($code, $guid, $userId)
    {
        try {
            // Validate the existence of topic and course
            $topic = Topic::where('guid', $guid)->where('course_code', $code)->first();

            if (!$topic) {
                return response()->json(['message' => 'Topic not found'], 404);
            }

            // Validate user exists
            $user = User::find($userId);
            if (!$user) {
                return response()->json(['message' => 'Student not found'], 404);
            }

            // Get all levels data
            $levels = ['remembering', 'understanding', 'applying', 'analyzing'];
            $levelsData = [];
            $overallCorrectAnswers = 0;
            $overallTotalQuestions = 0;
            $highestAchievedLevel = null;

            // Get plagiarism data for this user
            $plagiarismData = Plagiarism::whereHas('userAnswer', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
                ->with(['details', 'userAnswer'])
                ->where(function ($query) {
                    $query->where('cosine_similarity', '>=', 0.55)->orWhere('jaccard_similarity', '>=', 0.55)->orWhere('bert_score', '>=', 0.65)->orWhere('levenshtein_similarity', '>=', 0.55)->orWhere('ngram_similarity', '>=', 0.5);
                })
                ->get();

            // Process data for each learning level
            foreach ($levels as $level) {
                // Get all questions for this level in this topic
                $questions = Question::where('topic_guid', $guid)->where('category', $level)->get();

                $questionIds = $questions->pluck('guid')->toArray();
                $totalQuestions = count($questionIds);
                $overallTotalQuestions += $totalQuestions;

                if (empty($questionIds)) {
                    continue;
                }

                // Get user answers for these questions
                $userAnswers = AnswerUser::whereIn('question_guid', $questionIds)->where('user_id', $userId)->get();

                // Get LLM answers for comparison
                $llmAnswers = AnswerLLM::whereIn('question_guid', $questionIds)->get()->groupBy('question_guid');

                // Calculate metrics
                $attemptedQuestionIds = $userAnswers->pluck('question_guid')->unique()->toArray();
                $attemptedQuestions = count($attemptedQuestionIds);

                $passedQuestionIds = $userAnswers->where('is_correct', true)->pluck('question_guid')->unique()->toArray();
                $passedQuestions = count($passedQuestionIds);

                $overallCorrectAnswers += $passedQuestions;

                // Average score
                $avgScore = $userAnswers->avg('evaluation_scores');

                // Average lecturer score (if available)
                $lecturerScored = $userAnswers->whereNotNull('lecturer_score');
                $avgLecturerScore = $lecturerScored->count() > 0 ? $lecturerScored->avg('lecturer_score') : null;

                // Calculate attempts per question
                $attemptsPerQuestion = [];
                foreach ($attemptedQuestionIds as $qId) {
                    $attemptsPerQuestion[$qId] = $userAnswers->where('question_guid', $qId)->count();
                }

                $avgAttempts = count($attemptsPerQuestion) > 0 ? array_sum($attemptsPerQuestion) / count($attemptsPerQuestion) : 0;

                // Check if this is the highest achieved level with at least 40% completion
                if ($passedQuestions > 0 && $passedQuestions / $totalQuestions >= 0.4) {
                    $highestAchievedLevel = $level;
                }

                // Question-specific performance
                $questionPerformance = [];
                foreach ($questions as $question) {
                    $userAnswersForQ = $userAnswers->where('question_guid', $question->guid);
                    $attempts = $userAnswersForQ->count();
                    $bestScore = $userAnswersForQ->max('evaluation_scores');
                    $isCorrect = $userAnswersForQ->where('is_correct', true)->count() > 0;

                    // Get plagiarism score if available
                    $plagiarismFlag = false;
                    $plagiarismScore = null;
                    $detectedStrategies = [];

                    foreach ($plagiarismData as $plagiarism) {
                        if ($plagiarism->userAnswer && $plagiarism->userAnswer->question_guid === $question->guid) {
                            $plagiarismFlag = true;
                            $plagiarismScore = [
                                'cosine' => $plagiarism->cosine_similarity,
                                'jaccard' => $plagiarism->jaccard_similarity,
                                'bert' => $plagiarism->bert_score,
                                'levenshtein' => $plagiarism->levenshtein_similarity,
                                'ngram' => $plagiarism->ngram_similarity,
                            ];

                            if ($plagiarism->detected_strategies) {
                                $detectedStrategies = json_decode($plagiarism->detected_strategies, true);
                            }
                            break;
                        }
                    }

                    $questionPerformance[] = [
                        'question_id' => $question->guid,
                        'question_text' => $question->question,
                        'attempts' => $attempts,
                        'best_score' => $bestScore,
                        'is_correct' => $isCorrect,
                        'has_plagiarism' => $plagiarismFlag,
                        'plagiarism_scores' => $plagiarismScore,
                        'plagiarism_strategies' => $detectedStrategies,
                    ];
                }

                // Store all level data
                $levelsData[$level] = [
                    'level_name' => ucfirst($level),
                    'level_description' => $this->getLevelDescription($level),
                    'total_questions' => $totalQuestions,
                    'attempted_questions' => $attemptedQuestions,
                    'passed_questions' => $passedQuestions,
                    'progress_percentage' => $totalQuestions > 0 ? round(($passedQuestions / $totalQuestions) * 100) : 0,
                    'avg_score' => $avgScore ? round($avgScore, 1) : null,
                    'avg_lecturer_score' => $avgLecturerScore ? round($avgLecturerScore, 1) : null,
                    'avg_attempts' => $avgAttempts ? round($avgAttempts, 1) : 0,
                    'question_performance' => $questionPerformance,
                    'improvement_suggestions' => $this->getImprovementSuggestions($level, $passedQuestions, $avgAttempts),
                ];
            }

            // Calculate plagiarism summary
            $plagiarismSummary = [
                'total_detected' => $plagiarismData->count(),
                'detected_by_level' => [],
                'most_common_strategies' => $this->getMostCommonPlagiarismStrategies($plagiarismData),
                'details' => $plagiarismData->map(function ($plagiarism) {
                    return [
                        'similarity_scores' => [
                            'cosine' => $plagiarism->cosine_similarity,
                            'jaccard' => $plagiarism->jaccard_similarity,
                            'bert' => $plagiarism->bert_score,
                            'levenshtein' => $plagiarism->levenshtein_similarity,
                            'ngram' => $plagiarism->ngram_similarity,
                        ],
                        'detected_strategies' => json_decode($plagiarism->detected_strategies ?? '[]', true),
                        'weighted_score' => $plagiarism->details->pluck('weighted_score')->avg(),
                    ];
                }),
            ];

            // Count plagiarism instances by level
            foreach ($plagiarismData as $plagiarism) {
                if ($plagiarism->userAnswer && $plagiarism->userAnswer->question_guid) {
                    $question = Question::find($plagiarism->userAnswer->question_guid);
                    if ($question) {
                        $level = $question->category;
                        if (!isset($plagiarismSummary['detected_by_level'][$level])) {
                            $plagiarismSummary['detected_by_level'][$level] = 0;
                        }
                        $plagiarismSummary['detected_by_level'][$level]++;
                    }
                }
            }

            // Overall progress data
            $overallProgress = [
                'total_questions' => $overallTotalQuestions,
                'correct_answers' => $overallCorrectAnswers,
                'progress_percentage' => $overallTotalQuestions > 0 ? round(($overallCorrectAnswers / $overallTotalQuestions) * 100) : 0,
                'highest_level' => $highestAchievedLevel ? ucfirst($highestAchievedLevel) : 'None',
                'learning_path_status' => $this->getLearningPathStatus($user->id, $guid),
            ];

            // Student profile data
            $profile = [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
            ];

            return ResponseController::getResponse(
                [
                    'profile' => $profile,
                    'levels' => $levelsData,
                    'overall_progress' => $overallProgress,
                    'plagiarism_summary' => $plagiarismSummary,
                ],
                200,
                'Answer generated successfully.',
            );
        } catch (\Exception $e) {
            Log::error('Error getting student evaluation statistics: ' . $e->getMessage());
            return response()->json(
                [
                    'message' => 'Error retrieving student evaluation statistics',
                    'error' => $e->getMessage(),
                ],
                500,
            );
        }
    }

    private function getLearningPathStatus($userId, $topicGuid)
    {
        // Define the expected learning path and mapping
        $path = ['remembering', 'understanding', 'applying', 'analyzing'];
        $levelMapping = [
            'none' => 'Not Started',
            'remembering' => 'Remembering Level',
            'understanding' => 'Understanding Level',
            'applying' => 'Applying Level',
            'analyzing' => 'Analyzing Level',
        ];

        // Determine the highest level achieved
        $highestLevel = 'none';
        foreach (array_reverse($path) as $level) {
            $hasCorrect = AnswerUser::where('user_id', $userId)
                ->where('is_correct', true)
                ->whereHas('question', function ($query) use ($topicGuid, $level) {
                    $query->where('topic_guid', $topicGuid)->where('category', $level);
                })
                ->exists();
            if ($hasCorrect) {
                $highestLevel = $level;
                break;
            }
        }

        return $levelMapping[$highestLevel] ?? 'In Progress';
    }

    private function getLevelDescription($level)
    {
        $descriptions = [
            'remembering' => 'Mahasiswa mampu mengingat fakta, istilah, konsep dasar, dan jawaban.',
            'understanding' => 'Mahasiswa menunjukkan pemahaman terhadap fakta dan ide dengan mengorganisir, membandingkan, menafsirkan, serta menyampaikan ide pokok.',
            'applying' => 'Mahasiswa mampu menerapkan pengetahuan, fakta, teknik, dan aturan yang telah dipelajari untuk menyelesaikan permasalahan.',
            'analyzing' => 'Mahasiswa dapat menganalisis informasi dengan memecahnya menjadi bagian-bagian, serta mengidentifikasi motif, penyebab, dan hubungan antar konsep.',
        ];

        return $descriptions[$level] ?? '';
    }

    private function getImprovementSuggestions($level, $isCorrect, $attempts)
    {
        $suggestions = [];

        // Berdasarkan hasil jawaban dan percobaan
        if (!$isCorrect) {
            if ($attempts >= 3) {
                $suggestions[] = 'Mahasiswa belum berhasil menjawab meskipun telah mencoba beberapa kali. Perlu mengulang materi atau mendapat penjelasan tambahan.';
            } else {
                $suggestions[] = 'Jawaban belum benar. Dorong mahasiswa untuk mengulas kembali konsep inti dari materi ini.';
            }
        } else {
            if ($attempts > 2) {
                $suggestions[] = 'Jawaban benar, namun memerlukan beberapa kali percobaan. Ajak mahasiswa merefleksikan kesalahan awal.';
            } else {
                $suggestions[] = 'Mahasiswa berhasil menjawab dengan baik. Dapat melanjutkan ke level berikutnya.';
            }
        }

        // Berdasarkan level kognitif
        $suggestions = array_merge($suggestions, $this->suggestByCognitiveLevelSingle($level, $isCorrect, $attempts));

        return $suggestions;
    }

    private function suggestByCognitiveLevelSingle($level, $isCorrect, $attempts)
    {
        $suggestions = [];

        switch ($level) {
            case 'remembering':
                if (!$isCorrect) {
                    $suggestions[] = 'Gunakan teknik seperti flashcard atau pengulangan untuk mengingat konsep.';
                }
                break;

            case 'understanding':
                if (!$isCorrect) {
                    $suggestions[] = 'Minta mahasiswa menjelaskan ulang konsep dengan bahasanya sendiri.';
                }
                break;

            case 'applying':
                if (!$isCorrect) {
                    $suggestions[] = 'Latihan dengan studi kasus serupa agar terbiasa menerapkan konsep.';
                }
                break;

            case 'analyzing':
                if (!$isCorrect) {
                    $suggestions[] = 'Dorong mahasiswa untuk memecah permasalahan dan melihat hubungan antar konsep.';
                }
                break;

            case 'evaluating':
                if (!$isCorrect) {
                    $suggestions[] = 'Latih kemampuan menilai solusi lain berdasarkan argumen logis.';
                }
                break;

            case 'creating':
                if (!$isCorrect) {
                    $suggestions[] = 'Beri ruang untuk mencoba membuat solusi baru dari gabungan konsep.';
                }
                break;
        }

        return $suggestions;
    }

    private function getMostCommonPlagiarismStrategies($plagiarismData)
    {
        $strategies = [];

        foreach ($plagiarismData as $plagiarism) {
            if ($plagiarism->detected_strategies) {
                $detected = json_decode($plagiarism->detected_strategies, true);
                if (is_array($detected)) {
                    foreach ($detected as $strategy) {
                        if (!isset($strategies[$strategy])) {
                            $strategies[$strategy] = 0;
                        }
                        $strategies[$strategy]++;
                    }
                }
            }
        }

        arsort($strategies);

        return $strategies;
    }
}
