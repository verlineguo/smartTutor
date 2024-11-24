<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Grade;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserCourse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Session\Session;
use Yajra\DataTables\Facades\DataTables;

class TopicController extends Controller
{

    public function insertData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'description' => 'required|string',
            'max_attempt_gpt' => 'required|int',
            'course_code' => 'required|string|max:10',
            'time_start' => 'required|date_format:Y-m-d\TH:i',
            'time_end' => 'required|date_format:Y-m-d\TH:i|after:' . $request['time_start'],
        ], MessagesController::messages());

        if ($validator->fails()) {
            return ResponseController::getResponse(null, 422, $validator->errors()->first());
        }
        $data = Topic::create([
            'name' => $request['name'],
            'description' => $request['description'],
            'course_code' => $request['course_code'],
            'max_attempt_gpt' => $request['max_attempt_gpt'],
            'time_start' => $request['time_start'],
            'time_end' => $request['time_end'],
        ]);

        return ResponseController::getResponse($data, 200, 'Success');
    }

    public function showData()
    {
        $data = Topic::all();
        if (!isset($data)) {
            return ResponseController::getResponse(null, 400, "Data not found");
        }
        $currentDateTime = Carbon::now('Asia/Jakarta');
        foreach ($data as $topic) {
            if ($topic->time_end < $currentDateTime) {
                $topic->deadline = false;
            } else {
                $topic->deadline = true;
            }
        }

        $dataTable = DataTables::of($data)
            ->addIndexColumn()
            ->make(true);

        return $dataTable;
    }
    public function topicByCourse(Request $request)
    {
        $data = Topic::where('course_code', '=', $request['code'])
            ->with('course')
            ->with(['grade' => function ($query) use ($request) {
                $query->where('user_id', '=', $request['user_id']);
            }])
            ->get();
        $currentDateTime = Carbon::now('Asia/Jakarta');
        foreach ($data as $topic) {
            if ($topic->time_end < $currentDateTime) {
                $topic->deadline = false;
            } else {
                $topic->deadline = true;
            }
        }
        $dataTable = DataTables::of($data)
            ->addIndexColumn()
            ->make(true);

        return $dataTable;
    }
    public function topicByDeadline(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|string',
        ], MessagesController::messages());
        if ($validator->fails()) {
            return ResponseController::getResponse(null, 422, $validator->errors()->first());
        }
        $id = $request['id'];
        // Ambil role_name dari user
        $user = User::with('role')->where('id', $id)->first();

        // Periksa apakah user memiliki role dan apakah role_name adalah 'student'
        if ($user && $user->role->role_name === 'student') {
            $publicCourses = Course::where('status', 'public')->pluck('code')->toArray(); // Semua kursus public
            $userCourses = UserCourse::where('user_id', '=', $id)->pluck('course_code')->toArray(); // Kursus yang sudah diikuti
            $unenrolledCourses = array_diff($publicCourses, $userCourses); // Kursus yang belum diikuti

            foreach ($unenrolledCourses as $courseCode) {
                UserCourse::create([
                    'user_id' => $id,
                    'course_code' => $courseCode,
                ]);
            }
        }

        $course = UserCourse::where('user_id', '=', $id)->pluck('course_code');
        $currentDateTime = Carbon::now('Asia/Jakarta');
        $topic = Topic::with('course')
            ->with(['grade' => function ($query) use ($request) {
                $query->where('user_id', '=', $request['id']);
            }])
            ->where('time_end', '>', $currentDateTime)
            ->where('time_start', '<=', $currentDateTime)
            ->whereIn("course_code", $course)
            ->get();
        $dataTable = DataTables::of($topic)
            ->addIndexColumn()
            ->make(true);

        return $dataTable;
    }
    public function checkSubmit(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|string',
            'topic_guid' => 'required|string|max:36',

        ], MessagesController::messages());
        if ($validator->fails()) {
            return ResponseController::getResponse(null, 422, $validator->errors()->first());
        }
        $data = Grade::where('user_id', '=', $request['user_id'])
            ->where('topic_guid', '=', $request['topic_guid'])
            ->first();

        if ($data->count() > 0) {
            $response = true;
        }
        $response = false;

        return ResponseController::getResponse($response, 200, 'Success');
    }
    public function checkDeadline($guid)
    {
        $data = Topic::where('guid', '=', $guid)->first();
        $currentDateTime = Carbon::now('Asia/Jakarta');
        if ($data->time_end < $currentDateTime) {
            $data = false;
        }
        return ResponseController::getResponse($data, 200, 'Success');
    }
    public function getData($guid)
    {
        $data = Topic::where('guid', '=', $guid)->with('question')->first();
        $data->totalWeight = $data->question->sum('weight');
        return ResponseController::getResponse($data, 200, 'Success');
    }
    public function updateData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'guid' => 'required|string|max:36',
            'description' => 'required|string',
            'course_code' => 'required|string|max:10',
            'max_attempt_gpt' => 'required|int',
            'time_start' => 'required|date_format:Y-m-d\TH:i',
            'time_end' => 'required|date_format:Y-m-d\TH:i|after:' . $request['time_start'],
        ], MessagesController::messages());

        if ($validator->fails()) {
            return ResponseController::getResponse(null, 422, $validator->errors()->first());
        }

        $data = Topic::where('guid', '=', $request['guid'])->first();

        if (!isset($data)) {
            return ResponseController::getResponse(null, 400, "Data not found");
        }
        /// UPDATE DATA
        $data->name = $request['name'];
        $data->description = $request['description'];
        $data->course_code = $request['course_code'];
        $data->max_attempt_gpt = $request['max_attempt_gpt'];
        $data->time_start = $request['time_start'];
        $data->time_end = $request['time_end'];
        $data->save();

        return ResponseController::getResponse($data, 200, 'Success');
    }
    public function uploadFile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'topic_guid' => 'required|string|max:36', // Identifier for the topic
            'file' => 'required|file|mimes:pdf|max:10048', // Only PDF files with a max size of 10MB
            'language' => 'required|string|max:10', // File language is required
        ], MessagesController::messages());

        if ($validator->fails()) {
            return ResponseController::getResponse(null, 422, $validator->errors()->first());
        }

        // Find topic by topic_guid
        $topic = Topic::where('guid', $request->input('topic_guid'))->first();
        if (!$topic) {
            return ResponseController::getResponse(null, 400, 'Topic not found');
        }

        // Delete existing file in storage if exists
        if ($topic->file_path) {
            $existingFilePath = storage_path('app/public/' . $topic->file_path);
            if (file_exists($existingFilePath)) {
                unlink($existingFilePath); // Delete the old file from storage
            }
        }

        // Save new file
        if ($request->hasFile('file')) {
            $file = $request->file('file');

            // Get original filename with a unique identifier for uniqueness
            $originalName = $file->getClientOriginalName();
            $uniqueFileName = uniqid() . '_' . $originalName;

            // Save file to 'uploads/topics' with the original name
            $filePath = $file->storeAs('uploads/topics', $uniqueFileName, 'public');

            // Update database with new file path and language
            $topic->file_path = $filePath;
            $topic->file_language = $request->input('language');

            // Update translation metadata with original file
            $translationMetadata = json_decode($topic->translation_metadata, true) ?? [];
            $translationMetadata[] = [
                'language' => $request->input('language'),
                'path' => $filePath
            ];
            $topic->translation_metadata = json_encode($translationMetadata);

            $topic->save();

            return ResponseController::getResponse(['file_path' => $filePath], 200, 'PDF file uploaded and saved successfully');
        }

        return ResponseController::getResponse(null, 400, 'Failed to upload file');
    }
    public function deleteFile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'topic_guid' => 'required|string|max:36', // Verifikasi topic_guid
        ], MessagesController::messages());

        if ($validator->fails()) {
            return ResponseController::getResponse(null, 422, $validator->errors()->first());
        }

        // Cari topik berdasarkan topic_guid
        $topic = Topic::where('guid', $request->input('topic_guid'))->first();
        if (!$topic) {
            return ResponseController::getResponse(null, 400, 'Topic tidak ditemukan');
        }

        $existingFilePath = $topic->file_path; // Ambil path file utama dari database
        $translationMetadata = json_decode($topic->translation_metadata, true) ?? []; // Decode metadata

        // Hapus file utama jika ada
        if ($existingFilePath) {
            $fullFilePath = storage_path('app/public/' . $existingFilePath);

            // Cek apakah file utama ada di storage lokal
            if (file_exists($fullFilePath)) {
                unlink($fullFilePath); // Hapus file utama dari storage lokal
            }
        }

        // Hapus semua file dalam translation_metadata
        foreach ($translationMetadata as $metadata) {
            if (isset($metadata['path'])) {
                $translationFilePath = storage_path('app/public/' . $metadata['path']);

                // Cek apakah file metadata ada di storage lokal
                if (file_exists($translationFilePath)) {
                    unlink($translationFilePath); // Hapus file metadata dari storage lokal
                }
            }
        }

        // Reset file_path dan translation_metadata di database
        $topic->file_path = null;
        $topic->translation_metadata = json_encode([]);
        $topic->save();

        return ResponseController::getResponse(null, 200, 'File dan terjemahan berhasil dihapus');
    }

    public function deleteData($guid)
    {
        $data = Topic::where('guid', '=', $guid)->first();

        if (!isset($data)) {
            return ResponseController::getResponse(null, 400, "Data not found");
        }

        // Hapus semua file dari storage yang terkait dengan metadata terjemahan
        $translationMetadata = json_decode($data->translation_metadata, true) ?? [];
        foreach ($translationMetadata as $metadata) {
            $filePath = storage_path('app/public/' . $metadata['path']);
            if (file_exists($filePath)) {
                unlink($filePath); // Hapus file dari storage
            }
        }

        // Hapus file utama
        if ($data->file_path) {
            $filePath = storage_path('app/public/' . $data->file_path);
            if (file_exists($filePath)) {
                unlink($filePath); // Hapus file utama
            }
        }

        $data->delete();

        return ResponseController::getResponse(null, 200, 'Topic and all associated files deleted successfully');
    }
}
