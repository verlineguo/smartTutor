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

            return response()->json(['data' => $result], 200);
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

            return response()->json(
                [
                    'profile' => $profileData,
                    'level_stats' => $levelStats,
                    'highest_level' => $highestLevel,
                    'data' => $result,
                ],
                200,
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

    /**
     * Get detailed evaluation statistics for a specific student in a topic
     *
     * @param string $code Course code
     * @param string $guid Topic GUID
     * @param string $userId User ID
     * @return \Illuminate\Http\JsonResponse
     */

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
            $levelProgression = [];

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
                $userAnswers = AnswerUser::whereIn('question_guid', $questionIds)->where('user_id', $userId)->orderBy('created_at', 'asc')->get();

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

                // Calculate attempts required to level up
                $attemptsToLevelUp = $this->calculateAttemptsToLevelUp($userAnswers, $passedQuestionIds);

                // Check if this is the highest achieved level with at least one correct answer
                if ($passedQuestions > 0) {
                    $highestAchievedLevel = $level;
                }

                // Check when the student reached this level
                $firstCorrectAnswer = $userAnswers->where('is_correct', true)->first();
                $reachedAt = $firstCorrectAnswer ? $firstCorrectAnswer->created_at : null;

                // Track progression through levels
                if ($reachedAt) {
                    $levelProgression[$level] = [
                        'reached_at' => $reachedAt,
                        'attempts_to_reach' => $attemptsToLevelUp,
                    ];
                }

                // Plagiarism data for this level
                $levelPlagiarismCount = $plagiarismData
                    ->filter(function ($item) use ($questionIds) {
                        return in_array($item->userAnswer->question_guid, $questionIds);
                    })
                    ->count();

                // Generate improvement suggestions based on performance
                $improvementSuggestions = $this->generateImprovementSuggestions($level, $avgScore, $avgAttempts, $passedQuestions, $totalQuestions);

                // Question performance details (limit to just key metrics, not all questions)
                $questionPerformance = $this->getQuestionPerformanceSummary($userAnswers, $questions, $plagiarismData);

                // Add level data to response
                $levelsData[$level] = [
                    'level_name' => ucfirst($level),
                    'level_description' => $this->getLevelDescription($level),
                    'total_questions' => $totalQuestions,
                    'attempted_questions' => $attemptedQuestions,
                    'passed_questions' => $passedQuestions,
                    'progress_percentage' => $totalQuestions > 0 ? round(($passedQuestions / $totalQuestions) * 100) : 0,
                    'avg_score' => round($avgScore, 1),
                    'avg_lecturer_score' => $avgLecturerScore ? round($avgLecturerScore, 1) : null,
                    'avg_attempts' => round($avgAttempts, 1),
                    'attempts_to_level_up' => $attemptsToLevelUp,
                    'reached_at' => $reachedAt,
                    'plagiarism_count' => $levelPlagiarismCount,
                    'improvement_suggestions' => $improvementSuggestions,
                    'question_performance' => $questionPerformance,
                ];
            }

            // Calculate level progression times and attempts
            $progressionAnalysis = $this->analyzeLevelProgression($levelProgression);

            // Calculate plagiarism summary
            $plagiarismSummary = $this->calculatePlagiarismSummary($plagiarismData, $levelsData);

            // Generate temporal data (learning over time)
            $temporalData = $this->generateTemporalData($userId, $guid, $levels);

            // Overall progress data - modified to focus on level progression
            $overallProgress = [
                'total_questions' => $overallTotalQuestions,
                'correct_answers' => $overallCorrectAnswers,
                'progress_percentage' => $overallTotalQuestions > 0 ? round(($overallCorrectAnswers / $overallTotalQuestions) * 100) : 0,
                'highest_level' => $highestAchievedLevel ? ucfirst($highestAchievedLevel) : 'None',
                'learning_path_status' => $this->getLearningPathStatus($highestAchievedLevel),
                'level_progression' => $progressionAnalysis,
            ];

            // Student profile data
            $profile = [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
            ];

            // Generate instructor-focused recommendations
            $recommendations = $this->generateInstructorRecommendations($levelsData, $plagiarismSummary, $progressionAnalysis);

            return response()->json(
                [
                    'profile' => $profile,
                    'levels' => $levelsData,
                    'overall_progress' => $overallProgress,
                    'temporal_progression' => $temporalData,
                    'plagiarism_summary' => $plagiarismSummary,
                    'recommendations' => $recommendations,
                ],
                200,
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

    /**
     * Calculate the number of attempts required to level up
     */
    private function calculateAttemptsToLevelUp($userAnswers, $passedQuestionIds)
    {
        if (empty($passedQuestionIds)) {
            return null; // Level not reached yet
        }

        // Get the first correct answer for each passed question
        $firstCorrectAttempts = [];
        foreach ($passedQuestionIds as $qId) {
            $attempts = $userAnswers->where('question_guid', $qId)->sortBy('created_at')->values();
            $correctAttemptIndex = $attempts->search(function ($answer) {
                return $answer->is_correct;
            });

            if ($correctAttemptIndex !== false) {
                $firstCorrectAttempts[$qId] = $correctAttemptIndex + 1; // +1 because index is zero-based
            }
        }

        // Calculate average attempts to get a correct answer
        return !empty($firstCorrectAttempts) ? round(array_sum($firstCorrectAttempts) / count($firstCorrectAttempts), 1) : null;
    }

    /**
     * Analyze how the student progressed through learning levels
     */
    private function analyzeLevelProgression($levelProgression)
    {
        if (empty($levelProgression)) {
            return ['reached_levels' => 0, 'avg_time_between_levels' => null];
        }

        $reachedLevels = count($levelProgression);
        $timesBetweenLevels = [];
        $avgAttemptsPerLevel = array_filter(array_column($levelProgression, 'attempts_to_reach'));

        // Sort levels by time reached
        $sortedProgression = collect($levelProgression)->sortBy('reached_at')->values()->toArray();

        // Calculate times between levels
        for ($i = 1; $i < count($sortedProgression); $i++) {
            $previousTime = new \DateTime($sortedProgression[$i - 1]['reached_at']);
            $currentTime = new \DateTime($sortedProgression[$i]['reached_at']);
            $interval = $previousTime->diff($currentTime);
            $timesBetweenLevels[] = $interval->days * 24 + $interval->h; // hours
        }

        $avgTime = !empty($timesBetweenLevels) ? array_sum($timesBetweenLevels) / count($timesBetweenLevels) : null;
        $avgAttempts = !empty($avgAttemptsPerLevel) ? array_sum($avgAttemptsPerLevel) / count($avgAttemptsPerLevel) : null;

        return [
            'reached_levels' => $reachedLevels,
            'avg_time_between_levels' => $avgTime ? round($avgTime, 1) : null, // hours
            'avg_attempts_per_level' => $avgAttempts ? round($avgAttempts, 1) : null,
            'progression_details' => $sortedProgression,
        ];
    }

    /**
     * Generate a summary of question performance instead of detailed per-question data
     */
    private function getQuestionPerformanceSummary($userAnswers, $questions, $plagiarismData)
    {
        $summary = [];

        foreach ($questions as $question) {
            $answers = $userAnswers->where('question_guid', $question->guid);

            if ($answers->isEmpty()) {
                continue; // Skip questions with no attempts
            }

            $attempts = $answers->count();
            $bestScore = $answers->max('evaluation_scores');
            $isCorrect = $answers->contains('is_correct', true);

            // Check for plagiarism
            $hasPlagiarism = false;
            $plagiarismScores = null;

            foreach ($answers as $answer) {
                $plagiarismRecord = $plagiarismData->where('user_answer_guid', $answer->guid)->first();
                if ($plagiarismRecord) {
                    $hasPlagiarism = true;
                    $plagiarismScores = [
                        'cosine' => $plagiarismRecord->cosine_similarity,
                        'jaccard' => $plagiarismRecord->jaccard_similarity,
                        'bert' => $plagiarismRecord->bert_score,
                        'levenshtein' => $plagiarismRecord->levenshtein_similarity,
                        'ngram' => $plagiarismRecord->ngram_similarity,
                    ];
                    break;
                }
            }

            $summary[] = [
                'question_text' => $question->question,
                'attempts' => $attempts,
                'best_score' => $bestScore,
                'is_correct' => $isCorrect,
                'has_plagiarism' => $hasPlagiarism,
                'plagiarism_scores' => $plagiarismScores,
            ];
        }

        return $summary;
    }

    /**
     * Generate improvement suggestions based on performance
     */
    private function generateImprovementSuggestions($level, $avgScore, $avgAttempts, $passedQuestions, $totalQuestions)
    {
        $suggestions = [];

        // Level hasn't been attempted or there are no questions for this level
        if ($totalQuestions == 0) {
            return ['No questions available for this level.'];
        }

        // Level hasn't been started
        if ($passedQuestions == 0) {
            $suggestions[] = "Student hasn't successfully completed any questions in this level yet.";

            if ($level == 'remembering') {
                $suggestions[] = 'Consider providing foundational resources to help student get started.';
            } else {
                $suggestions[] = "Review student's progress in previous levels to ensure readiness.";
            }

            return $suggestions;
        }

        // Level completion rate
        $completionRate = $passedQuestions / $totalQuestions;

        // Analyze based on level and metrics
        switch ($level) {
            case 'remembering':
                if ($avgAttempts > 2) {
                    $suggestions[] = 'Student requires multiple attempts at basic recall questions. Consider revising foundational concepts.';
                }
                if ($avgScore < 7) {
                    $suggestions[] = 'Low score on remembering tasks suggests gaps in foundational knowledge.';
                }
                break;

            case 'understanding':
                if ($avgAttempts > 3) {
                    $suggestions[] = 'Student requires multiple attempts to demonstrate understanding. May benefit from conceptual clarification.';
                }
                if ($avgScore < 7) {
                    $suggestions[] = 'Student may need help connecting concepts and establishing relationships between ideas.';
                }
                break;

            case 'applying':
                if ($avgAttempts > 3) {
                    $suggestions[] = 'Student struggles to apply concepts in practice. Consider providing more hands-on examples.';
                }
                if ($avgScore < 7) {
                    $suggestions[] = 'Difficulty applying knowledge suggests a gap between theoretical understanding and practical implementation.';
                }
                break;

            case 'analyzing':
                if ($avgAttempts > 3) {
                    $suggestions[] = 'Student requires multiple attempts at analysis tasks. May benefit from guided analytical practice.';
                }
                if ($avgScore < 7) {
                    $suggestions[] = 'Low analytical scores suggest student may need more practice with complex problem decomposition.';
                }
                break;
        }

        // Level progression
        if ($completionRate < 0.5 && $passedQuestions > 0) {
            $suggestions[] = 'Student has started but not completed this level. Check for specific obstacles.';
        }

        return $suggestions;
    }

    /**
     * Get description for each learning level
     */
    private function getLevelDescription($level)
    {
        switch ($level) {
            case 'remembering':
                return 'Retrieval of relevant knowledge from long-term memory - recognition and recall of facts';
            case 'understanding':
                return 'Making meaning from educational messages through interpreting, exemplifying, classifying, summarizing, inferring, comparing and explaining';
            case 'applying':
                return 'Using procedures to perform exercises or solve problems - implementation and execution';
            case 'analyzing':
                return 'Breaking material into constituent parts and detecting how parts relate to one another and to an overall structure';
            default:
                return '';
        }
    }

    /**
     * Get learning path status based on highest achieved level
     */
    private function getLearningPathStatus($highestLevel)
    {
        if (!$highestLevel) {
            return 'Not Started';
        }

        switch ($highestLevel) {
            case 'remembering':
                return 'Foundation Level';
            case 'understanding':
                return 'Intermediate Level';
            case 'applying':
                return 'Advanced Level';
            case 'analyzing':
                return 'Expert Level';
            default:
                return 'Not Started';
        }
    }

    /**
     * Calculate plagiarism summary for instructor view
     */
    private function calculatePlagiarismSummary($plagiarismData, $levelsData)
    {
        if ($plagiarismData->isEmpty()) {
            return [
                'total_detected' => 0,
                'detected_by_level' => [],
                'most_common_strategies' => [],
                'average_similarity_scores' => [
                    'cosine' => 0,
                    'jaccard' => 0,
                    'bert' => 0,
                    'levenshtein' => 0,
                    'ngram' => 0,
                ],
            ];
        }

        // Count by level
        $detectedByLevel = [];
        foreach ($levelsData as $level => $data) {
            $detectedByLevel[$level] = $data['plagiarism_count'] ?? 0;
        }

        // Extract and count strategies
        $strategies = [];
        foreach ($plagiarismData as $item) {
            if (!empty($item->detected_strategies)) {
                $itemStrategies = json_decode($item->detected_strategies, true);
                foreach ($itemStrategies as $strategy) {
                    if (!isset($strategies[$strategy])) {
                        $strategies[$strategy] = 0;
                    }
                    $strategies[$strategy]++;
                }
            }
        }

        // Sort strategies by frequency
        arsort($strategies);

        // Calculate average similarity scores
        $scores = [
            'cosine' => $plagiarismData->avg('cosine_similarity'),
            'jaccard' => $plagiarismData->avg('jaccard_similarity'),
            'bert' => $plagiarismData->avg('bert_score'),
            'levenshtein' => $plagiarismData->avg('levenshtein_similarity'),
            'ngram' => $plagiarismData->avg('ngram_similarity'),
        ];

        return [
            'total_detected' => $plagiarismData->count(),
            'detected_by_level' => $detectedByLevel,
            'most_common_strategies' => $strategies,
            'average_similarity_scores' => $scores,
        ];
    }

    /**
     * Generate temporal data for learning progress over time
     */
    private function generateTemporalData($userId, $topicGuid, $levels)
    {
        // Get all user answers for this topic
        $answers = AnswerUser::whereHas('question', function ($query) use ($topicGuid) {
            $query->where('topic_guid', $topicGuid);
        })
            ->where('user_id', $userId)
            ->orderBy('created_at', 'asc')
            ->get();

        if ($answers->isEmpty()) {
            return null;
        }

        // Group answers by date
        $answersByDate = $answers->groupBy(function ($answer) {
            return $answer->created_at->format('Y-m-d');
        });

        // Initialize data structure
        $dailyProgress = [];
        $cumulativeByLevel = array_fill_keys($levels, ['attempts' => 0, 'correct' => 0, 'cumulative_correct' => 0]);

        foreach ($answersByDate as $date => $dateAnswers) {
            // Reset daily counters
            $byLevel = [];
            foreach ($levels as $level) {
                $byLevel[$level] = [
                    'attempts' => 0,
                    'correct' => 0,
                    'cumulative_correct' => $cumulativeByLevel[$level]['cumulative_correct'],
                ];
            }

            // Count attempts and correct answers by level
            foreach ($dateAnswers as $answer) {
                $question = Question::find($answer->question_guid);
                if (!$question) {
                    continue;
                }

                $level = $question->category;
                if (!isset($byLevel[$level])) {
                    continue;
                }

                $byLevel[$level]['attempts']++;

                if ($answer->is_correct) {
                    $byLevel[$level]['correct']++;
                    $byLevel[$level]['cumulative_correct']++;
                    $cumulativeByLevel[$level]['cumulative_correct']++;
                }
            }

            $dailyProgress[] = [
                'date' => $date,
                'by_level' => $byLevel,
                'total_attempts' => $dateAnswers->count(),
                'total_correct' => $dateAnswers->where('is_correct', true)->count(),
            ];
        }

        // Calculate engagement and persistence metrics
        $engagementDays = count($dailyProgress);
        $totalDaysSpan = (new \DateTime(end($dailyProgress)['date']))->diff(new \DateTime($dailyProgress[0]['date']))->days + 1;
        $engagementRate = $totalDaysSpan > 0 ? $engagementDays / $totalDaysSpan : 0;

        // Calculate persistence score (higher score for consistent work over time)
        $persistenceScore = min(100, round($engagementRate * 70 + ($engagementDays > 1 ? 30 : 0)));

        return [
            'daily_progress' => $dailyProgress,
            'engagement_metrics' => [
                'days_active' => $engagementDays,
                'days_span' => $totalDaysSpan,
                'engagement_rate' => round($engagementRate, 2),
            ],
            'persistence_metrics' => [
                'persistence_score' => $persistenceScore,
            ],
        ];
    }

    /**
     * Generate recommendations specifically for instructors
     */
    private function generateInstructorRecommendations($levelsData, $plagiarismSummary, $progressionAnalysis)
    {
        $recommendations = [];

        // Learning path analysis
        if ($progressionAnalysis['reached_levels'] == 0) {
            $recommendations[] = [
                'type' => 'learning_path',
                'priority' => 'high',
                'message' => 'Student has not yet reached any learning level. Consider checking for onboarding issues or prerequisite knowledge gaps.',
            ];
        } elseif ($progressionAnalysis['reached_levels'] < 4) {
            $stuckLevel = null;
            $levelsInOrder = ['remembering', 'understanding', 'applying', 'analyzing'];

            for ($i = 0; $i < count($levelsInOrder); $i++) {
                $level = $levelsInOrder[$i];
                if (!isset($levelsData[$level]) || $levelsData[$level]['passed_questions'] == 0) {
                    $stuckLevel = $level;
                    break;
                }
            }

            if ($stuckLevel) {
                $recommendations[] = [
                    'type' => 'learning_path',
                    'priority' => 'medium',
                    'message' => "Student appears to be stuck at the $stuckLevel level. Consider providing targeted interventions for this learning stage.",
                ];
            }
        }

        // Attempt analysis
        $highAttemptLevels = [];
        foreach ($levelsData as $level => $data) {
            if (($data['avg_attempts'] ?? 0) > 3 && $data['attempted_questions'] > 0) {
                $highAttemptLevels[] = $level;
            }
        }

        if (!empty($highAttemptLevels)) {
            $recommendations[] = [
                'type' => 'attempts',
                'priority' => 'medium',
                'message' => 'Student required multiple attempts in these levels: ' . implode(', ', array_map('ucfirst', $highAttemptLevels)) . '. Consider reviewing teaching materials for these concepts.',
            ];
        }

        // Plagiarism analysis
        if ($plagiarismSummary['total_detected'] > 0) {
            $recommendations[] = [
                'type' => 'plagiarism',
                'priority' => 'high',
                'message' => "Detected {$plagiarismSummary['total_detected']} instance(s) of potential plagiarism. Academic integrity discussion recommended.",
            ];
        }

        // Learning progress analysis
        if ($progressionAnalysis['avg_time_between_levels'] !== null) {
            if ($progressionAnalysis['avg_time_between_levels'] > 72) {
                // 3 days
                $recommendations[] = [
                    'type' => 'progress_rate',
                    'priority' => 'medium',
                    'message' => 'Student is progressing slowly between levels (avg. ' . round($progressionAnalysis['avg_time_between_levels'] / 24, 1) . ' days between levels). Consider checking for comprehension issues.',
                ];
            } elseif ($progressionAnalysis['avg_time_between_levels'] < 1) {
                // Less than 1 hour
                $recommendations[] = [
                    'type' => 'progress_rate',
                    'priority' => 'low',
                    'message' => 'Student is progressing very quickly between levels. Verify depth of understanding.',
                ];
            }
        }

        // Add general recommendation if none were generated
        if (empty($recommendations)) {
            $recommendations[] = [
                'type' => 'general',
                'priority' => 'low',
                'message' => 'Student is progressing as expected through the learning levels.',
            ];
        }

        return $recommendations;
    }
}
