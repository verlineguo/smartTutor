<?php

namespace App\Http\Controllers;

use App\Models\AnswerLLM;
use App\Models\AnswerPDF;
use App\Models\AnswerUser;
use App\Models\Plagiarism;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EvaluationController extends Controller
{
    public function show($questionGuid, $answerGuid)
    {
        try {
            // Get the question details
            $question = Question::where('guid', $questionGuid)->firstOrFail();
            if (!$question) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Question not found',
                    ],
                    404,
                );
            }

            // Get user's answer
            $userAnswer = AnswerUser::where('guid', $answerGuid)->where('question_guid', $questionGuid)->firstOrFail();

            $referenceAnswers = [];
            if ($question->guid) {
                $referenceAnswers = AnswerPDF::where('question_guid', $question->guid)
                    ->orderBy('combined_score', 'desc')
                    ->get()
                    ->map(function ($answer) {
                        $answer->page_references = json_decode($answer->page_references, true); // Konversi JSON ke array
                        return $answer;
                    });
            }

            // Get plagiarism level (highest match from any LLM)
            $plagiarismData = $this->getPlagiarismData($answerGuid);


            return response()->json([
                'success' => true,
                'data' => [
                    'question' => $question,
                    'userAnswer' => $userAnswer,
                    'referenceAnswers' => $referenceAnswers,
                    'plagiarismLevel' => $plagiarismData['averageSimilarity'],
                    'highestAISimilarity' => $plagiarismData['highestSimilarity'],
                    'highestAISource' => $plagiarismData['highestSource'],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Error retrieving evaluation data: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    public function getAllPlagiarismData($questionGuid, $answerGuid)
    {
        try {
            $result = [];
            $plagiarismRecords = Plagiarism::where('user_answer_guid', $answerGuid)->get();

            foreach ($plagiarismRecords as $record) {
                $llmAnswer = AnswerLlm::where('guid', $record->ai_answer_guid)->first();

                if (!$llmAnswer) {
                    Log::warning('LLM answer not found for guid:', ['guid' => $record->ai_answer_guid]);
                    continue;
                }
                $detailedResults = DB::table('plagiarism_details')->where('plagiarism_guid', $record->guid)->get();
                $sentenceResults = [];
                foreach ($detailedResults as $detail) {
                    $sentenceResults[] = [
                        'student_text' => $detail->student_text,
                        'best_match' => $detail->best_match,
                        'is_plagiarized' => (bool) $detail->is_plagiarized,
                        'weighted_score' => $detail->weighted_score,
                        'individual_scores' => json_decode($detail->individual_scores, true) ?? [],
                    ];
                }
                $thresholds = json_decode($record->thresholds, true) ?? [];
                $methodWeights = json_decode($record->method_weights, true) ?? [];
                $detectedStrategies = json_decode($record->detected_strategies, true) ?? [];
                $overallScore = 0;
                if (!empty($methodWeights)) {
                    $overallScore = $record->cosine_similarity * ($methodWeights['cosine'] ?? 0.15) + $record->jaccard_similarity * ($methodWeights['jaccard'] ?? 0.15) + $record->bert_score * ($methodWeights['bert'] ?? 0.4) + $record->levenshtein_similarity * ($methodWeights['levenshtein'] ?? 0.05) + $record->ngram_similarity * ($methodWeights['ngram'] ?? 0.25);
                } else {
                    $overallScore = ($record->cosine_similarity + $record->jaccard_similarity + $record->bert_score + $record->levenshtein_similarity + $record->ngram_similarity) / 5;
                }

                $result[] = [
                    'source' => $llmAnswer->source,
                    'answer' => $llmAnswer->answer,
                    'cosine_similarity' => $record->cosine_similarity,
                    'jaccard_similarity' => $record->jaccard_similarity,
                    'bert_score' => $record->bert_score,
                    'levenshtein_similarity' => $record->levenshtein_similarity,
                    'ngram_similarity' => $record->ngram_similarity,
                    'thresholds' => $thresholds,
                    'method_weights' => $methodWeights,
                    'detected_strategies' => $detectedStrategies,
                    'average' => $overallScore,
                    'sentence_results' => $sentenceResults,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving plagiarism data:', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Error retrieving plagiarism data: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Helper function to get plagiarism data (used internally)
     */
    private function getPlagiarismData($answerGuid)
    {
        $result = [];

        // Get plagiarism records for this answer
        $plagiarismRecords = Plagiarism::where('user_answer_guid', $answerGuid)->get();

        $totalSimilarity = 0;
        $highestSimilarity = 0;
        $highestSource = '';

        foreach ($plagiarismRecords as $record) {
            // Get AI answer details
            $aiAnswer = AnswerLLM::where('guid', $record->ai_answer_guid)->first();

            if ($aiAnswer) {
                // Calculate weighted score based on method_weights
                $methodWeights = json_decode($record->method_weights, true);
                $weightedScore = $record->cosine_similarity * $methodWeights['cosine'] + $record->jaccard_similarity * $methodWeights['jaccard'] + $record->bert_score * $methodWeights['bert'] + $record->levenshtein_similarity * $methodWeights['levenshtein'] + $record->ngram_similarity * $methodWeights['ngram'];

                // Convert to percentage
                $similarityPercent = $weightedScore * 100;

                // Add to total for average calculation
                $totalSimilarity += $similarityPercent;

                // Track highest similarity
                if ($similarityPercent > $highestSimilarity) {
                    $highestSimilarity = $similarityPercent;
                    $highestSource = $aiAnswer->source ?? 'Unknown';
                }
            }
        }

        // Calculate average similarity
        $averageSimilarity = count($plagiarismRecords) > 0 ? $totalSimilarity / count($plagiarismRecords) : 0;

        return [
            'averageSimilarity' => $averageSimilarity,
            'highestSimilarity' => $highestSimilarity,
            'highestSource' => $highestSource,
        ];
    }

    public function downloadReport($questionGuid, $answerGuid)
    {
        // Get data
        $question = Question::where('guid', $questionGuid)->firstOrFail();
        $userAnswer = AnswerUser::where('guid', $answerGuid)->firstOrFail();
        $referenceAnswer = AnswerPdf::where('question_guid', $questionGuid)->first();
        $plagiarismData = Plagiarism::where('user_answer_guid', $answerGuid)->with('answerLlm')->get();

        // Generate PDF (This is just a placeholder - implement your PDF generation logic)
        $pdf = app()->make('dompdf.wrapper');
        $pdf->loadView('evaluation.report', compact('question', 'userAnswer', 'referenceAnswer', 'plagiarismData'));

        return $pdf->download('evaluation-report-' . $answerGuid . '.pdf');
    }
}
