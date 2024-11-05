<?php

namespace App\Http\Controllers;

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
        $data = UserCourse::where('user_id', '=', $id)->with('user', 'course')->get();

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
