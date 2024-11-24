<?php

use App\Http\Controllers\AnswerController;
use App\Http\Controllers\AssistantController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\ChatHistoryController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\JawabanController;
use App\Http\Controllers\MataKuliahController;
use App\Http\Controllers\MateriController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SoalController;
use App\Http\Controllers\TopicController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserCourseController;
use App\Http\Controllers\UserMataKuliahController;
use App\Models\ChatHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();

// });

$version = "v1/";
$url = $version;

Route::group([
    'prefix' => $url . 'auth',
    'middleware' => 'api',
], function ($router) {
    $router->post('/login', [AuthController::class, 'login'])->name('login');
    $router->post('/google', [UserController::class, 'google']);
});

/**
 * FORGOT PASSWORD
 */
Route::group([
    'prefix' => $url . 'forgot-password',
    'middleware' => 'api',
], function ($router) {
    $router->post('/generate-otp', [OtpController::class, 'generateOtp']);
    $router->post('/validate-otp', [OtpController::class, 'validateOtp']);
    $router->post('/reset-password', [PasswordController::class, 'resetPassword']);
});

Route::group([
    'prefix' => $url . 'auth',
    'middleware' => 'jwt.verify',
], function ($router) {
    $router->post('/logout', [AuthController::class, 'logout']);
});

/**
 * PROFILE
 */
Route::group([
    'prefix' => $url . 'user',
    'middleware' => 'jwt.verify'
], function ($router) {
    $router->get('/self', [UserController::class, 'index']);
    // $router->put('/update', [ProfileController::class, 'updateUser']);
    $router->put('/change-password', [PasswordController::class, 'changePassword']);
    // $router->put('/update-fcm-token', [FcmController::class, 'updateFcmToken']);
    $router->get('/', [UserController::class, 'showData']);
    $router->get('/{id}', [UserController::class, 'getData']);
    $router->put('/', [UserController::class, 'updateData']);
    $router->delete('/{id}', [UserController::class, 'deleteData']);
    $router->post('/', [UserController::class, 'insertData']);
    $router->post('/upload-file', [UserController::class, 'uploadCSV']);
    $router->post('/user-course', [UserController::class, 'filterUserCourse']);
    $router->post('/assistant', [UserController::class, 'filterAssistant']);
    $router->post('/google', [UserController::class, 'google']);
    $router->post('/check/role', [UserController::class, 'checkAssistant']);
});

/**
 * QUESTION
 */
Route::group([
    'prefix' => $url . 'question',
    'middleware' => 'jwt.verify'
], function ($router) {
    $router->post('/upload-file', [QuestionController::class, 'uploadFile']);
    $router->get('/generate', [QuestionController::class, 'generateData']);
    $router->get('/check-cossine', [QuestionController::class, 'checkCossine']);
    $router->post('/translate', [QuestionController::class, 'translateDocument']);
    $router->post('/tfidf', [QuestionController::class, 'calculateTfidf']);
    $router->post('/save', [QuestionController::class, 'saveQuestions']);
    $router->post('/generate', [QuestionController::class, 'generateData']);
    $router->post('/convert/datatable', [QuestionController::class, 'convertDatatable']);
    $router->post('/bulk-update-threshold', [QuestionController::class, 'bulkUpdateThreshold'])->name('bulkUpdateThreshold');
    $router->post('/bulk-delete', [QuestionController::class, 'bulkDeleteQuestions'])->name('bulkDeleteQuestions');
    $router->get('/show/{guid}', [QuestionController::class, 'showData']);
    $router->get('/show/{guid}/{language}', [QuestionController::class, 'showDataLanguage']);
    $router->put('/', [QuestionController::class, 'updateData']);
    $router->get('/{guid}', [QuestionController::class, 'getData']);
    $router->delete('/{guid}', [QuestionController::class, 'deleteData']);

    $router->post('/', [QuestionController::class, 'insertData']);
});


/**
 * CHATBOT
 */
Route::group([
    'prefix' => $url . 'chatbot',
    'middleware' => 'jwt.verify'
], function ($router) {
    $router->get('/question', [ChatbotController::class, 'getQuestion']);
    $router->post('/answer', [ChatbotController::class, 'answerQuestion']);
    $router->post('/save', [ChatHistoryController::class, 'saveMessage']);
    $router->post('/reset-histories', [ChatHistoryController::class, 'resetHistories']);
    $router->get('/history/{topicGuid}/{userId}', [ChatHistoryController::class, 'getHistory']);
    $router->get('/status/{topicGuid}/{userId}', [ChatHistoryController::class, 'checkStatus']);
    $router->get('/languages/{topicGuid}', [ChatHistoryController::class, 'getAvailableLanguages']);
});

/**
 * ROLE
 */
Route::group([
    'prefix' => $url . 'role',
    'middleware' => 'jwt.verify'
], function ($router) {
    $router->get('/', [RoleController::class, 'showData']);
    $router->get('/name', [RoleController::class, 'getDataByName']);
    $router->put('/', [RoleController::class, 'updateData']);
    $router->get('/{guid}', [RoleController::class, 'getData']);
    $router->delete('/{guid}', [RoleController::class, 'deleteData']);
    $router->post('/', [RoleController::class, 'insertData']);
});

/**
 * ANSWER
 */
Route::group([
    'prefix' => $url . 'answer',
    'middleware' => 'jwt.verify'
], function ($router) {
    $router->get('/', [AnswerController::class, 'showData']);
    $router->put('/', [AnswerController::class, 'updateData']);
    $router->get('/{guid}/{id}', [AnswerController::class, 'getDataByUser']);
    $router->post('/user', [AnswerController::class, 'getData']);
    $router->delete('/{guid}', [AnswerController::class, 'deleteData']);
    $router->post('/', [AnswerController::class, 'insertData']);
    $router->post('/grade', [AnswerController::class, 'grade']);
});

/**
 * TOPIC
 */
Route::group([
    'prefix' => $url . 'topic',
    'middleware' => 'jwt.verify'
], function ($router) {
    $router->get('/', [TopicController::class, 'showData']);
    $router->put('/', [TopicController::class, 'updateData']);
    $router->get('/{guid}', [TopicController::class, 'getData']);
    $router->get('/deadline/{guid}', [TopicController::class, 'checkDeadline']);
    $router->post('/delete-file', [TopicController::class, 'deleteFile']);
    $router->delete('/{guid}', [TopicController::class, 'deleteData']);
    $router->post('/', [TopicController::class, 'insertData']);
    $router->post('/filter/course', [TopicController::class, 'topicByCourse']);
    $router->get('/filter/deadline', [TopicController::class, 'topicByDeadline']);
    $router->post('/check/submit', [TopicController::class, 'checkSubmit']);
    $router->post('/upload-file', [TopicController::class, 'uploadFile']);
});

/**
 * COURSE
 */
Route::group([
    'prefix' => $url . 'course',
    'middleware' => 'jwt.verify'
], function ($router) {
    $router->get('/', [CourseController::class, 'showData']);
    $router->put('/', [CourseController::class, 'updateData']);
    $router->get('/{code}', [CourseController::class, 'getData']);
    $router->delete('/{code}', [CourseController::class, 'deleteData']);
    $router->post('/', [CourseController::class, 'insertData']);
});

/**
 * USER COURSE
 */
Route::group([
    'prefix' => $url . 'user-course',
    'middleware' => 'jwt.verify'
], function ($router) {
    $router->get('/', [UserCourseController::class, 'showData']);
    $router->put('/', [UserCourseController::class, 'updateData']);
    $router->get('/course/{code}', [UserCourseController::class, 'getUserByCourse']);
    $router->get('/user/{id}', [UserCourseController::class, 'getDataByUser']);
    $router->delete('/{code}', [UserCourseController::class, 'deleteData']);
    $router->post('/', [UserCourseController::class, 'insertData']);
});

/**
 * ASSISTANT
 */
Route::group([
    'prefix' => $url . 'assistant',
    'middleware' => 'jwt.verify'
], function ($router) {
    $router->get('/{code}', [AssistantController::class, 'getData']);
    $router->delete('/{guid}', [AssistantController::class, 'deleteData']);
    $router->post('/', [AssistantController::class, 'insertData']);
    $router->post('/check', [AssistantController::class, 'checkData']);
});

/**
 * GRADE
 */
Route::group([
    'prefix' => $url . 'grade',
    'middleware' => 'jwt.verify'
], function ($router) {
    $router->get('/topic/{code}/{guid}', [GradeController::class, 'getDataByTopic']);
    $router->get('/', [GradeController::class, 'getData']);
    $router->post('/', [GradeController::class, 'insertData']);
    $router->put('/', [GradeController::class, 'updateData']);
});
