<?php

namespace App\Http\Controllers;

use App\Models\Grade;
use App\Models\Question;
use App\Models\Role;
use App\Models\UserCourse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;



class GradeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function getData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'topic_guid' => 'required|string|max:50',
            'user_id' => 'required|string|max:10',
        ], MessagesController::messages());

        if ($validator->fails()) {
            return ResponseController::getResponse(null, 422, $validator->errors()->first());
        }
        $data = Grade::where('topic_guid', '=', $request['topic_guid'])->where('user_id', '=', $request['user_id'])->first();

        return ResponseController::getResponse($data, 200, 'Success');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function getDataByTopic($code, $guid)
    {
        $role = Role::where('role_name', '=', 'student')->pluck('guid');

        // Ambil data user dengan relasi grade dan status dari chathistories
        $data = UserCourse::whereHas('user', function ($query) use ($guid, $role) {
            $query->where('role_guid', '=', $role);
        })
            ->with(['user' => function ($query) use ($guid) {
                $query->with(['grade' => function ($query) use ($guid) {
                    $query->where('topic_guid', '=', $guid);
                }])
                    ->with(['chathistories' => function ($query) use ($guid) {
                        $query->where('topic_guid', '=', $guid)->orderBy('created_at', 'desc');
                    }]);
            }])
            ->where('course_code', '=', $code)
            ->get();
        $data = UserCourse::whereHas('user', function ($query) use ($guid, $role) {
            $query->where('role_guid', '=', $role);
        })
            ->with(['user' => function ($query) use ($guid) {
                $query->with(['grade' => function ($query) use ($guid) {
                    $query->where('topic_guid', '=', $guid);
                }])
                    ->with(['chathistories' => function ($query) use ($guid) {
                        $query->where('topic_guid', '=', $guid)
                            ->orderBy('created_at', 'desc');
                    }])
                    ->with(['chathistories.question' => function ($query) {
                        $query->select('user_id', 'language', 'topic_guid', 'page');
                    }]); // Relasi ke pertanyaan terkait
            }])
            ->where('course_code', '=', $code)
            ->get();

        // Ambil data untuk menentukan status dan grade
        $processedData = $data->map(function ($item) use ($guid) {
            $user = $item->user;

            // Ambil record terakhir dari chathistories
            $lastChatHistory = $user->chathistories()->latest('created_at')->first();
            $language = $lastChatHistory && $lastChatHistory->question ? $lastChatHistory->question->language : null;

            // Cari page terakhir berdasarkan topic_guid dan language
            $highestPage = null;
            if ($language) {
                $highestPage = Question::where('topic_guid', $guid)
                    ->where('language', $language)
                    ->max('page');
            }

            // Tentukan status
            $status = 'not submitted';
            if ($lastChatHistory) {
                $lastPage = $lastChatHistory->page;
                $lastSender = $lastChatHistory->sender;

                if ($lastPage == $highestPage && $lastSender === 'cosine') {
                    $status = 'submitted';
                }
            }

            // Hitung rata-rata cosine_similarity dari chathistories dengan sender 'user'
            $userCosineSimilarity = $user->chathistories
                ->where('sender', 'user') // Filter hanya sender 'user'
                ->pluck('cosine_similarity') // Ambil cosine_similarity
                ->filter(); // Hilangkan null values

            $grade = $userCosineSimilarity->count() > 0
                ? round($userCosineSimilarity->avg(), 2) // Rata-rata dan dibulatkan ke 2 desimal
                : null;

            return [
                'user_id' => $user->id,
                'name' => $user->name,
                'language' => $language,
                'last_page' => $lastChatHistory ? $lastChatHistory->page : null,
                'last_sender' => $lastChatHistory ? $lastChatHistory->sender : null,
                'highest_page' => $highestPage,
                'status' => $status,
                'grade' => $grade, // Tambahkan grade ke hasil akhir
            ];
        });

        // Return data processedData dalam format DataTable
        if ($processedData->isEmpty()) {
            return ResponseController::getResponse(null, 400, "Data not found");
        }

        $dataTable = DataTables::of($processedData)
            ->addIndexColumn()
            ->make(true);

        return $dataTable;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function updateData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'topic_guid' => 'required|string|max:50',
            'grade' => 'required|numeric',
            'user_id' => 'required|string|max:10',
        ], MessagesController::messages());

        if ($validator->fails()) {
            return ResponseController::getResponse(null, 422, $validator->errors()->first());
        }

        $data = Grade::where('topic_guid', '=', $request['topic_guid'])->where('user_id', '=', $request['user_id'])->first();
        if (isset($data)) {
            $data->grade = $request['grade'];
            $data->save();
        } else {
            $data = Grade::create([
                'topic_guid' => $request['topic_guid'],
                'user_id' => $request['user_id'],
                'grade' => $request['grade'],
            ]);
        }
        return ResponseController::getResponse($data, 200, 'Success');
    }
    public function insertData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'topic_guid' => 'required|string|max:50',
            'user_id' => 'required|string|max:10',
        ], MessagesController::messages());

        if ($validator->fails()) {
            return ResponseController::getResponse(null, 422, $validator->errors()->first());
        }

        $data = Grade::create([
            'topic_guid' => $request['topic_guid'],
            'user_id' => $request['user_id'],
        ]);
        return ResponseController::getResponse($data, 200, 'Success');
    }
}
