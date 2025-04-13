<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;


class AssignmentController extends Controller
{
    public function submitAnswer(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required',
            'topic_guid' => 'required',
            'question_guid' => 'required',
            'answer' => 'required'
        ]);

        try {
            $response = Http::post(env('FLASK_API_URL') . '/api/v1/assignment/submit', [
                    'user_id' => $validated['user_id'],
                    'topic_guid' => $validated['topic_guid'],
                    'question_guid' => $validated['question_guid'],
                    'answer' => $validated['answer']
                ]);

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json(['error' => 'Failed to submit answer'], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    
    public function getPlagiarismAnalysis($userAnswerGuid)
    {
        try {
            $response = Http::get(env('FLASK_API_URL')  . '/api/v1/assignment/plagiarism/' . $userAnswerGuid);

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json(['error' => 'Failed to fetch plagiarism analysis'], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getAnswerHistory($userId, $topicGuid)
    {
        try {
            $response = Http::get(env('FLASK_API_URL'). '/api/v1/assignment/history/' . $userId . '/' . $topicGuid);

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json(['error' => 'Failed to fetch answer history'], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
