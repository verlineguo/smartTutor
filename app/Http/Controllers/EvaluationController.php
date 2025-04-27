<?php

namespace App\Http\Controllers;

use App\Models\AnswerLLM;
use App\Models\AnswerPDF;
use App\Models\AnswerUser;
use App\Models\Plagiarism;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EvaluationController extends Controller
{
    public function show($questionGuid, $answerGuid)
    {
        try {
            // Get the question details
            $question = Question::where('guid', $questionGuid)->firstOrFail();
            if (!$question) {
                return response()->json([
                    'success' => false,
                    'message' => 'Question not found'
                ], 404);
            }

            // Get user's answer
            $userAnswer = AnswerUser::where('guid', $answerGuid)
                                    ->where('question_guid', $questionGuid)
                                    ->firstOrFail();
            
            
            // Get the most relevant PDF answer
            $referenceAnswer  = null;
            if ($question->answer_pdf_guid) {
                $referenceAnswer  = AnswerPDF::where('guid', $question->answer_pdf_guid)->first();
            }

            $passed = $userAnswer->cosine_similarity >= $question->threshold;

           
            $keywords = $this->extractKeywords($question->question_nouns, $userAnswer->answer);

             // Get plagiarism level (highest match from any LLM)
             $plagiarismData = $this->getPlagiarismData($answerGuid);
             $plagiarismLevel = 0;
             $plagiarismSource = '';
             
             foreach ($plagiarismData as $data) {
                 if ($data['average'] > $plagiarismLevel) {
                     $plagiarismLevel = $data['average'];
                     $plagiarismSource = $data['source'];
                 }
             }
             
             return response()->json([
                 'success' => true,
                 'data' => [
                     'question' => $question,
                     'userAnswer' => $userAnswer,
                     'referenceAnswer' => $referenceAnswer,
                     'keywords' => $keywords,
                     'passed' => $passed,
                     'plagiarismLevel' => $plagiarismLevel,
                     'plagiarismSource' => $plagiarismSource,
                 ]
             ]);
         } catch (\Exception $e) {
             return response()->json([
                 'success' => false,
                 'message' => 'Error retrieving evaluation data: ' . $e->getMessage()
             ], 500);
         }
    }
    
    public function getAllPlagiarismData($questionGuid, $answerGuid)
    {
        try {

            $result = [];
            
            
            // Get plagiarism records for this answer
            $plagiarismRecords = Plagiarism::where('user_answer_guid', $answerGuid)->get();
            foreach ($plagiarismRecords as $record) {
                // Get the LLM answer
                $llmAnswer = AnswerLlm::where('guid', $record->ai_answer_guid)->first();
                
                if (!$llmAnswer) {
                    Log::warning('LLM answer not found for guid:', ['guid' => $record->ai_answer_guid]);
                    continue; // Skip this record if LLM answer is not found
                }

                // Calculate average similarity
                $average = ($record->cosine + $record->jaccard + $record->bert) / 3;
                
                $result[] = [
                    'source' => $llmAnswer->source,
                    'answer' => $llmAnswer->answer,
                    'cosine_similarity' => $record->cosine_similarity,
                    'jaccard_similarity' => $record->jaccard_similarity,
                    'bert_score' => $record->bert_score,
                    'average' => $average
                ];
            }
            Log::info('Plagiarism data retrieved successfully:', ['data' => $result]);
            
            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving plagiarism data:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving plagiarism data: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get submission history
     */
    public function history(Request $request)
    {
        try {
            $userId = $request->user()->id; // Get authenticated user ID
            
            $history = AnswerUser::where('user_id', $userId)
                ->with('question')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function($item) {
                    return [
                        'created_at_formatted' => $item->created_at->format('M d, Y'),
                        'question_guid' => $item->question_guid,
                        'answer_guid' => $item->guid,
                        'question_fix' => $item->question->question_fix,
                        'cosine_similarity' => $item->cosine_similarity,
                        'passed' => $item->cosine_similarity >= $item->question->threshold
                    ];
                });
            
            return response()->json([
                'success' => true,
                'data' => $history
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving history: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Helper function to extract keywords and check if they're found in the answer
     */
    private function extractKeywords($questionNouns, $answer)
    {
        // This is a simplified example. In a real app, you'd have a more sophisticated algorithm.
        $nouns = explode(',', $questionNouns);
        $result = [];
        
        foreach ($nouns as $noun) {
            $noun = trim($noun);
            if (!empty($noun)) {
                $result[] = [
                    'keyword' => $noun,
                    'found' => stripos($answer, $noun) !== false,
                    'importance' => 'High' // This would be determined by your algorithm
                ];
            }
        }
        
        return $result;
    }
    
    /**
     * Helper function to get plagiarism data (used internally)
     */
    private function getPlagiarismData($answerGuid)
    {
        $result = [];
        
        // Get user answer
        $userAnswer = AnswerUser::where('guid', $answerGuid)->first();
        
        if (!$userAnswer) {
            return $result;
        }
        
        // Get plagiarism records for this answer
        $plagiarismRecords = Plagiarism::where('user_answer_guid', $answerGuid)->get();
        
        foreach ($plagiarismRecords as $record) {
            // Get the LLM answer
            $llmAnswer = AnswerLlm::where('guid', $record->answer_llm_guid)->first();
            
            if ($llmAnswer) {
                // Calculate average similarity
                $average = ($record->cosine + $record->jaccard + $record->bert + $record) / 3;
                
                $result[] = [
                    'source' => $llmAnswer->source,
                    'answer' => $llmAnswer->answer,
                    'cosine_similarity' => $record->cosine,
                    'jaccard_similarity' => $record->jaccard,
                    'bert_score' => $record->bert,
                    'average' => $average
                ];
            }
        }
        
        return $result;
    }
    public function downloadReport($questionGuid, $answerGuid)
    {
        // Get data
        $question = Question::where('guid', $questionGuid)->firstOrFail();
        $userAnswer = AnswerUser::where('guid', $answerGuid)->firstOrFail();
        $referenceAnswer = AnswerPdf::where('question_guid', $questionGuid)->first();
        $plagiarismData = Plagiarism::where('user_answer_guid', $answerGuid)
            ->with('answerLlm')
            ->get();
        
        // Generate PDF (This is just a placeholder - implement your PDF generation logic)
        $pdf = app()->make('dompdf.wrapper');
        $pdf->loadView('evaluation.report', compact('question', 'userAnswer', 'referenceAnswer', 'plagiarismData'));
        
        return $pdf->download('evaluation-report-' . $answerGuid . '.pdf');
    }

}
