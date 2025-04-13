<?php

namespace App\Http\Controllers;

use App\Models\AnswerLLM;
use App\Models\AnswerPDF;
use App\Models\Gram;
use App\Models\PageNoun;
use App\Models\Question;
use App\Models\Topic;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Session\Session;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class QuestionController extends Controller
{

    public function uploadFile(Request $request)
    {
        $path = 'public/file/';
        $pathUrl = 'file/';
        $file = $request->file('pdf');
        $name = $file->hashName();
        $file->storeAs($path, $name);
        $page = Http::attach('pdf', file_get_contents('/home/u486571172/domains/smart-tutor-fit.com/storage/app/public/file/' . $name), 'file.pdf', ['Content-Type' => 'pdf'])
            ->post(
                'http://91.108.110.58/count-page'
            );
        $page = json_decode($page, true);
        return ResponseController::getResponse(['path' => $path, 'name' => $name, 'page' => $page], 200, 'Success');
    }

    public function checkCossine(Request $request)
    {
        $Rawdata = Http::timeout(300)->post(
            'http://91.108.110.58/cossine-similarity',
            [
                'question' => $request->get('question'),
                'answer' => $request->get('answer')
            ]
        );
        $data = json_encode($Rawdata[0][0], true);
        if (isset($data)) {
            $floatValue = floatval($data);
            $formattedValue = number_format($floatValue * 100, 2);
            return $formattedValue;
        } else {
            return false;
        }
    }
    
    public function convertDatatable(Request $request)
    {
        $dataTable = DataTables::of($request->get('data'))
            ->addIndexColumn()
            ->make(true);
        Storage::delete($request['path'] . $request['name']);
        return $dataTable;
    }

    public function translateDocument(Request $request)
    {
        set_time_limit(1500);

        $validator = Validator::make($request->all(), [
            'topic_guid' => 'required|string',
            'language' => 'required|string',
        ]);

        if ($validator->fails()) {
            return ResponseController::getResponse(null, 422, $validator->errors()->first());
        }

        $topic = Topic::where('guid', $request->get('topic_guid'))->first();
        if (!$topic) {
            return ResponseController::getResponse(null, 404, "Topik tidak ditemukan.");
        }

        $requestedLanguage = $request->get('language');
        $existingTranslationMetadata = json_decode($topic->translation_metadata, true) ?? [];

        // Cek apakah bahasa sudah tersedia di metadata
        $metadataExists = collect($existingTranslationMetadata)->contains(function ($metadata) use ($requestedLanguage) {
            return $metadata['language'] === $requestedLanguage;
        });

        if ($metadataExists) {
            return ResponseController::getResponse(
                ['translation_metadata' => $topic->translation_metadata],
                200,
                'File sudah tersedia dalam bahasa yang diminta.'
            );
        }

        $filePath = storage_path('app/public/' . $topic->file_path);

        if (!file_exists($filePath)) {
            return ResponseController::getResponse(null, 404, "File tidak ditemukan.");
        }

        // Proses Translate File
        $response = Http::attach('pdf', file_get_contents($filePath), 'file.pdf')
            ->timeout(1500)
            ->post(env('FLASK_API_URL') . '/translate', [
                'language' => $requestedLanguage,
            ]);

        if ($response->failed()) {
            return ResponseController::getResponse(null, 500, "Terjadi kesalahan saat menerjemahkan dokumen.");
        }

        $translatedPdfContent = $response->body();
        $fileName = 'translated_' . uniqid() . '.pdf';
        $translatedFilePath = 'uploads/topics/translated/' . $fileName;
        Storage::disk('public')->put($translatedFilePath, $translatedPdfContent);

        // Tambahkan ke translation metadata
        $existingTranslationMetadata[] = [
            'language' => $requestedLanguage,
            'path' => $translatedFilePath
        ];
        $topic->translation_metadata = json_encode($existingTranslationMetadata);
        $topic->save();

        return ResponseController::getResponse(['translation_metadata' => $topic->translation_metadata], 200, 'Dokumen berhasil diterjemahkan.');
    }




    public function calculateTfidf(Request $request)
    {
        set_time_limit(900);

        $validator = Validator::make($request->all(), [
            'topic_guid' => 'required|string',
            'language' => 'required|string',
        ]);

        if ($validator->fails()) {
            return ResponseController::getResponse(null, 422, $validator->errors()->first());
        }

        $topic = Topic::where('guid', $request->get('topic_guid'))->first();
        if (!$topic) {
            return ResponseController::getResponse(null, 404, "Topik tidak ditemukan.");
        }

        // Parsing translation_metadata untuk mencari file berdasarkan bahasa
        $translationMetadata = json_decode($topic->translation_metadata, true);
        $translatedFile = collect($translationMetadata)->firstWhere('language', $request->get('language'));

        if (!$translatedFile || !Storage::disk('public')->exists($translatedFile['path'])) {
            return ResponseController::getResponse(null, 404, "File terjemahan untuk bahasa yang diminta tidak ditemukan.");
        }

        // Cek apakah data TF-IDF sudah ada untuk bahasa tersebut
        $existingGramData = Gram::where('topic_guid', $topic->guid)
            ->where('language', $request->get('language'))
            ->exists();

        if ($existingGramData) {
            $tfidfData = Gram::where('topic_guid', $topic->guid)
                ->where('language', $request->get('language'))
                ->get(['noun', 'gram_type', 'tfidf_val', 'cosine_val'])
                ->groupBy('gram_type')
                ->map(function ($group) {
                    return $group->map(function ($item) {
                        return [
                            'N-gram' => $item->noun,
                            'TF-IDF Score' => $item->tfidf_val,
                            'Cosine Similarity' => $item->cosine_val,
                        ];
                    });
                })->toArray();

            return $this->calculateTfidfPage($request);
        }

        // Proses TF-IDF menggunakan file terjemahan yang ditemukan
        $response = Http::attach(
            'pdf',
            Storage::disk('public')->get($translatedFile['path']),
            'translated_file.pdf'
        )->timeout(900)
            ->post(env('FLASK_API_URL') . '/tfidf', [
                'language' => $request->get('language'),
            ]);

        if ($response->failed()) {
            return ResponseController::getResponse(null, 500, "Kesalahan saat menghitung TF-IDF.");
        }

        $data = $response->json();
        foreach (['unigram' => 'uni', 'bigram' => 'bi', 'trigram' => 'tri'] as $type => $gramType) {
            if (isset($data[$type])) {
                foreach ($data[$type] as $gram) {
                    Gram::create([
                        'noun' => $gram['N-gram'],
                        'topic_guid' => $topic->guid,
                        'language' => $request->get('language'), // Tambahkan bahasa
                        'gram_type' => $gramType,
                        'tfidf_val' => $gram['TF-IDF Score'],
                        'cosine_val' => $data['cosine_similarity'][$gram['N-gram']] ?? 0.0,
                    ]);
                }
            }
        }

        // Setelah selesai menghitung TF-IDF, lanjutkan untuk menghitung TF-IDF per halaman
        return $this->calculateTfidfPage($request);
    }

    public function calculateTfidfPage(Request $request)
    {
        set_time_limit(900);

        $validator = Validator::make($request->all(), [
            'topic_guid' => 'required|string',
            'language' => 'required|string',
        ]);

        if ($validator->fails()) {
            return ResponseController::getResponse(null, 422, $validator->errors()->first());
        }

        $topic = Topic::where('guid', $request->get('topic_guid'))->first();
        if (!$topic) {
            return ResponseController::getResponse(null, 404, "Topik tidak ditemukan.");
        }

        // Parsing translation_metadata untuk mencari file berdasarkan bahasa
        $translationMetadata = json_decode($topic->translation_metadata, true);
        $translatedFile = collect($translationMetadata)->firstWhere('language', $request->get('language'));

        if (!$translatedFile || !Storage::disk('public')->exists($translatedFile['path'])) {
            return ResponseController::getResponse(null, 404, "File terjemahan untuk bahasa yang diminta tidak ditemukan.");
        }

        // Cek apakah data PageNoun sudah ada untuk topic_guid dan language
        $existingPageNouns = PageNoun::where('topic_guid', $topic->guid)
            ->where('language', $request->get('language'))
            ->exists();

        if ($existingPageNouns) {
            // Jika data sudah ada, kembalikan response tanpa perlu melakukan request ke Flask
            $pageNounData = PageNoun::where('topic_guid', $topic->guid)
                ->where('language', $request->get('language'))
                ->get(['page', 'noun', 'cosine'])
                ->groupBy('page')
                ->map(function ($group) {
                    return $group->map(function ($item) {
                        return [
                            'Noun' => $item->noun,
                            'Cosine Similarity' => $item->cosine,
                        ];
                    });
                })->toArray();

            return ResponseController::getResponse(['page_noun_data' => $pageNounData], 200, 'Data PageNoun sudah ada.');
        }

        // Proses TF-IDF menggunakan file terjemahan yang ditemukan
        $response = Http::attach(
            'pdf',
            Storage::disk('public')->get($translatedFile['path']),
            'translated_file.pdf'
        )->timeout(900)
            ->post(env('FLASK_API_URL') . '/tfidf-page', [
                'language' => $request->get('language'),
            ]);

        if ($response->failed()) {
            return ResponseController::getResponse(null, 500, "Kesalahan saat menghitung TF-IDF.");
        }

        $data = $response->json();

        // Penyimpanan data TF-IDF dan Cosine Similarity ke dalam database untuk setiap halam

        foreach ($data as $page => $cosineData) {
            foreach ($cosineData['cosine_similarity'] as $noun => $cosine) {
                // Simpan data ke dalam tabel 'page_noun' untuk setiap halaman
                PageNoun::create([
                    'topic_guid' => $topic->guid,
                    'language' =>  $request->get('language'),
                    'page' => (int) $page,  // Menyimpan nomor halaman
                    'noun' => $noun,  // Menyimpan kata kunci
                    'cosine' => (float) $cosine,  // Menyimpan nilai cosine similarity
                ]);
            }
        }

        return ResponseController::getResponse(['tfidf_data' => $data], 200, 'TF-IDF berhasil dihitung.');
    }

    public function getLlmAnswers($topicGuid)
    {
        try {
            $questions = Question::where('topic_guid', $topicGuid)
                ->orderByRaw('cast(page as unsigned) asc')
                ->get();

            $result = [];
            foreach ($questions as $question) {
                $llmAnswers = AnswerLLM::where('question_guid', $question->guid)->get();
                
                $item = [
                    'guid' => $question->guid,
                    'question' => $question->question,
                    'openai_answer' => null,
                    'gemini_answer' => null,
                    'deepseek_answer' => null
                ];

                foreach ($llmAnswers as $answer) {
                    if ($answer->source === 'openai') {
                        $item['openai_answer'] = $answer->answer;
                    } elseif ($answer->source === 'gemini') {
                        $item['gemini_answer'] = $answer->answer;
                    } elseif ($answer->source === 'deepseek') {
                        $item['deepseek_answer'] = $answer->answer;
                    }
                }

                $result[] = $item;
            }

            return DataTables::of($result)
                ->addIndexColumn()
                ->make(true);
        } catch (\Exception $e) {
            return ResponseController::getResponse(null, 500, $e->getMessage());
        }
    }



    public function getLlmAnswersByQuestion($questionGuid)
    {
        try {
            $answers = AnswerLLM::where('question_guid', $questionGuid)->get();
            
            return response()->json([
                'status' => 'success',
                'message' => 'LLM answers retrieved successfully',
                'data' => $answers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getPdfAnswers($topicGuid)
    {
        try {
            $questions = Question::where('topic_guid', $topicGuid)
                ->orderByRaw('cast(page as unsigned) asc')
                ->get();

            $result = [];
            foreach ($questions as $question) {
                $pdfAnswer = AnswerPdf::where('question_guid', $question->guid)->first();
                
                $item = [
                    'guid' => $question->guid,
                    'question' => $question->question,
                    'pdf_answer' => $pdfAnswer ? $pdfAnswer->answer : null,
                    'combined_score' => $pdfAnswer ? $pdfAnswer->combined_score : null,
                    'qa_score' => $pdfAnswer ? $pdfAnswer->qa_score : null,
                    'retrieval_score' => $pdfAnswer ? $pdfAnswer->retrieval_score : null
                ];

                $result[] = $item;
            }

            return DataTables::of($result)
                ->addIndexColumn()
                ->make(true);
        } catch (\Exception $e) {
            return ResponseController::getResponse(null, 500, $e->getMessage());
        }
    }

    public function getPdfAnswersByQuestion($questionGuid)
    {
        try {
            $question = Question::where('guid', $questionGuid)->first();
                
            if (!$question) {
                return response()->json([
                    'status' => false,
                    'message' => 'Question not found'
                ], 404);
            }
            
            $pdfAnswers = AnswerPDF::where('question_guid', $questionGuid)
                ->orderBy('combined_score', 'desc')
                ->get();
            
            // Mark the currently selected answer if it exists
            foreach ($pdfAnswers as $key => $answer) {
                $pdfAnswers[$key]->is_selected = ($answer->guid === $question->answer_pdf_guid);
                
                // Add the page from the question if it exists
                if ($question->page) {
                    $pdfAnswers[$key]->page = $question->page;
                }
            }
                
            return response()->json([
                'status' => true,
                'message' => 'PDF answers loaded successfully',
                'data' => $pdfAnswers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to load PDF answers: ' . $e->getMessage()
            ], 500);
        }
    }




    public function saveQuestions(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'topic_guid' => 'required|string',
            'questions' => 'required|array',
        ]);
    
        if ($validator->fails()) {
            return ResponseController::getResponse(null, 422, $validator->errors()->first());
        }
    
        try {
            $questions = $request->get('questions');
    
            foreach ($questions as $questionData) {
   
                $question = Question::create([
                    'topic_guid' => $request->topic_guid,
                    'question' => $questionData['question'],
                    'question_fix' => $questionData['question'],
                    'language' => $questionData['language'],
                    'threshold' => $questionData['threshold'] ?? 0,
                    'weight' => 1.0,
                    'category' => $questionData['category'] ?? null,
                    'question_nouns' => json_encode($questionData['question_nouns'] ?? []),
                    'page' => $questionData['page_number'] ?? null,
                    'cossine_similarity' => $questionData['cosine_q&d'] ?? 0,
                ]);
    
                if (isset($questionData['all_pdf_answers']) && is_array($questionData['all_pdf_answers'])) {
                    foreach ($questionData['all_pdf_answers'] as $pdfAnswer) {
                        $answerPdf = AnswerPDF::create([
                            'question_guid' => $question->guid,
                            'answer' => $pdfAnswer['answer'],
                            'combined_score' => $pdfAnswer['combined_score'] ?? 0,
                            'qa_score' => $pdfAnswer['qa_score'] ?? 0,
                            'retrieval_score' => $pdfAnswer['retrieval_score'] ?? 0,
                        ]);
                        
                        // If this is the best answer, update the question with the reference
                        if (isset($questionData['pdf_answer']) && $questionData['pdf_answer'] == $pdfAnswer['answer']) {
                            $question->answer_pdf_guid = $answerPdf->guid;
                            $question->answer_fix = $answerPdf->answer;
                            $question->save();
                        }
                    }
                }
               
    
                // Simpan jawaban LLM ke tabel `answer_llm`
                if (isset($questionData['answer_openai'])) {
                    AnswerLLM::create([
                        'question_guid' => $question->guid,
                        'answer' => $questionData['answer_openai'],
                        'source' => 'openai',
                    ]);
                }
    
                if (isset($questionData['answer_gemini'])) {
                    AnswerLLM::create([
                        'question_guid' => $question->guid,
                        'answer' => $questionData['answer_gemini'],
                        'source' => 'gemini',
                    ]);
                }
            }
    
            return ResponseController::getResponse(null, 200, 'Questions and answers saved successfully.');
        } catch (\Exception $e) {
            return ResponseController::getResponse(null, 500, "Error: " . $e->getMessage());
        }
    }



    public function generateData(Request $request)
    {
        set_time_limit(2000);

        $validator = Validator::make($request->all(), [
            'topic_guid' => 'required|string',
            'language' => 'required|string',
        ]);

        if ($validator->fails()) {
            return ResponseController::getResponse(null, 422, $validator->errors()->first());
        }

        $topic = Topic::where('guid', $request->get('topic_guid'))->first();
        if (!$topic || !$topic->file_path) {
            return ResponseController::getResponse(null, 404, "Topik atau file path tidak ditemukan.");
        }

        // Ambil data TF-IDF dari database berdasarkan topic_guid
        // Ambil data TF-IDF dari database berdasarkan topic_guid dan language
        $tfidfData = Gram::where('topic_guid', $topic->guid)
            ->where('language', $request->get('language')) // Tambahkan filter language
            ->get(['noun', 'gram_type', 'tfidf_val', 'cosine_val'])
            ->groupBy('gram_type')
            ->map(function ($group) {
                return $group->map(function ($item) {
                    return [
                        'N-gram' => $item->noun,
                        'TF-IDF Score' => $item->tfidf_val,
                        'Cosine Similarity' => $item->cosine_val,
                    ];
                });
            })->toArray();


        // Konversi data TF-IDF menjadi JSON
        $tfidfDataJson = json_encode($tfidfData);

        // Kirim permintaan ke API Python dengan tfidf_data sebagai parameter
        $generateResponse = Http::attach('pdf', Storage::disk('public')->get($topic->file_path), 'file.pdf')
            ->timeout(2000)
            ->post(env('FLASK_API_URL') . '/generate', [
                'language' => $request->get('language'),
                'tfidf_data' => $tfidfDataJson,
            ]);

            if ($generateResponse->failed()) {
                Log::error('Flask API Error', [
                    'status' => $generateResponse->status(),
                    'response' => $generateResponse->body(),
                    'request' => $request->all()
                ]);
                
                return ResponseController::getResponse(null, 500, "Flask API Error: " . $generateResponse->body());
            }

        $questionsData = $generateResponse->json();
        $questionsData = array_map(function ($question) {
            $question['guid'] = (string) Str::uuid(); // Tambahkan GUID unik
            return $question;
        }, $questionsData);


        return ResponseController::getResponse($questionsData, 200, 'Pertanyaan berhasil dihasilkan.');
        // return ResponseController::getResponse(null, 200, 'Pertanyaan berhasil dihasilkan.');
    }

    public function bulkUpdateThreshold(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'guids' => 'required|array',
            'threshold' => 'required|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $updatedCount = Question::whereIn('guid', $request->guids)
            ->update(['threshold' => $request->threshold]);

        return response()->json([
            'message' => "$updatedCount questions updated successfully.",
        ], 200);
    }

    public function bulkDeleteQuestions(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'guids' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $deletedCount = Question::whereIn('guid', $request->guids)->delete();

        return response()->json([
            'message' => "$deletedCount questions deleted successfully.",
        ], 200);
    }


    public function showDataLanguage($guid, $language, Request $request)
    {
        if ($request['user_id']) {
            $userId = $request['user_id'];
            $data = Question::with(['user_answer' => function ($query) use ($userId) {
                $query->where('user_id', '=', $userId);
            }])
                ->where('topic_guid', '=', $guid)
                ->where('language', '=', $language) // Filter berdasarkan bahasa
                ->orderByRaw('cast(page as unsigned) asc')
                ->get();
        } else {
            $data = Question::where('topic_guid', '=', $guid)
                ->where('language', '=', $language) // Filter berdasarkan bahasa
                ->where('user_id', '=', null)
                ->orderByRaw('cast(page as unsigned) asc')
                ->get();
        }

        

        if (!isset($data) || $data->isEmpty()) {
            return ResponseController::getResponse(null, 400, "Data not found");
        }

        $dataTable = DataTables::of($data)
            ->addIndexColumn()
            ->make(true);

        return $dataTable;
    }

    public function showData($guid, Request $request)
    {
        if ($request['user_id']) {
            $userId = $request['user_id'];
            $data = Question::with(['user_answer' => function ($query) use ($userId) {
                $query->where('user_id', '=', $userId);
            }])
                ->where('topic_guid', '=', $guid)
                ->orderByRaw('cast(page as unsigned) asc')
                ->get();
        } else {
            $data = Question::where('topic_guid', '=', $guid)
                ->orderByRaw('cast(page as unsigned) asc')
                ->get();
        }

        $dataTable = DataTables::of($data)
            ->addIndexColumn()
            ->make(true);

        return $dataTable;
    }

    public function getData($guid)
    {
        $data = Question::where('guid', '=', $guid)->first();

        return ResponseController::getResponse($data, 200, 'Success');
    }
    public function updateData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'guid' => 'required|string',
            'question_fix' => 'required|string',
            'answer_fix' => 'required|string',
            'threshold' => 'required|numeric',
            'category' => 'required|string|max:40',
            'language' => 'required|string|max:40',
            'page' => 'nullable|numeric|min:0',
            'weight' => 'nullable|numeric|min:0|max:100',
            'answer_pdf_guid' => 'nullable|string|exists:answer_pdf,guid',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $data = Question::where('guid', $request['guid'])->first();

        if (!$data) {
            return response()->json(['error' => 'Data not found'], 404);
        }

        $data->question_fix = $request['question_fix'];
        $data->answer_fix = $request['answer_fix'];
        $data->threshold = $request['threshold'];
        $data->category = $request['category'];
        $data->language = $request['language'];
        $data->weight = $request['weight'] ?? 1.0;
        $data->save();
        if ($request->has('answer_pdf_guid') && $request->answer_pdf_guid) {
            $updateData['answer_pdf_guid'] = $request->answer_pdf_guid;
        }

        return response()->json(['data' => $data, 'message' => 'Success'], 200);
    }

    public function setSelectedPdfAnswer(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'question_guid' => 'required|string|exists:question,guid',
                'answer_pdf_guid' => 'required|string|exists:answer_pdf,guid',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Verify the answer belongs to the question
            $answerExists = AnswerPDF::where('guid', $request->answer_pdf_guid)
                ->where('question_guid', $request->question_guid)
                ->exists();
                
            if (!$answerExists) {
                return response()->json([
                    'status' => false,
                    'message' => 'The selected answer does not belong to this question'
                ], 400);
            }
            
            Question::where('guid', $request->question_guid)
                ->update([
                    'answer_pdf_guid' => $request->answer_pdf_guid,
                    'updated_at' => Carbon::now()
                ]);
            
            return response()->json([
                'status' => true,
                'message' => 'Selected PDF answer updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update selected PDF answer: ' . $e->getMessage()
            ], 500);
        }
    }
    public function deleteData($guid)
    {
        

        $data = Question::where('guid', '=', $guid)->first();

        if (!isset($data)) {
            return ResponseController::getResponse(null, 400, "Data not found");
        }


        $data->delete();
        AnswerLLM::where('question_guid', $guid)->delete();
        AnswerPDF::where('question_guid', $guid)->delete();

        return ResponseController::getResponse(null, 200, 'Success');
    }
}
