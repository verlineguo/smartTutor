<?php

namespace App\Http\Controllers;

use App\Models\ChatHistory;
use App\Models\Question;
use App\Models\Topic;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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

        ChatHistory::create([
            'user_id' => $validated['user_id'],
            'topic_guid' => $validated['topic_guid'],
            'message' => $similarityMessage,
            'sender' => 'cosine',
            'page' => $validated['page'],
            'question_guid' => $validated['question_guid'],
            'cosine_similarity' => null,
        ]);
        // If no remaining questions, ask if the user wants to generate new questions
        if ($remainingQuestions->isEmpty()) {
            // Respond with a message to the frontend asking if the user wants to regenerate questions
            return response()->json([
                'status' => 'no_questions_left',
                'message' => 'No remaining questions. Would you like to regenerate questions?',
                'similarityMessage' => $similarityMessage,
                'similarity_score' => $similarity_score,
            ]);
        }

        // If there are remaining questions, select the next question and continue
        $nextQuestion = $remainingQuestions->random();


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


    public function regenerateQuestions(Request $request)
    {
        set_time_limit(2000);

        // Validasi input dari pengguna
        $validated = $request->validate([
            'user_id' => 'required|string',
            'topic_guid' => 'required|string',
            'page' => 'required|integer',
            'regenerate' => 'required|string',  // Validate regenerate as string ('true' or 'false')
        ]);

        // Convert regenerate string ('true'/'false') to boolean
        $isRegenerate = filter_var($validated['regenerate'], FILTER_VALIDATE_BOOLEAN);

        // Ambil topik berdasarkan topic_guid
        $topic = Topic::where('guid', $validated['topic_guid'])->first();
        if (!$topic) {
            return response()->json(['status' => 'error', 'message' => 'Topic not found.']);
        }

        // Ambil attempt terakhir yang digunakan oleh user_id untuk topik ini
        $userLastAttempt = Question::where('user_id', $validated['user_id'])
            ->where('topic_guid', $validated['topic_guid'])
            ->max('attempt'); // Cari nilai attempt tertinggi

        // Tentukan attempt saat ini
        $currentAttempt = $userLastAttempt ? $userLastAttempt + 1 : 1;

        // Cek apakah attempt yang diizinkan masih ada
        if ($currentAttempt > $topic->max_attempt_gpt) {
            return response()->json([
                'status' => 'no_attempts_left',
                'message' => 'You have reached the maximum attempts for this topic. Proceeding without regeneration.',
            ]);
        }

        // Jika regenerasi diinginkan dan masih ada sisa attempt
        if ($isRegenerate) {
            // Proses regenerasi pertanyaan
            $askedQuestions = ChatHistory::where('user_id', $validated['user_id'])
                ->where('topic_guid', $validated['topic_guid'])
                ->where('page', $validated['page'])
                ->pluck('question_guid')
                ->toArray();

            // Ambil pertanyaan yang sudah diajukan berdasarkan GUID
            $existingQuestions = Question::whereIn('guid', $askedQuestions)
                ->pluck('question_fix')
                ->toArray();

            // Ambil path file PDF dari topik
            $pdf_file_path = $topic->file_path;

            // Menentukan path lengkap menggunakan storage_path
            $full_pdf_path = storage_path('app/public/' . $pdf_file_path);

            // Memeriksa apakah file PDF ada
            if (!file_exists($full_pdf_path)) {
                // Mengembalikan response error jika file tidak ditemukan
                return response()->json(['status' => 'error', 'message' => 'PDF file not found at ' . $full_pdf_path]);
            }

            // Mengambil konten file PDF

            // Ambil data terakhir dari tabel chathistory untuk user_id dan topic_guid yang sesuai
            $lastChatHistory = ChatHistory::where('user_id', $validated['user_id'])
                ->where('topic_guid', $validated['topic_guid'])
                ->orderBy('created_at', 'desc')
                ->first();

            // Jika ada record chathistory terakhir, ambil question_guid-nya
            if ($lastChatHistory) {
                $questionGuidFromHistory = $lastChatHistory->question_guid;

                // Cari threshold berdasarkan question_guid dari tabel question
                $lastQuestion = Question::where('guid', $questionGuidFromHistory)->first();
                $language = $lastQuestion->language;

                // Tentukan nilai threshold dari pertanyaan terakhir
                $threshold = $lastQuestion ? $lastQuestion->threshold : null;
            } else {
                // Jika tidak ada record chathistory, threshold bisa null atau nilai default lainnya
                $threshold = null;
            }

            // Kirim file PDF ke Flask untuk mendapatkan pertanyaan baru
            $response = Http::attach('pdf', file_get_contents($full_pdf_path), 'file.pdf')
                ->timeout(1500)
                ->post(env('FLASK_API_URL') . '/regenerate', [
                    'page' => $validated['page'],
                    'language' => $language,
                    'existing_questions' => json_encode($existingQuestions), // Encode array to JSON
                ]);



            Log::info('Flask API Response:', [
                'status' => $response->status(),
                'body' => $response->body(),
                'json' => $response->json() // Bisa dicatat dalam format JSON jika diperlukan
            ]);

            // Periksa apakah API Flask berhasil
            if ($response->failed()) {
                return response()->json(['status' => 'error', 'message' => 'Failed to regenerate questions.']);
            }

            // Ambil pertanyaan baru dari response Flask
            $newQuestion = $response->json()['data']['questions'][0];

            // Simpan pertanyaan baru ke database dengan increment attempt

            $question = Question::create([
                'question_ai' => $newQuestion['question'],
                'question_fix' => $newQuestion['question'],
                'answer_ai' => $newQuestion['answer'],
                'answer_fix' => $newQuestion['answer'],
                'topic_guid' => $validated['topic_guid'],
                'category' => $newQuestion['category'],
                'user_id' => $validated['user_id'],
                'attempt' => $currentAttempt, // Increment attempt untuk setiap user
                'language' => $language,
                'weight' => 0,
                'threshold' => $threshold, // Menggunakan threshold dari pertanyaan terakhir
            ]);


            // Kirim respons sukses dengan pertanyaan baru
            return response()->json([
                'status' => 'success',
                'message' => 'Questions have been regenerated successfully.',
                'newQuestion' => $question,  // Pertanyaan baru dari Flask
            ]);
        }
    }




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
