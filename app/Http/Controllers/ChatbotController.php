<?php

namespace App\Http\Controllers;

use App\Models\Question;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    public function getQuestion()
    {
        // Mendapatkan pertanyaan acak dari database
        $question = Question::inRandomOrder()->first();
        return response()->json(['question' => $question->question, 'id' => $question->id]);
    }

    public function answerQuestion(Request $request)
    {
        $question = Question::find($request->id);
        if ($question) {
            // Anda bisa memproses jawaban pengguna di sini
            return response()->json(['success' => true, 'message' => 'Answer received.']);
        }
        return response()->json(['success' => false, 'message' => 'Question not found.']);
    }
}
