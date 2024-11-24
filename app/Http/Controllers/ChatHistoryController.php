<?php

namespace App\Http\Controllers;

use App\Models\ChatHistory;
use App\Models\Question;
use App\Models\Topic;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class ChatHistoryController extends Controller
{
    public function saveMessage(Request $request)
    {
        // Validate the request
        $validated = $request->validate([
            'user_id' => 'required|string',
            'topic_guid' => 'required|string',
            'message' => 'required|string',
            'sender' => 'required|in:user,bot,cosine',
            'page' => 'required|integer',
            'question_guid' => 'required|string',
        ]);

        usleep(1000000); // Delay for 1 second

        if (in_array($validated['sender'], ['bot', 'cosine'])) {
            // Save bot's or cosine's message to chat history
            ChatHistory::create($validated);
            return response()->json(['status' => 'question_saved']);
        }


        // For user sender, proceed with saving the user's response
        $chatHistory = ChatHistory::create(array_merge($validated, ['cosine_similarity' => null]));

        // Fetch the corresponding question
        $question = Question::where('guid', $validated['question_guid'])->first();
        if (!$question) {
            return response()->json(['error' => 'Question not found'], 404);
        }

        // Calculate cosine similarity
        $similarity_score = $this->calculateCosineSimilarity($validated['message'], $question->answer_fix);

        // Update chat history with the calculated cosine similarity score
        $chatHistory->update(['cosine_similarity' => $similarity_score]);

        // Prepare similarity message for response
        $similarityMessage = "Cosine Similarity Score: {$similarity_score}%";

        // Determine if similarity score meets or exceeds threshold
        if ($similarity_score >= $question->threshold) {
            return $this->responseForSuccess($validated['page'], $similarityMessage, $similarity_score, $question->threshold);
        }

        // Otherwise, check for remaining questions on the current page
        return $this->responseForRetry($validated, $similarityMessage, $similarity_score, $question->threshold, $question->language);
    }

    /**
     * Calculate cosine similarity by calling the Flask API.
     */
    protected function calculateCosineSimilarity($user_answer, $actual_answer)
    {
        $flask_url = env('FLASK_API_URL') . '/cosine_similarity';
        $response = Http::post($flask_url, [
            'user_answer' => $user_answer,
            'actual_answer' => $actual_answer
        ]);

        if ($response->failed()) {
            throw new \Exception('Failed to calculate cosine similarity');
        }

        return $response->json()['similarity_score'] * 100;
    }

    /**
     * Prepare success response for when the similarity score meets the threshold.
     */
    protected function responseForSuccess($currentPage, $similarityMessage, $similarity_score, $threshold)
    {
        return response()->json([
            'status' => 'success',
            'nextPage' => $currentPage + 1,
            'similarityMessage' => $similarityMessage,
            'similarity_score' => $similarity_score,
            'threshold' => $threshold
        ]);
    }

    /**
     * Prepare retry response for when the similarity score is below the threshold.
     */
    protected function responseForRetry($validated, $similarityMessage, $similarity_score, $threshold, $language)
    {
        // Get already asked questions for this topic and page
        $askedQuestions = ChatHistory::where('user_id', $validated['user_id'])
            ->where('topic_guid', $validated['topic_guid'])
            ->where('page', $validated['page'])
            ->pluck('question_guid')
            ->toArray();

        // Find remaining questions for this topic and page
        $remainingQuestions = Question::where('topic_guid', $validated['topic_guid'])
            ->where('page', $validated['page'])
            ->where('language', $language)
            ->whereNotIn('guid', $askedQuestions)
            ->get();

        if ($remainingQuestions->isEmpty()) {
            // If no remaining questions, proceed to next page
            return $this->responseForSuccess($validated['page'], $similarityMessage, $similarity_score, $threshold);
        }

        // Pick a random question from the remaining questions
        $nextQuestion = $remainingQuestions->random();
        ChatHistory::create([
            'user_id' => $validated['user_id'],
            'topic_guid' => $validated['topic_guid'],
            'message' => $similarityMessage,
            'sender' => 'cosine',
            'page' => $validated['page'],
            'question_guid' => $nextQuestion->guid,
            'cosine_similarity' => null,
        ]);

        usleep(1000000);

        // Save the retry question to ChatHistory
        ChatHistory::create([
            'user_id' => $validated['user_id'],
            'topic_guid' => $validated['topic_guid'],
            'message' => $nextQuestion->question_fix,
            'sender' => 'bot',
            'page' => $validated['page'],
            'question_guid' => $nextQuestion->guid,
            'cosine_similarity' => null, // Retry questions do not have cosine similarity
        ]);

        return response()->json([
            'status' => 'retry',
            'nextQuestion' => $nextQuestion->question_fix,
            'nextQuestionGuid' => $nextQuestion->guid,
            'similarityMessage' => $similarityMessage,
            'similarity_score' => $similarity_score,
            'threshold' => $threshold,
        ]);
    }

    // protected function responseForRetry($validated, $similarityMessage, $similarity_score, $threshold, $language)
    // {
    //     // Cari pertanyaan yang sudah diajukan
    //     $askedQuestions = ChatHistory::where('user_id', $validated['user_id'])
    //         ->where('topic_guid', $validated['topic_guid'])
    //         ->where('page', $validated['page'])
    //         ->pluck('question_guid')
    //         ->toArray();

    //     // Cari pertanyaan yang belum diajukan
    //     $remainingQuestions = Question::where('topic_guid', $validated['topic_guid'])
    //         ->where('page', $validated['page'])
    //         ->where('language', $language)
    //         ->whereNotIn('guid', $askedQuestions)
    //         ->get();

    //     if ($remainingQuestions->isEmpty()) {
    //         // Jika stok pertanyaan habis, ambil informasi topik
    //         $topic = Topic::where('guid', $validated['topic_guid'])->first();

    //         if (!$topic) {
    //             return response()->json(['error' => 'Topic not found'], 404);
    //         }

    //         // Panggil Flask API untuk menghasilkan pertanyaan baru
    //         $flask_url = env('FLASK_API_URL') . '/generate_question';

    //         // Ambil semua pertanyaan yang telah dibuat
    //         $generatedQuestions = ChatHistory::where('topic_guid', $validated['topic_guid'])
    //             ->where('page', $validated['page'])
    //             ->where('sender', 'bot')
    //             ->pluck('message')
    //             ->toArray();

    //         // Kirim permintaan ke Flask API
    //         $response = Http::attach('pdf', file_get_contents($topic->pdf_path), basename($topic->pdf_path))
    //             ->post($flask_url, [
    //                 'language' => $language,
    //                 'page' => $validated['page'],
    //                 'generated_questions' => $generatedQuestions,
    //             ]);

    //         if ($response->failed()) {
    //             return response()->json(['error' => 'Failed to generate new question from Flask API'], 500);
    //         }

    //         $newQuestion = $response->json()['question'];

    //         // Simpan pertanyaan baru ke dalam ChatHistory
    //         ChatHistory::create([
    //             'user_id' => $validated['user_id'],
    //             'topic_guid' => $validated['topic_guid'],
    //             'message' => $newQuestion,
    //             'sender' => 'bot',
    //             'page' => $validated['page'],
    //             'question_guid' => null, // Karena pertanyaan dihasilkan oleh Flask
    //             'cosine_similarity' => null,
    //         ]);

    //         return response()->json([
    //             'status' => 'retry',
    //             'nextQuestion' => $newQuestion,
    //             'nextQuestionGuid' => null,
    //             'similarityMessage' => $similarityMessage,
    //             'similarity_score' => $similarity_score,
    //             'threshold' => $threshold,
    //         ]);
    //     }

    //     // Jika masih ada pertanyaan yang tersisa, pilih satu secara acak
    //     $nextQuestion = $remainingQuestions->random();

    //     // Simpan ke dalam ChatHistory
    //     ChatHistory::create([
    //         'user_id' => $validated['user_id'],
    //         'topic_guid' => $validated['topic_guid'],
    //         'message' => $nextQuestion->question_fix,
    //         'sender' => 'bot',
    //         'page' => $validated['page'],
    //         'question_guid' => $nextQuestion->guid,
    //         'cosine_similarity' => null,
    //     ]);

    //     return response()->json([
    //         'status' => 'retry',
    //         'nextQuestion' => $nextQuestion->question_fix,
    //         'nextQuestionGuid' => $nextQuestion->guid,
    //         'similarityMessage' => $similarityMessage,
    //         'similarity_score' => $similarity_score,
    //         'threshold' => $threshold,
    //     ]);
    // }



    public function getHistory($topicGuid, $userId)
    {
        // Mendapatkan history berdasarkan user_id dan topic_guid
        $history = ChatHistory::where('user_id', $userId)
            ->where('topic_guid', $topicGuid)
            ->orderBy('created_at')
            ->get();

        return response()->json(['data' => $history]);
    }

    public function checkStatus($topicGuid, $userId)
    {
        // Mendapatkan topik berdasarkan GUID
        $topic = Topic::where('guid', $topicGuid)->first();

        if (!$topic) {
            return response()->json(['error' => 'Topic not found'], 404);
        }

        // Memeriksa apakah waktu selesai topik sudah lewat
        $timeEnd = new Carbon($topic->time_end);
        $isTimePassed = $timeEnd->isPast();

        // Mendapatkan nomor page terakhir dari chat history user

        // Menentukan status is_read_only berdasarkan waktu atau penyelesaian halaman terakhir
        $isReadOnly = $isTimePassed;

        return response()->json(['data' => [
            'is_read_only' => $isReadOnly,
        ]]);
    }
    public function getAvailableLanguages($topicGuid)
    {
        // Ambil bahasa unik dari tabel pertanyaan berdasarkan topik
        $languages = Question::where('topic_guid', $topicGuid)
            ->select('language')
            ->distinct()
            ->pluck('language');

        return response()->json(['data' => $languages]);
    }

    public function resetHistories(Request $request)
    {
        // Validasi input untuk memastikan user_id dan topic_guid diberikan
        $validated = $request->validate([
            'user_id' => 'required|string',
            'topic_guid' => 'required|string',
        ]);

        // Hapus semua chat histories berdasarkan user_id dan topic_guid
        $deleted = ChatHistory::where('user_id', $validated['user_id'])
            ->where('topic_guid', $validated['topic_guid'])
            ->delete();

        // Periksa apakah ada data yang dihapus
        if ($deleted) {
            return response()->json([
                'message' => 'Chat histories have been successfully reset.',
            ], 200); // Respon sukses
        } else {
            return response()->json([
                'message' => 'No chat histories found for the given user and topic.',
            ], 404); // Respon jika tidak ada data ditemukan
        }
    }
}
