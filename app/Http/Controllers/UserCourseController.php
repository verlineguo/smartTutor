<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\User;
use App\Models\UserCourse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Session\Session;
use Yajra\DataTables\Facades\DataTables;

class UserCourseController extends Controller
{

    public function insertData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'course_code' => 'required|string|max:10',
        ], MessagesController::messages());

        if ($validator->fails()) {
            return ResponseController::getResponse(null, 422, $validator->errors()->first());
        }

        //USER
        $users = json_decode("[]", true);
        $user = null;
        if (!empty($request['user'])) {
            foreach ($request['user'] as $key => $value) {
                array_push($users, $value);
            }

            $user = User::find($users);
            if (count($request['user']) != count($user)) {
                return ResponseController::getResponse(null, 400, "Invalid author parameter");
            }
        }

        foreach ($users as $user) {
            $data = UserCourse::create([
                'user_id' => $user,
                'course_code' => $request['course_code'],
            ]);
        }

        return ResponseController::getResponse($data, 200, 'Success');
    }

    public function getDataByUser($id)
    {
        // Retrieve the role of the currently authenticated user
        $role_name = auth('api')->user()->role->role_name;


        if ($role_name === 'admin') {
            // If the user is an admin, retrieve all courses without filtering by user ID
            $data = Course::all();
        } else {
            // If the user is not an admin, retrieve only courses associated with the specific user ID
            $data = Course::whereHas('user_course', function ($query) use ($id) {
                $query->where('user_id', $id);
            })->get();
        }

        // Return the response
        return ResponseController::getResponse($data, 200, 'Success');
    }

    public function getUserByCourse($code)
    {
        $data = UserCourse::where('course_code', '=', $code)
            ->with('user')
            ->get();
        $dataTable = DataTables::of($data)
            ->addIndexColumn()
            ->make(true);

        return $dataTable;
    }
    public function deleteData($guid)
    {
        $data = UserCourse::where('guid', '=', $guid)->first();
        $data->delete();

        return ResponseController::getResponse(null, 200, 'Success');
    }
}
