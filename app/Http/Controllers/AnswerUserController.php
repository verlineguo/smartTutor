<?php

namespace App\Http\Controllers;

use App\Models\AnswerLLM;
use App\Models\AnswerPDF;
use App\Models\AnswerUser;
use App\Models\Plagiarism;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;


class AnswerUserController extends Controller
{
    public function submit(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|string',
            'topic_guid' => 'required|string|uuid',
            'question_guid' => 'required|string|uuid',
            'answer' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Get the question details
            $question = Question::where('guid', $request->question_guid)->first();
            if (!$question) {
                return response()->json([
                    'status' => false,
                    'message' => 'Question not found',
                ], 404);
            }

            // Store user answer
            $userAnswer = new AnswerUser();
            $userAnswer->guid = (string) Str::uuid();
            $userAnswer->user_id = $request->user_id;
            $userAnswer->question_guid = $request->question_guid;
            $userAnswer->topic_guid = $request->topic_guid;
            $userAnswer->answer = $request->answer;
            $userAnswer->page = $question->page;
            $userAnswer->save();

            // Get PDF answer for the question
            $pdfAnswer = $this->getPdfAnswer($request->question_guid);


            $cosineSimilarity = $this->calculateCosineSimilarity($request->answer, $question->answer_fix ?? '');

            $score = $this->calculateScore($cosineSimilarity, $pdfAnswer->qa_score ?? 0, $pdfAnswer->retrieval_score ?? 0);

            $pdfUrl = $this->generatePdf($pdfAnswer->answer, $request->answer, $score);

            
            return response()->json([
                'status' => true,
                'message' => 'Answer submitted successfully',
                'data' => [
                    'guid' => $pdfAnswer->guid ?? null,
                    'pdf_url' => $pdfUrl,
                    'pdf_content' => $pdfAnswer->answer ?? '',
                    'answer' => $pdfAnswer->answer ?? '',
                    'score' => $score
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error processing submission',
                'error' => $e->getMessage()
            ], 500);
        }
    }
 

}
