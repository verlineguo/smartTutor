<?php

namespace App\Http\Controllers;

use App\Models\Assistant;
use App\Models\Course;
use App\Models\UserCourse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Session\Session;
use Yajra\DataTables\Facades\DataTables;

class CourseController extends Controller
{

    public function insertData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:10',
            'user_id' => 'required|string|max:10',
            'description' => 'required|string',
            'status' => 'required|string',
        ], MessagesController::messages());

        if ($validator->fails()) {
            return ResponseController::getResponse(null, 422, $validator->errors()->first());
        }
        $data = Course::create([
            'code' => $request['code'],
            'name' => $request['name'],
            'description' => $request['description'],
            'status' => $request['status'],
        ]);
        $data = UserCourse::create([
            'course_code' => $request['code'],
            'user_id' => $request['user_id'],
        ]);
        return ResponseController::getResponse($data, 200, 'Success');
    }

    public function showData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|string',
            'role_name' => 'required|string|max:10',
        ], MessagesController::messages());

        if ($validator->fails()) {
            return ResponseController::getResponse(null, 422, $validator->errors()->first());
        }

        $userId = $request['user_id'];
        $roleName = $request['role_name'];

        if ($roleName == "admin") {
            // Jika admin, tampilkan semua data
            $data = Course::all();
        } else if ($roleName == "student") {
            // Ambil semua course public
            $publicCourses = Course::where('status', 'public')->pluck('code')->toArray();

            // Ambil daftar course_code yang sudah diikuti oleh user
            $userCourses = UserCourse::where('user_id', '=', $userId)->pluck('course_code')->toArray();

            // Cari kursus public yang belum di-enroll
            $unenrolledCourses = array_diff($publicCourses, $userCourses);

            // Enroll user ke setiap kursus public yang belum diikuti
            foreach ($unenrolledCourses as $courseCode) {
                UserCourse::create([
                    'user_id' => $userId,
                    'course_code' => $courseCode,
                ]);
            }

            // Ambil semua kursus yang sudah di-enroll oleh user
            $data = Course::whereIn('code', $userCourses)->orWhereIn('code', $unenrolledCourses)->get();
        } else {
            // Jika bukan admin atau student, cek berdasarkan user_id + assistant role
            $userCourse = UserCourse::where('user_id', '=', $userId)->pluck('course_code')->toArray();
            $assistant = Assistant::where('user_id', '=', $userId)->pluck('course_code')->toArray();

            $combinedCodes = array_merge($userCourse, $assistant);

            $data = Course::whereIn('code', $combinedCodes)->get();
        }

        if (!isset($data)) {
            return ResponseController::getResponse(null, 400, "Data not found");
        }

        $dataTable = DataTables::of($data)
            ->addIndexColumn()
            ->make(true);

        return $dataTable;
    }

    public function getData($code)
    {
        $data = Course::where('code', '=', $code)->first();

        return ResponseController::getResponse($data, 200, 'Success');
    }
    public function updateData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'code_old' => 'required|string|max:10',
            'code_new' => 'required|string|max:10',
            'description' => 'required|string',
            'status' => 'required|string',
        ], MessagesController::messages());

        if ($validator->fails()) {
            return ResponseController::getResponse(null, 422, $validator->errors()->first());
        }

        $data = Course::where('code', '=', $request['code_old'])->first();

        if (!isset($data)) {
            return ResponseController::getResponse(null, 400, "Data not found");
        }
        /// UPDATE DATA
        $data->code = $request['code_new'];
        $data->name = $request['name'];
        $data->description = $request['description'];
        $data->status = $request['status'];
        $data->save();

        return ResponseController::getResponse($data, 200, 'Success');
    }
    public function deleteData($code)
    {
        $data = Course::where('code', '=', $code)->first();

        if (!isset($data)) {
            return ResponseController::getResponse(null, 400, "Data not found");
        }

        $data->delete();

        return ResponseController::getResponse(null, 200, 'Success');
    }
}
