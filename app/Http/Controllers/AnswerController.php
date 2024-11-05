<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Grade;
use App\Models\Question;
use App\Models\Topic;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

use Symfony\Component\HttpFoundation\Session\Session;

class AnswerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function insertData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'question_guid' => 'required|string|max:40',
            'user_id' => 'required|string|max:10',
            'topic_guid' => 'required|string|max:40',
        ], MessagesController::messages());

        if ($validator->fails()) {
            return ResponseController::getResponse(null, 422, $validator->errors()->first());
        }
        $time_end = Topic::where('guid', '=', $request['topic_guid'])->pluck('time_end');
        $currentDateTime = Carbon::now('Asia/Jakarta');
        if ($time_end < $currentDateTime) {
            $data = false;
            return ResponseController::getResponse($data, 200, 'Success');
        }
        $data = Answer::where('question_guid', '=', $request['question_guid'])
            ->where('user_id', '=', $request['user_id'])
            ->first();


        if (!isset($data)) {
            $data = Answer::create([
                'answer' => $request['answer'],
                'question_guid' => $request['question_guid'],
                'user_id' => $request['user_id'],
            ]);
        } else {
            $data->answer = $request['answer'];
            $data->save();
        }

        return ResponseController::getResponse($data, 200, 'Success');
    }
    public function updateData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'answer_guid' => 'required|string|max:40',
            'cossine_similarity' => 'required|string|max:10',
        ], MessagesController::messages());
        if ($validator->fails()) {
            return ResponseController::getResponse(null, 422, $validator->errors()->first());
        }
        $data = Answer::where('guid', '=', $request['answer_guid'])->first();
        $data->cossine_similarity = $request['cossine_similarity'];
        $data->save();
    }
    public function grade(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'question_guid' => 'required|string|max:40',
            'user_id' => 'required|string|max:10',
            'grade' => 'nullable|numeric',
            'topic_guid' => 'required|string|max:40',
        ], MessagesController::messages());

        if ($validator->fails()) {
            return ResponseController::getResponse(null, 422, $validator->errors()->first());
        }
        $data = Answer::where('question_guid', '=', $request['question_guid'])
            ->where('user_id', '=', $request['user_id'])
            ->first();

        if (!isset($data)) {
            $data = Answer::create([
                'answer' => $request['answer'],
                'question_guid' => $request['question_guid'],
                'grade' => $request['grade'],
                'user_id' => $request['user_id'],
            ]);
        } else {
            $data->grade = $request['grade'];
            $data->save();
        }
        $grade = Grade::where('topic_guid', '=', $request['topic_guid'])->where('user_id', '=', $request['user_id'])->first();
        $question = Question::where('topic_guid', '=', $request['topic_guid'])->pluck('guid');
        $totalGrade = Answer::whereIn('question_guid', $question)
            ->where('user_id', '=', $request['user_id'])
            ->sum('grade');
        $grade->grade = $totalGrade;
        $grade->save();
        return ResponseController::getResponse($data, 200, 'Success');
    }
    public function getData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'question_guid' => 'required|string|max:40',
            'user_id' => 'required|string|max:10',
        ], MessagesController::messages());

        if ($validator->fails()) {
            return ResponseController::getResponse(null, 422, $validator->errors()->first());
        }

        $data = Answer::where('question_guid', '=', $request['question_guid'])
            ->where('user_id', '=', $request['user_id'])
            ->pluck('answer');

        if (!isset($data)) {
            $data = "";
        }

        return ResponseController::getResponse($data, 200, 'Success');
    }
    public function getDataByUser($guid, $id)
    {

        $data = Question::where('topic_guid', '=', $guid)
            ->with(['user_answer' => function ($query) use ($id) {
                $query->where('user_id', '=', $id);
            }])
            ->get();

        if (!isset($data)) {
            return ResponseController::getResponse(null, 400, "Data not found");
        }

        $dataTable = DataTables::of($data)
            ->addIndexColumn()
            ->make(true);

        return $dataTable;
    }
}
