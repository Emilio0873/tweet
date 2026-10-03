<?php

use App\Controllers\AuthController;
use App\Controllers\ChatController;
use App\Controllers\ContactController;
use App\Controllers\FeedController;
use App\Controllers\HomeController;
use App\Controllers\ListingController;
use App\Controllers\PostController;
use App\Controllers\UserController;
use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\GuestMiddleware;

$router = new Router();

// Accueil = fil social ; marketplace = /annonces
$router->get('/', [FeedController::class, 'index']);
$router->get('/feed', [FeedController::class, 'redirectHome']);
$router->get('/feed/poll', [FeedController::class, 'poll'], [AuthMiddleware::class]);
$router->get('/annonces', [ListingController::class, 'index']);
$router->get('/annonces/{id}', [ListingController::class, 'show']);
$router->get('/annonces/{id}/edit', [ListingController::class, 'editForm'], [AuthMiddleware::class]);
$router->post('/annonces/{id}/update', [ListingController::class, 'update'], [AuthMiddleware::class]);
$router->post('/annonces/{id}/delete', [ListingController::class, 'destroy'], [AuthMiddleware::class]);
$router->post('/annonces/{id}/save', [ListingController::class, 'save']);
$router->post('/annonces/{id}/like', [ListingController::class, 'like'], [AuthMiddleware::class]);
$router->post('/annonces/{id}/comment', [ListingController::class, 'comment'], [AuthMiddleware::class]);
$router->get('/publier', [ListingController::class, 'createForm'], [AuthMiddleware::class]);
$router->post('/annonces', [ListingController::class, 'store'], [AuthMiddleware::class]);

// Auth (invités)
$router->get('/login', [AuthController::class, 'showLogin'], [GuestMiddleware::class]);
$router->post('/login', [AuthController::class, 'login'], [GuestMiddleware::class]);
$router->get('/register', [AuthController::class, 'showRegister'], [GuestMiddleware::class]);
$router->post('/register', [AuthController::class, 'register'], [GuestMiddleware::class]);
$router->get('/forgot-password', [AuthController::class, 'showForgot'], [GuestMiddleware::class]);
$router->post('/forgot-password', [AuthController::class, 'forgot'], [GuestMiddleware::class]);
$router->get('/logout', [AuthController::class, 'logout'], [AuthMiddleware::class]);

// Fil d'actualités (Accueil = / ; /feed redirige)
$router->get('/explore', [FeedController::class, 'explore'], [AuthMiddleware::class]);
$router->get('/explore/suggest', [FeedController::class, 'suggest'], [AuthMiddleware::class]);
$router->get('/hashtag/{tag}', [FeedController::class, 'hashtag'], [AuthMiddleware::class]);
$router->get('/saved', [FeedController::class, 'saved'], [AuthMiddleware::class]);
$router->get('/notifications', [FeedController::class, 'notifications'], [AuthMiddleware::class]);
$router->post('/follow', [FeedController::class, 'follow'], [AuthMiddleware::class]);
$router->post('/unfollow', [FeedController::class, 'unfollow'], [AuthMiddleware::class]);
$router->get('/u/{username}', [PostController::class, 'byUsername'], [AuthMiddleware::class]);

$router->post('/posts', [PostController::class, 'store'], [AuthMiddleware::class]);
$router->get('/posts/{id}', [PostController::class, 'show'], [AuthMiddleware::class]);
$router->get('/posts/{id}/edit', [PostController::class, 'edit'], [AuthMiddleware::class]);
$router->post('/posts/{id}/update', [PostController::class, 'update'], [AuthMiddleware::class]);
$router->post('/posts/{id}/delete', [PostController::class, 'destroy'], [AuthMiddleware::class]);
$router->post('/posts/{id}/react', [PostController::class, 'react'], [AuthMiddleware::class]);
$router->post('/posts/{id}/comment', [PostController::class, 'comment'], [AuthMiddleware::class]);
$router->post('/posts/{id}/repost', [PostController::class, 'repost'], [AuthMiddleware::class]);
$router->post('/posts/{id}/save', [PostController::class, 'save'], [AuthMiddleware::class]);
$router->post('/posts/{id}/hide', [PostController::class, 'hide'], [AuthMiddleware::class]);
$router->post('/posts/{id}/pin', [PostController::class, 'pin'], [AuthMiddleware::class]);
$router->post('/posts/{id}/report', [PostController::class, 'report'], [AuthMiddleware::class]);
$router->post('/comments/{id}/update', [PostController::class, 'updateComment'], [AuthMiddleware::class]);
$router->post('/comments/{id}/delete', [PostController::class, 'deleteComment'], [AuthMiddleware::class]);
$router->post('/comments/{id}/like', [PostController::class, 'likeComment'], [AuthMiddleware::class]);
$router->post('/mute', [PostController::class, 'mute'], [AuthMiddleware::class]);
$router->post('/unmute', [PostController::class, 'unmute'], [AuthMiddleware::class]);

// Chat
$router->get('/chat', [ChatController::class, 'index'], [AuthMiddleware::class]);
$router->post('/chat/start', [ChatController::class, 'start'], [AuthMiddleware::class]);
$router->get('/chat/{id}/poll', [ChatController::class, 'poll'], [AuthMiddleware::class]);
$router->post('/chat/{id}/send', [ChatController::class, 'send'], [AuthMiddleware::class]);
$router->get('/chat/{id}', [ChatController::class, 'show'], [AuthMiddleware::class]);

// Profil & paramètres
$router->get('/profile', [UserController::class, 'profile'], [AuthMiddleware::class]);
$router->get('/settings', [UserController::class, 'settings'], [AuthMiddleware::class]);
$router->post('/profile', [UserController::class, 'updateProfile'], [AuthMiddleware::class]);
$router->post('/profile/cover', [UserController::class, 'updateCover'], [AuthMiddleware::class]);
$router->post('/profile/avatar', [UserController::class, 'updateAvatar'], [AuthMiddleware::class]);
$router->post('/profile/password', [UserController::class, 'changePassword'], [AuthMiddleware::class]);
$router->get('/user/{id}', [UserController::class, 'showUser'], [AuthMiddleware::class]);
$router->get('/search', [UserController::class, 'search'], [AuthMiddleware::class]);
$router->get('/sessions', [UserController::class, 'sessions'], [AuthMiddleware::class]);
$router->post('/sessions/revoke', [UserController::class, 'revokeSessions'], [AuthMiddleware::class]);

// Contacts
$router->get('/contacts', [ContactController::class, 'index'], [AuthMiddleware::class]);
$router->post('/contacts/add', [ContactController::class, 'add'], [AuthMiddleware::class]);
$router->post('/contacts/remove', [ContactController::class, 'remove'], [AuthMiddleware::class]);
$router->post('/contacts/block', [ContactController::class, 'block'], [AuthMiddleware::class]);
$router->post('/contacts/unblock', [ContactController::class, 'unblock'], [AuthMiddleware::class]);

return $router;
