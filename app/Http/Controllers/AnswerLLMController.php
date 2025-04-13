<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\AnswerLLM;

class AnswerLLMController extends Controller
{
    public function getLLMAnswer(Request $request)
    {
        set_time_limit(3000);
        $validator = Validator::make($request->all(), [
            'model' => 'required|string',
            'prompt' => 'required|string',
            'question_guid' => 'required|string',

        ]);

        if ($validator->fails()) {
            return ResponseController::getResponse(null, 422, $validator->errors()->first());
        }

        try {
            // Kirim permintaan ke Flask API
            $LLMResponse = Http::timeout(300)->post(env('FLASK_API_URL') . '/answer_llm', [
                'model' => $request->get('model'),
                'prompt' => $request->get('prompt'),
            ]);

            Log::info('LLM API Response: ' . $LLMResponse->body());
            if ($LLMResponse->failed() || !isset($LLMResponse->json()['response'])) {
                Log::error('Flask API Error: ' . $LLMResponse->body());
                return ResponseController::getResponse(null, 500, "Error getting response from LLM.");
            }

            $responseData = $LLMResponse->json();

           
            return ResponseController::getResponse($responseData, 200, 'Answer generated successfully.');
        } catch (\Exception $e) {
            Log::error('Error in getLLMAnswer: ' . $e->getMessage());
            return ResponseController::getResponse(null, 500, "Error: " . $e->getMessage());
        }
    }
}