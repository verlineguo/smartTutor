<?php

namespace App\Http\Controllers;

use App\Models\AnswerPDF;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\AnswerUser;
use App\Models\PageNoun;
use App\Models\Plagiarism;
use App\Models\Topic;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AssignmentController extends Controller
{
    public function getAvailableLanguages($topicGuid)
    {
        // Ambil bahasa unik dari tabel pertanyaan berdasarkan topik
        $languages = Question::where('topic_guid', $topicGuid)->select('language')->distinct()->pluck('language');

        return response()->json(['data' => $languages]);
    }

    public function getAnswerPdf($questionGuid)
    {
        try {
            $pdfAnswer = AnswerPDF::where('question_guid', $questionGuid)->first();

            if (!$pdfAnswer) {
                return response()->json(['error' => 'PDF answer not found'], 404);
            }

            return response()->json(
                [
                    'status' => true,
                    'data' => [
                        'guid' => $pdfAnswer->guid,
                        'question_guid' => $pdfAnswer->question_guid,
                        'answer' => $pdfAnswer->answer,
                        'combined_score' => $pdfAnswer->combined_score,
                        'qa_score' => $pdfAnswer->qa_score,
                        'retrieval_score' => $pdfAnswer->retrieval_score,
                        'created_at' => $pdfAnswer->created_at,
                        'updated_at' => $pdfAnswer->updated_at,
                    ],
                ],
                200,
            );
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getPlagiarismAnalysis($userAnswerGuid)
    {
        try {
            $userAnswer = AnswerUser::where('guid', $userAnswerGuid)->first();

            if (!$userAnswer) {
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => 'User answer not found',
                    ],
                    404,
                );
            }

            $plagiarismData = Plagiarism::where('user_answer_guid', $userAnswerGuid)
                ->join('answer_llm', 'plagiarisme.ai_answer_guid', '=', 'answer_llm.guid')
                ->get(['plagiarisme.*', 'answer_llm.source']);

            $result = [
                'user_answer' => $userAnswer,
                'plagiarism_analysis' => [],
            ];

            foreach ($plagiarismData as $item) {
                $result['plagiarism_analysis'][$item->source] = [
                    'cosine_similarity' => $item->cosine,
                    'jaccard_similarity' => $item->jaccard,
                    'bert_score' => $item->bert,
                    'highlighted_text' => $item->highlighted_text,
                    'average' => ($item->cosine + $item->jaccard + $item->bert) / 3,
                ];
            }

            return response()->json([
                'status' => 'success',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ],
                500,
            );
        }
    }

    public function getUserProgress($userId, $topicGuid)
    {
        try {
            // First get questions that belong to this topic
            $topicQuestions = Question::where('topic_id', $topicGuid)->pluck('guid');

            if ($topicQuestions->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No questions found for this topic',
                ]);
            }

            // Find the most recent answer submitted by this user for any question in this topic
            $latestAnswer = AnswerUser::where('user_id', $userId)->whereIn('question_guid', $topicQuestions)->orderBy('created_at', 'desc')->first();

            if (!$latestAnswer) {
                return response()->json([
                    'success' => false,
                    'message' => 'No previous progress found',
                ]);
            }

            // Get the question to determine language
            $question = Question::where('guid', $latestAnswer->question_guid)->first();

            // Return language and last page information
            return response()->json([
                'success' => true,
                'data' => [
                    'language' => $question->language,
                    'lastPage' => $latestAnswer->page,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Error retrieving progress: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    public function getAnswerHistory($userId, $topicGuid)
    {
        try {
            $history = AnswerUser::where('user_id', $userId)
                ->where('topic_guid', $topicGuid)
                ->join('questions', 'answer_user.question_guid', '=', 'questions.guid')
                ->orderBy('answer_user.created_at', 'desc')
                ->get(['answer_user.*', 'questions.question', 'questions.question_fix', 'questions.threshold']);

            return response()->json([
                'status' => 'success',
                'data' => $history,
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ],
                500,
            );
        }
    }

    public function submitAnswer(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|string',
            'question_guid' => 'required|string',
            'answer' => 'required|string',
            'page' => 'required|integer',
            'topic_guid' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ],
                422,
            );
        }

        try {
            // Get the question details
            $question = Question::where('guid', $request->question_guid)->first();
            if (!$question) {
                return response()->json(
                    [
                        'status' => false,
                        'message' => 'Question not found',
                    ],
                    404,
                );
            }

            // Store user answer
            $userAnswer = new AnswerUser();
            $userAnswer->guid = (string) Str::uuid();
            $userAnswer->user_id = $request->user_id;
            $userAnswer->question_guid = $request->question_guid;
            $userAnswer->answer = $request->answer;
            $userAnswer->page = $request->page;
            $userAnswer->save();

            $question = Question::where('guid', $request->question_guid)->first();
            if (!$question) {
                return response()->json(
                    [
                        'status' => false,
                        'message' => 'Question not found',
                    ],
                    404,
                );
            }

            $pdfAnswer = AnswerPDF::where('guid', $question->answer_pdf_guid)->first();
            if (!$pdfAnswer) {
                return response()->json(
                    [
                        'status' => false,
                        'message' => 'PDF answer not found',
                    ],
                    404,
                );
            }

            $similarityResult = $this->calculateCosineSimilarity($request->answer, $pdfAnswer->answer, $question->page, $question->topic_guid, $question->language, $question->question_fix);

            $similarity_score = $similarityResult['similarity_score'];

            $noun_cosine_data = $similarityResult['noun_cosine_data'];

            $userAnswer->update(['cosine_similarity' => $similarity_score]);

            $similarityMessage = '<p><strong>Cosine Similarity Score:</strong> ' . number_format($similarity_score, 2) . '%</p>';
            // Menambahkan data cosine similarity per noun
            if (!empty($noun_cosine_data)) {
                // Sorting noun_cosine_data berdasarkan cosine_answer
                $noun_cosine_data = collect($noun_cosine_data)->sortByDesc('cosine_similarity')->values()->toArray();

                $similarityMessage .= '<ul>';
                foreach ($noun_cosine_data as $nounData) {
                    $similarityMessage .= '<li><strong>Noun:</strong> ' . htmlspecialchars($nounData['noun']) . ' - <strong>Similarity Page:</strong> ' . number_format($nounData['cosine_similarity'] * 100, 2) . '% - ';
                }
                $similarityMessage .= '</ul>';
            }
            if ($similarity_score >= $question->threshold) {
                return response()->json([
                    'status' => 'success',
                    'nextPage' => $request->page + 1,
                    'similarityMessage' => $similarityMessage,
                    'similarity_score' => $similarity_score,
                    'threshold' => $question->threshold,
                    'noun_cosine_data' => $noun_cosine_data,
                    'data' => [
                        'user_answer_guid' => $userAnswer->guid,
                    ],
                ]);
            } else {
                $askedQuestions = AnswerUser::join('questions', 'answer_user.question_guid', '=', 'questions.question_guid')
                ->where('answer_user.user_id', $request->user_id)
                ->where('questions.topic_guid', $request->topic_guid)
                ->where('answer_user.page', $request->page)
                ->pluck('answer_user.question_guid')
                ->toArray();
                $remainingQuestions = Question::where('topic_guid', $request->topic_guid)->where('page', $request->page)->where('language', $question->language)->whereNotIn('guid', $askedQuestions)->get();

                if ($remainingQuestions->isEmpty()) {
                    // No questions left - offer regeneration
                    return response()->json([
                        'status' => 'no_questions_left',
                        'message' => 'No remaining questions. Would you like to regenerate with GPT?',
                        'similarityMessage' => $similarityMessage,
                        'similarity_score' => $similarity_score,
                        'threshold' => $question->threshold,
                        'data' => [
                            'user_answer_guid' => $userAnswer->guid,
                        ],
                    ]);
                } else {
                    // Get next random question
                    $nextQuestion = $remainingQuestions->random();

                    // Return retry response
                    return response()->json([
                        'status' => 'retry',
                        'nextQuestion' => $nextQuestion->question_fix,
                        'nextQuestionGuid' => $nextQuestion->guid,
                        'similarityMessage' => $similarityMessage,
                        'similarity_score' => $similarity_score,
                        'threshold' => $question->threshold,
                        'noun_cosine_data' => $noun_cosine_data,
                        'data' => [
                            'user_answer_guid' => $userAnswer->guid,
                        ],
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error processing submission: ' . $e->getMessage());

            return response()->json(
                [
                    'status' => false,
                    'message' => 'Error processing submission',
                    'error' => $e->getMessage(),
                ],
                500,
            );
        }
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
            $askedQuestions = AnswerUser::where('user_id', $validated['user_id'])
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
            $lastAnswer = AnswerUser::where('user_id', $validated['user_id'])
                ->where('topic_guid', $validated['topic_guid'])
                ->orderBy('created_at', 'desc')
                ->first();

            // Jika ada record chathistory terakhir, ambil question_guid-nya
            if ($lastAnswer) {
                $questionGuidFromHistory = $lastAnswer->question_guid;

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
                'question' => $newQuestion['question'],
                'question_fix' => $newQuestion['question'],
                'answer_openai' => $newQuestion['answer_openai'],
                'answer_fix' => $newQuestion['answer'],
                'topic_guid' => $validated['topic_guid'],
                'category' => $newQuestion['category'],
                'user_id' => $validated['user_id'],
                'page' => $validated['page'],
                'attempt' => $currentAttempt, // Increment attempt untuk setiap user
                'language' => $language,
                'weight' => 0,
                'threshold' => $threshold, // Menggunakan threshold dari pertanyaan terakhir
            ]);
            $nextQuestionMessage = '<div class="bot-message">
                Retry required! <br>
                <strong>Page:</strong> ' . $validated['page'] . ' <br>
                <strong>Threshold:</strong> ' . ($question->threshold ?? "N/A") . ' <br>
                <strong>Message:</strong> ' . $question->question_fix . '
            </div>';

                // Kirim respons sukses dengan pertanyaan baru
                return response()->json([
                'status' => 'success',
                'message' => 'Questions have been regenerated successfully.',
                'newQuestion' => [
                    'message' => $nextQuestionMessage,
                    'question_guid' => $question->guid,
                ],
            ]);
        }
    }


    protected function calculateCosineSimilarity($user_answer, $actual_answer, $page, $topic_guid, $language, $question)
    {
        try {
            // Ambil semua nouns dan nilai cosine untuk halaman yang relevan
            $pageNouns = PageNoun::where('topic_guid', $topic_guid)->where('language', $language)->where('page', $page)->get();

            $nounsData = $pageNouns
                ->map(function ($item) {
                    return [
                        'noun' => $item->noun,
                        'cosine_similarity' => $item->cosine,
                    ];
                })
                ->toArray();


            $response = Http::post(env('FLASK_API_URL') . '/cosine_similarity', [
                'user_answer' => $user_answer,
                'actual_answer' => $actual_answer,
            ]);

            if ($response->failed()) {
                throw new \Exception('Failed to calculate cosine similarity');
            }

            $responseData = $response->json();

            $similarity_score = $responseData['similarity_score'];

            // Default noun similarities jika tidak ada

            return [
                'similarity_score' => $similarity_score * 100,
                'noun_cosine_data' => $nounsData,
            ];
        } catch (\Exception $e) {
            Log::error('Error calculating cosine similarity: ' . $e->getMessage());
        }
    }


}
