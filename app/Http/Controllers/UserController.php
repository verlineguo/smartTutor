<?php

namespace App\Http\Controllers;

use App\Models\Assistant;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\UserCourse;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class UserController extends Controller
{
    public function index()
    {
        $user = User::with('role')
            ->where('id', auth('api')->user()->id)
            ->first();

        return ResponseController::getResponse($user, 200, 'Get Profile User Success');
    }
    public function filterUserCourse(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:10',
        ], MessagesController::messages());

        if ($validator->fails()) {
            return ResponseController::getResponse(null, 422, $validator->errors()->first());
        }

        $userIds = UserCourse::where('course_code', '=', $request['code'])->pluck('user_id');

        $user = User::whereNotIn('id', $userIds)
            ->where('role_guid', '<>', '120014de-1d48-4947-b801-afe701bb19b8')
            ->get();

        return ResponseController::getResponse($user, 200, 'Get User Success');
    }
    public function filterAssistant(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:10',
        ], MessagesController::messages());

        if ($validator->fails()) {
            return ResponseController::getResponse(null, 422, $validator->errors()->first());
        }

        $userCourse = UserCourse::where('course_code', '=', $request['code'])->pluck('user_id');
        $assistant = Assistant::where('course_code', '=', $request['code'])->pluck('user_id');

        $user = User::whereNotIn('id', $userCourse)
            ->whereNotIn('id', $assistant)
            ->where('role_guid', '<>', '120014de-1d48-4947-b801-afe701bb19b8')
            ->where('role_guid', '<>', 'c6a51300-8153-4f31-933c-dc7cd0fb7d6f')
            ->get();

        return ResponseController::getResponse($user, 200, 'Get User Success');
    }
    public function insertData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|string|max:10',
            'username' => 'required|string',
            'name' => 'required|string',
            'email' => 'required|string',
            'role_guid' => 'required|string|max:40',
        ], MessagesController::messages());

        if ($validator->fails()) {
            return ResponseController::getResponse(null, 422, $validator->errors()->first());
        }
        $data = User::create([
            'id' => $request['id'],
            'username' => $request['username'],
            'name' => $request['name'],
            'email' => $request['email'],
            'password' => Hash::make('asd123'),
            'role_guid' => $request['role_guid'],
        ]);

        return ResponseController::getResponse($data, 200, 'Success');
    }
    public function google(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|string',
            'name' => 'required|string',
            'email' => 'required|string|email',
        ], MessagesController::messages());

        if ($validator->fails()) {
            return ResponseController::getResponse(null, 422, $validator->errors()->first());
        }

        // Cek apakah user dengan ID sudah ada
        $user = User::where('id', $request->id)
            ->orWhere('email', $request->email)
            ->first();


        if (!$user) {
            // Jika belum terdaftar, buat user baru
            $user = User::create([
                'id' => $request->id,
                'name' => $request->name,
                'username' => $request->name,
                'email' => $request->email,
                'role_guid' => 'dc6c6789-122f-40be-9751-f5be0a051b0e'
            ]);
            $user = User::where('id', $request->id)
                ->first();
        }

        // Buat payload untuk JWT token
        $payloadable = [
            'user_id' => $user->id,
            'name' => $user->name,
            'username' => $user->username ?? null, // Tambahkan null jika username bisa kosong
            'email' => $user->email,
            'role_guid' => $user->role_guid,
        ];

        // Buat token JWT
        $token = JWTAuth::fromUser($user, $payloadable);

        // Kembalikan respons dengan token dan data user
        return ResponseController::getResponse([
            'user' => $user,
            'token' => $token
        ], 200, 'Success');
    }

    public function uploadCSV(Request $request)
    {
        if ($request->hasFile('csv')) {
            $file = $request->file('csv');

            $data = array_map(function ($row) {
                return str_getcsv($row);
            }, file($file));

            $header = array_shift($data);

            $data = array_map(function ($row) use ($header) {
                return array_combine($header, $row);
            }, $data);

            $dataTable = DataTables::of($data)
                ->addIndexColumn()
                ->make(true);

            return $dataTable;
        }

        return response()->json(['message' => 'Tidak ada file CSV yang diunggah.'], 400);
    }

    public function showData()
    {
        $data = User::with('role')->get();
        if (!isset($data)) {
            return ResponseController::getResponse(null, 400, "Data not found");
        }
        $dataTable = DataTables::of($data)
            ->addIndexColumn()
            ->make(true);

        return $dataTable;
    }
    public function checkAssistant(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|string|max:10',
        ], MessagesController::messages());
        if ($validator->fails()) {
            return ResponseController::getResponse(null, 422, $validator->errors()->first());
        }
        $data = Assistant::where('user_id', '=', $request['user_id'])
            ->get();

        if ($data->count() > 0) {
            $response = true;
        }
        $response = false;

        return ResponseController::getResponse($response, 200, 'Success');
    }
    public function getData($id)
    {
        /// GET DATA
        $data = User::with('role')
            ->where('id', '=', $id)
            ->first();

        if (!isset($data)) {
            return ResponseController::getResponse(null, 400, "Data not found");
        }

        return ResponseController::getResponse($data, 200, 'Success');
    }
    public function updateData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|string|max:10',
            'username' => 'required|string',
            'name' => 'required|string',
            'email' => 'required|string',
            'role_guid' => 'required|string|max:40',
        ], MessagesController::messages());

        if ($validator->fails()) {
            return ResponseController::getResponse(null, 422, $validator->errors()->first());
        }

        $data = User::where('id', '=', $request['id'])->first();

        if (!isset($data)) {
            return ResponseController::getResponse(null, 400, "Data not found");
        }
        /// UPDATE DATA
        $data->id = $request['id'];
        $data->name = $request['name'];
        $data->username = $request['username'];
        $data->email = $request['email'];
        $data->role_guid = $request['role_guid'];
        $data->save();

        return ResponseController::getResponse($data, 200, 'Success');
    }
    public function deleteData($id)
    {
        $data = User::where('id', '=', $id)->first();

        if (!isset($data)) {
            return ResponseController::getResponse(null, 400, "Data not found");
        }

        $data->delete();

        return ResponseController::getResponse(null, 200, 'Success');
    }
}
