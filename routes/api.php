<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BannerController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\VendorController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PageController;

// ============================================================
// ПУБЛИЧНЫЕ маршруты (без авторизации)
// ============================================================

// Каталог
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{slug}', [CategoryController::class, 'show']);

// Вендоры
Route::get('/vendors', [VendorController::class, 'index']);
Route::get('/vendors/{slug}', [VendorController::class, 'show']);

// Товары
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{slug}', [ProductController::class, 'show']);
Route::get('/products/search', [ProductController::class, 'search']);

// Корзина — работает для гостей (session_id) и авторизованных (user_id)
Route::middleware('auth.optional')->group(function () {
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart', [CartController::class, 'store']);
    Route::patch('/cart/{id}', [CartController::class, 'update']);
    Route::delete('/cart/{id}', [CartController::class, 'destroy']);
    Route::delete('/cart', [CartController::class, 'clear']);
});

// Баннеры главной страницы
Route::get('/banners', [BannerController::class, 'index']);

// Разделы компании (О компании, Новости и т.д.)
Route::get('/pages', [PageController::class, 'index']);
Route::get('/pages/{slug}', [PageController::class, 'show']);

// Аутентификация
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// ============================================================
// ЗАЩИЩЁННЫЕ маршруты (только для авторизованных пользователей)
// middleware('auth:sanctum') — проверяет Bearer-токен в заголовке
// ============================================================
Route::middleware('auth:sanctum')->group(function () {

    // Выход
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Профиль
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::patch('/auth/me', [AuthController::class, 'update']);

    // Избранное
    Route::get('/favorites', [FavoriteController::class, 'index']);
    Route::post('/favorites/{productId}', [FavoriteController::class, 'toggle']);

    // Заказы
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);

    // ============================================================
    // ADMIN маршруты (только для роли admin)
    // ============================================================
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::apiResource('categories', CategoryController::class)->except(['index', 'show']);
        Route::post('categories/{id}/image', [CategoryController::class, 'uploadImage']);
        Route::apiResource('vendors', VendorController::class)->except(['index', 'show']);
        Route::post('vendors/{id}/image', [VendorController::class, 'uploadImage']);
        Route::get('products', [ProductController::class, 'adminIndex']);
        Route::post('products/{id}/licenses/sync', [ProductController::class, 'syncLicenses']);
        Route::post('products/{id}/image', [ProductController::class, 'uploadImage']);
        Route::delete('products/{id}/image', [ProductController::class, 'deleteImage']);
        Route::apiResource('products', ProductController::class)->except(['index', 'show']);
        Route::get('/orders', [OrderController::class, 'adminIndex']);
        Route::patch('/orders/{id}/status', [OrderController::class, 'updateStatus']);

        // Баннеры
        Route::get('banners', [BannerController::class, 'adminIndex']);
        Route::post('banners', [BannerController::class, 'store']);
        Route::put('banners/{id}', [BannerController::class, 'update']);
        Route::delete('banners/{id}', [BannerController::class, 'destroy']);
        Route::post('banners/{id}/image', [BannerController::class, 'uploadImage']);

        // Разделы компании
        Route::get('pages', [PageController::class, 'adminIndex']);
        Route::post('pages', [PageController::class, 'storePage']);
        Route::put('pages/{id}', [PageController::class, 'updatePage']);
        Route::delete('pages/{id}', [PageController::class, 'destroyPage']);
        Route::post('pages/{id}/cover', [PageController::class, 'uploadCover']);
        // Элементы раздела
        Route::get('pages/{id}/items', [PageController::class, 'adminItems']);
        Route::post('pages/{id}/items', [PageController::class, 'storeItem']);
        Route::put('items/{id}', [PageController::class, 'updateItem']);
        Route::delete('items/{id}', [PageController::class, 'destroyItem']);
        Route::post('items/{id}/file', [PageController::class, 'uploadFile']);
    });
});
