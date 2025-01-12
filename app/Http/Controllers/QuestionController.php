<?php

namespace App\Http\Controllers;

use App\Models\Gram;
use App\Models\PageNoun;
use App\Models\Question;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\URL;
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
        $page = Http::attach('pdf', file_get_contents('/home/u486571172/domains/smart-tutor-fit.com/smart-tutor-backend/storage/app/public/file/' . $name), 'file.pdf', ['Content-Type' => 'pdf'])
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
    // public function generateData(Request $request)
    // {

    //     if ($request->get('path') != "") {

    //         $Rawdata = Http::timeout(300)
    //             ->attach('pdf', file_get_contents('/home/u486571172/domains/smart-tutor-fit.com/smart-tutor-backend/storage/app/public/file/' . $request->get('name')), 'file.pdf', ['Content-Type' => 'pdf'])
    //             ->post(
    //                 'http://91.108.110.58/generate',
    //                 [
    //                     'language' => $request->get('language'),
    //                     'page' => $request->get('page')
    //                 ]
    //             );
    //         if (!isset($Rawdata[0])) {
    //             return 0;
    //         }
    //         $data = json_decode($Rawdata, true);
    //         if (isset($data['pertanyaan'])) {
    //             return $data['pertanyaan'];
    //         } else {
    //             return false;
    //         }
    //         // Storage::delete($request->get('path') . $request->get('name'));
    //     } else if ($request->get('noun') != "") {

    //         $data = Http::withHeaders([
    //             'Content-Type' => "application/json"
    //         ])->post(
    //             "http://91.108.110.58/generate",
    //             [
    //                 'topic' => $request->get('noun'),
    //                 'language' => $request->get('language'),
    //             ]
    //         );
    //     }

    //     $data = json_decode($data, true);
    //     $dataTable = DataTables::of($data['pertanyaan'])
    //         ->addIndexColumn()
    //         ->make(true);

    //     return $dataTable;
    // }
    public function convertDatatable(Request $request)
    {
        $dataTable = DataTables::of($request->get('data'))
            ->addIndexColumn()
            ->make(true);
        Storage::delete($request['path'] . $request['name']);
        return $dataTable;
    }


    public function insertData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'question_ai' => 'required|string',
            'answer_ai' => 'required|string',
            'question_fix' => 'required|string',
            'answer_fix' => 'required|string',
            'threshold' => 'required|numeric',
            'category' => 'required|string|max:40',
            'language' => 'required|string|max:40',
            'topic_guid' => 'required|string|max:40',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $data = Question::create([
            'question_ai' => $request['question_ai'],
            'answer_ai' => $request['answer_ai'],
            'question_fix' => $request['question_fix'],
            'answer_fix' => $request['answer_fix'],
            'category' => $request['category'],
            'weight' => 1.0,
            'threshold' => $request['threshold'],
            'topic_guid' => $request['topic_guid'],
            'language' => $request['language'],
        ]);

        return response()->json(['data' => $data, 'message' => 'Success'], 200);
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



    public function saveQuestions(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'topic_guid' => 'required|string',
            'questions' => 'required|array',
            'questions.*.question' => 'required|string',
            'questions.*.answer' => 'required|string',
            'questions.*.category' => 'required|string',
            'questions.*.language' => 'required|string',
        ]);

        if ($validator->fails()) {
            return ResponseController::getResponse(null, 422, $validator->errors()->first());
        }

        foreach ($request->questions as $questionData) {
            Question::create([
                'question_ai' => $questionData['question'],
                'answer_ai' => $questionData['answer'],
                'question_fix' => $questionData['question'],
                'answer_fix' => $questionData['answer'],
                'threshold' => $questionData['threshold'], // Default value
                'weight' => 1.0, // Default value
                'category' => $questionData['category'],
                'topic_guid' => $request->topic_guid,
                'language' => $questionData['language'],
                'question_nouns' => json_encode($questionData['question_nouns'] ?? []),
                'page' => $questionData['page_number'] ?? null,
                'cossine_similarity' => $questionData['cosine_q&d'] ?? 0.0,
            ]);
        }

        return ResponseController::getResponse(null, 200, 'Questions saved successfully.');
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
            return ResponseController::getResponse(null, 500, "Kesalahan saat menghasilkan pertanyaan.");
        }

        $questionsData = $generateResponse->json();
        // foreach ($questionsData as $questionData) {
        //     Question::create([
        //         'question_ai' => (string) ($questionData['question'] ?? ''), // Cast to string
        //         'answer_ai' => (string) ($questionData['answer'] ?? ''), // Cast to string
        //         'question_fix' => (string) ($questionData['question'] ?? ''), // Cast to string
        //         'answer_fix' => (string) ($questionData['answer'] ?? ''), // Cast to string
        //         'threshold' => (float) 70.0, // Cast to float
        //         'weight' => (float) 1.0, // Cast to float
        //         'category' => (string) ($questionData['category'] ?? 'general'), // Cast to string
        //         'topic_guid' => (string) $request->get('topic_guid'), // Cast to string
        //         'language' => (string) $request->get('language'), // Cast to string
        //         'question_nouns' => json_encode($questionData['question_nouns'] ?? []),
        //         'page' => isset($questionData['page_number']) ? (int) $questionData['page_number'] : null, // Cast to integer
        //         'cossine_similarity' => (float) ($questionData['cosine_q&d'] ?? 0.0), // Cast to float
        //     ]);
        // }

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

        // if (!isset($data) || $data->isEmpty()) {
        //     return ResponseController::getResponse(null, 400, "Data not found");
        // }

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
        $data->save();

        return response()->json(['data' => $data, 'message' => 'Success'], 200);
    }
    public function deleteData($guid)
    {
        $data = Question::where('guid', '=', $guid)->first();

        if (!isset($data)) {
            return ResponseController::getResponse(null, 400, "Data not found");
        }

        $data->delete();

        return ResponseController::getResponse(null, 200, 'Success');
    }
}
