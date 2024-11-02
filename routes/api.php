<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\PrestataireController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\StripePaymentController;
use App\Http\Controllers\PayPalController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('register', [AuthController::class, 'addUser']);
Route::post('login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth.jwt');
Route::get('/me', [AuthController::class, 'me'])->middleware('auth.jwt');

Route::get('getAnnonceDetails/{id}', [ClientController::class, 'getAnnonceDetails']);
Route::get('/getAllCategories', [ClientController::class, 'getAllCategories']);
Route::get('/categoriesWithAnnoncesCounted', [ClientController::class, 'getCategoriesWithAnnonces']);
Route::get('/getAllAcceptedAnnoncesHomePage', [ClientController::class, 'getAllAcceptedAnnonces']);

Route::middleware('guest')->group(function () {
    Route::post('/auth/google-login', [AuthController::class, 'googleLogin']);
});


Route::middleware('auth:api')->group(function () {
    Route::post('/paypal/payment', [PaypalController::class, 'payment']);
    Route::get('/paypal/success', [PaypalController::class, 'success'])->name('paypal.success');
    Route::get('/paypal/cancel', [PaypalController::class, 'cancel'])->name('paypal.cancel');

    Route::post('/pay-by-creditcard', [StripePaymentController::class, 'store']);
    Route::get('/myConversations', [ChatController::class, 'getMyConversations']);
    Route::get('/conversations/{userId}', [ChatController::class, 'getOrCreateConversation']);
    Route::post('/messages', [ChatController::class, 'storeMessage']);
    Route::get('/messages/{conversationId}', [ChatController::class, 'getMessages']);
    Route::post('/new-conversation', [ChatController::class, 'storeConversation']);
    Route::post('/send-message', [ChatController::class, 'send']);

    Route::get('getFiltredAnnonces', [ClientController::class, 'filterAnnonces']);
    Route::post('/reclamation', [ClientController::class, 'reclamation']);
    Route::get('/getAllAcceptedAnnonces', [ClientController::class, 'getAllAcceptedAnnonces']);
    Route::post('/favoris', [ClientController::class, 'favoris']);
    Route::get('/favoris/check', [ClientController::class, 'check']);
    Route::get('/getAnnonces', [ClientController::class, 'getAnnonces']);
    Route::get('/getAllDetails', [ClientController::class, 'getAllDetails']);

    Route::post('/banUsers', [AdminController::class, 'banUsers']);
    Route::get('/getAllPrestataires', [AdminController::class, 'getAllPrestataires']);
    Route::get('/getAllClients', [AdminController::class, 'getAllClients']);
    Route::get('/getAllReclamations', [AdminController::class, 'getAllReclamations']);
    Route::get('/getAllAnnonces', [AdminController::class, 'getAllAnnonces']);
    Route::get('/getLatestPrestataires', [AdminController::class, 'getLatestPrestataires']);
    Route::get('/getLatestClients', [AdminController::class, 'getLatestClients']);
    Route::get('/getLatestAnnonces', [AdminController::class, 'getLatestAnnonces']);
    Route::get('/getLatestReclamations', [AdminController::class, 'getLatestReclamations']);
    Route::get('/countPrestataires', [AdminController::class, 'countPrestataires']);
    Route::get('/countClients', [AdminController::class, 'countClients']);
    Route::get('/countAnnonces', [AdminController::class, 'countAnnonces']);
    Route::get('/countReclamations', [AdminController::class, 'countReclamations']);
    Route::delete('/deleteAnnonces/{id}', [AdminController::class, 'destroy']);
    Route::post('/acceptAnnonce/{id}', [AdminController::class, 'acceptAnnonce']);
    Route::delete('/deleteReclamation/{id}', [AdminController::class, 'destroyReclamations']);

    Route::post('/annonce/create', [PrestataireController::class, 'createAnnonce']);
    Route::get('/getMyAnnonces', [PrestataireController::class, 'getMyAnnonces']);
    Route::get('/checkIsAbleToAddAnnonce', [PrestataireController::class, 'checkIsAbleToAddAnnonce']);
    Route::get('/profile/user', [PrestataireController::class, 'show']);
    Route::put('/profile/user', [PrestataireController::class, 'update']);

    Route::get('verify-token', [AuthController::class, 'verifyToken']);
});
