<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Helpers\Security;
use App\Models\Follow;
use App\Models\Listing;
use App\Models\Notification;
use App\Models\Post;
use App\Models\User;

class FeedController extends Controller
{
    public function redirectHome(): void
    {
        $qs = $_SERVER['QUERY_STRING'] ?? '';
        $this->redirect($qs !== '' ? ('?' . $qs) : '');
    }

    public function index(): void
    {
        $mode = (string) $this->input('mode', 'recent');
        if (!in_array($mode, ['recent', 'foryou', 'following', 'popular'], true)) {
            $mode = 'recent';
        }
        $page = max(1, (int) $this->input('page', 1));
        $viewer = (int) (Auth::id() ?? 0);
        $postModel = new Post();
        $result = $postModel->feed($viewer, $mode, $page);

        if (isset($_GET['ajax'])) {
            ob_start();
            View::render('feed/cards', [
                'posts' => $result['posts'],
                'csrf'  => Csrf::field(),
                'meId'  => $viewer,
                'me'    => Auth::user(),
            ], null);
            $html = ob_get_clean();
            $this->json([
                'html' => $html,
                'next' => $result['hasMore'] ? $page + 1 : null,
            ]);
        }

        $newestId = 0;
        if (!empty($result['posts'])) {
            $newestId = (int) max(array_column($result['posts'], 'id'));
        }

        $this->view('feed/index', [
            'title'       => 'Accueil',
            'posts'       => $result['posts'],
            'hasMore'     => $result['hasMore'],
            'mode'        => $mode,
            'page'        => $page,
            'newestId'    => $newestId,
            'trends'      => $postModel->trending(),
            'csrf'        => Csrf::field(),
            'flash'       => $this->getFlash(),
            'me'          => Auth::user(),
            'meId'        => $viewer,
            'pageClass'   => 'page-feed',
        ], 'layouts/market');
    }

    public function poll(): void
    {
        $mode = (string) $this->input('mode', 'recent');
        if (!in_array($mode, ['recent', 'foryou', 'following', 'popular'], true)) {
            $mode = 'recent';
        }
        $afterId = max(0, (int) $this->input('after_id', 0));
        $count = (new Post())->countNewerThan((int) Auth::id(), $mode, $afterId);
        $this->json(['count' => $count]);
    }

    public function explore(): void
    {
        $q = Security::clean((string) $this->input('q', ''));
        $scope = (string) $this->input('scope', 'all');
        if (!in_array($scope, ['all', 'users', 'posts', 'listings', 'hashtags', 'places'], true)) {
            $scope = 'all';
        }
        $type = (string) $this->input('type', '');
        if (!in_array($type, ['', 'text', 'image', 'video'], true)) {
            $type = '';
        }

        $data = $this->buildSearch($q, $scope, $type);

        if (isset($_GET['ajax'])) {
            $this->json($this->serializeSearch($data, $q, $scope, false));
        }

        $people = (new Follow())->suggestions((int) (Auth::id() ?? 0), 6);

        $this->view('feed/explore', [
            'title'       => 'Recherche',
            'q'           => $q,
            'scope'       => $scope,
            'type'        => $type,
            'users'       => $data['users'],
            'posts'       => $data['posts'],
            'listings'    => $data['listings'],
            'hashtags'    => $data['hashtags'],
            'places'      => $data['places'],
            'trends'      => $data['trends'],
            'suggestions' => $data['suggestions'],
            'people'      => $people,
            'total'       => $data['total'],
            'csrf'        => Csrf::field(),
            'flash'       => $this->getFlash(),
            'me'          => Auth::user(),
            'meId'        => (int) (Auth::id() ?? 0),
            'pageClass'   => 'page-search',
        ], 'layouts/market');
    }

    public function suggest(): void
    {
        $q = Security::clean((string) $this->input('q', ''));
        $data = $this->buildSearch($q, 'all', '', true);
        $this->json($this->serializeSearch($data, $q, 'all', true));
    }

    private function serializeSearch(array $data, string $q, string $scope, bool $lite): array
    {
        $uLimit = $lite ? 5 : 12;
        $pLimit = $lite ? 4 : 12;
        $lLimit = $lite ? 4 : 12;
        $hLimit = $lite ? 5 : 10;
        $plLimit = $lite ? 4 : 10;

        $users = array_map(static function (array $u): array {
            return [
                'id'       => (int) $u['id'],
                'prenom'   => $u['prenom'] ?? '',
                'nom'      => $u['nom'] ?? '',
                'username' => $u['username'] ?? '',
                'bio'      => $u['bio'] ?? '',
                'photo'    => $u['photo'] ?? null,
            ];
        }, array_slice($data['users'], 0, $uLimit));

        $posts = array_map(static function (array $p): array {
            $text = trim(preg_replace('/\s+/', ' ', strip_tags((string) ($p['contenu'] ?? ''))) ?? '');
            return [
                'id'       => (int) $p['id'],
                'extrait'  => mb_strimwidth($text, 0, 100, '…'),
                'prenom'   => $p['prenom'] ?? '',
                'nom'      => $p['nom'] ?? '',
                'username' => $p['username'] ?? '',
                'photo'    => $p['photo'] ?? null,
            ];
        }, array_slice($data['posts'], 0, $pLimit));

        $listings = array_map(static function (array $l): array {
            return [
                'id'    => (int) $l['id'],
                'title' => $l['title'] ?? '',
                'city'  => $l['city'] ?? '',
                'price' => $l['price'] ?? null,
                'photo' => $l['images'][0]['file_path'] ?? ($l['photo'] ?? null),
            ];
        }, array_slice($data['listings'], 0, $lLimit));

        return [
            'q'        => $q,
            'scope'    => $scope,
            'total'    => (int) $data['total'],
            'users'    => $users,
            'posts'    => $posts,
            'listings' => $listings,
            'hashtags' => array_slice($data['hashtags'], 0, $hLimit),
            'places'   => array_slice($data['places'], 0, $plLimit),
        ];
    }

    private function buildSearch(string $q, string $scope, string $type = '', bool $lite = false): array
    {
        $viewer = (int) (Auth::id() ?? 0);
        $postModel = new Post();
        $raw = trim($q);
        $hasQuery = mb_strlen($raw) >= 1;
        $deep = mb_strlen($raw) >= 2;
        $isTag = str_starts_with($raw, '#');
        $isUser = str_starts_with($raw, '@');
        $tag = ltrim(mb_strtolower($raw), '#');
        $userQ = ltrim($raw, '@');

        $users = [];
        $posts = [];
        $listings = [];
        $hashtags = [];
        $places = [];
        $trends = $postModel->trending(8);

        if ($scope === 'all' || $scope === 'users') {
            if ($deep || $isUser) {
                $users = (new User())->search($userQ !== '' ? $userQ : $raw, $viewer, $lite ? 6 : 12);
            }
        }
        if ($scope === 'all' || $scope === 'posts') {
            if ($isTag && $tag !== '') {
                $posts = $postModel->search($viewer, ['hashtag' => $tag, 'type' => $type], $lite ? 6 : 12);
            } elseif ($hasQuery || $type !== '') {
                $posts = $postModel->search($viewer, ['q' => $isUser ? $userQ : $raw, 'type' => $type], $lite ? 6 : 12);
            } elseif ($scope === 'posts') {
                $posts = $postModel->search($viewer, ['type' => ''], $lite ? 6 : 12);
            }
        }
        if ($scope === 'all' || $scope === 'listings') {
            $listings = $deep && !$isTag && !$isUser
                ? (new Listing())->search(['q' => $raw, 'viewer' => $viewer])
                : [];
            $listings = array_slice($listings, 0, $lite ? 6 : 12);
        }
        if ($scope === 'all' || $scope === 'hashtags') {
            $hashtags = $postModel->searchHashtags($raw, $lite ? 6 : 10);
        }
        if ($scope === 'all' || $scope === 'places') {
            $places = (!$isTag && !$isUser)
                ? $postModel->searchLocations($raw, $lite ? 5 : 10)
                : [];
        }

        $suggestions = [];
        foreach (array_slice($trends, 0, 5) as $t) {
            $suggestions[] = [
                'label' => '#' . $t['name'],
                'href'  => 'explore?q=' . rawurlencode('#' . $t['name']) . '&scope=hashtags',
            ];
        }
        foreach (array_slice($places, 0, 3) as $p) {
            if (!empty($p['name'])) {
                $suggestions[] = [
                    'label' => $p['name'],
                    'href'  => 'explore?q=' . rawurlencode($p['name']) . '&scope=places',
                ];
            }
        }

        $total = count($users) + count($posts) + count($listings) + count($hashtags) + count($places);

        return compact('users', 'posts', 'listings', 'hashtags', 'places', 'trends', 'suggestions', 'total');
    }

    public function hashtag(string $tag): void
    {
        $tag = mb_strtolower(rawurldecode($tag));
        $postModel = new Post();
        $posts = $postModel->search((int) (Auth::id() ?? 0), ['hashtag' => $tag]);
        $this->view('feed/hashtag', [
            'title'     => '#' . $tag,
            'tag'       => $tag,
            'posts'     => $posts,
            'csrf'      => Csrf::field(),
            'me'        => Auth::user(),
            'meId'      => (int) (Auth::id() ?? 0),
            'flash'     => $this->getFlash(),
            'pageClass' => 'page-feed',
        ], 'layouts/market');
    }

    public function saved(): void
    {
        $posts = (new Post())->savedBy(Auth::id());
        $this->view('feed/saved', [
            'title'     => 'Enregistrements',
            'posts'     => $posts,
            'csrf'      => Csrf::field(),
            'me'        => Auth::user(),
            'meId'      => (int) Auth::id(),
            'flash'     => $this->getFlash(),
            'pageClass' => 'page-feed',
        ], 'layouts/market');
    }

    public function notifications(): void
    {
        $model = new Notification();
        $items = $model->forUser(Auth::id());
        $model->markAllRead(Auth::id());
        $this->view('listings/notifications', [
            'title'     => 'Notifications',
            'items'     => $items,
            'flash'     => $this->getFlash(),
            'me'        => Auth::user(),
            'pageClass' => 'page-profile',
        ], 'layouts/market');
    }

    public function follow(): void
    {
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Jeton de sécurité invalide.');
            $this->back('');
        }
        $id = (int) $this->input('user_id', 0);
        (new Follow())->follow(Auth::id(), $id);
        $this->flash('success', 'Abonnement ajouté.');
        $this->back('user/' . $id);
    }

    public function unfollow(): void
    {
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Jeton de sécurité invalide.');
            $this->back('');
        }
        $id = (int) $this->input('user_id', 0);
        (new Follow())->unfollow(Auth::id(), $id);
        $this->flash('success', 'Abonnement retiré.');
        $this->back('user/' . $id);
    }

    private function back(string $fallback): void
    {
        $ref = $_SERVER['HTTP_REFERER'] ?? '';
        $app = require dirname(__DIR__, 2) . '/config/app.php';
        if ($ref !== '' && str_contains($ref, $app['url'])) {
            header('Location: ' . $ref);
            exit;
        }
        $this->redirect($fallback);
    }
}
