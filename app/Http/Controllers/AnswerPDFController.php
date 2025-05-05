<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use App\Models\Topic;
use Illuminate\Support\Facades\Log;

class AnswerPDFController extends Controller
{
    // Add this method to your existing controller
    public function getBertAnswer(Request $request)
    {
        set_time_limit(30000);

        $validator = Validator::make($request->all(), [
            'topic_guid' => 'required|string',
            'language' => 'required|string',
            'question' => 'required|string',
        ]);
        
        if ($validator->fails()) {
            return ResponseController::getResponse(null, 422, $validator->errors()->first());
        }
       

        $topic = Topic::where('guid', $request->get('topic_guid'))->first();
        if (!$topic) {
            return ResponseController::getResponse(null, 404, 'Topik tidak ditemukan.');
        }

        // Parsing translation_metadata untuk mencari file berdasarkan bahasa
        $translationMetadata = json_decode($topic->translation_metadata, true);
        $translatedFile = collect($translationMetadata)->firstWhere('language', $request->get('language'));

       
        try {
            // Send the PDF and question to the Flask API
            $bertResponse = Http::attach('pdf', Storage::disk('public')->get($translatedFile['path']), 'translatedfile.pdf')
                ->timeout(3000)
                ->post(env('FLASK_API_URL') . '/bert_qa', [
                    'question' => $request->get('question'),
                    'language' => $request->get('language'),
                ]);

      
            
            if ($bertResponse->failed()) {
                return ResponseController::getResponse(null, 500, "Error getting answer from BERT.");
            }
            
            return ResponseController::getResponse($bertResponse->json(), 200, 'Answer generated successfully.');
        } catch (\Exception $e) {
            return ResponseController::getResponse(null, 500, "Error: " . $e->getMessage());
        }
    }
}
