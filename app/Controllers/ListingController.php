<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Helpers\Security;
use App\Models\Listing;
use App\Models\Notification;

class ListingController extends Controller
{
    public function home(): void
    {
        $model = new Listing();
        $this->view('listings/home', [
            'title'      => 'Accueil',
            'categories' => Listing::CATEGORIES,
            'feed'       => $model->feed(Auth::id(), 40),
            'csrf'       => Csrf::field(),
            'me'         => Auth::user(),
            'flash'      => $this->getFlash(),
        ], 'layouts/market');
    }

    public function index(): void
    {
        $filters = [
            'q'        => Security::clean((string) $this->input('q', '')),
            'category' => (string) $this->input('category', ''),
            'city'     => Security::clean((string) $this->input('city', '')),
            'sort'     => (string) $this->input('sort', 'recent'),
        ];
        $this->view('listings/index', [
            'title'      => 'Recherche',
            'filters'    => $filters,
            'listings'   => (new Listing())->search($filters + ['viewer' => Auth::id()]),
            'categories' => Listing::CATEGORIES,
            'csrf'       => Csrf::field(),
            'me'         => Auth::user(),
        ], 'layouts/market');
    }

    public function show(string $id): void
    {
        $model = new Listing();
        $listing = $model->findPublic((int) $id, Auth::id());
        if (!$listing) {
            $this->flash('error', 'Annonce introuvable.');
            $this->redirect('');
        }
        $key = 'seen_listing_' . $listing['id'];
        if (empty($_SESSION[$key])) {
            $model->hit((int) $listing['id']);
            $_SESSION[$key] = 1;
            $listing['views'] = (int) $listing['views'] + 1;
        }
        $this->view('listings/show', [
            'title'    => $listing['title'],
            'listing'  => $listing,
            'comments' => $model->comments((int) $listing['id'], 50),
            'csrf'     => Csrf::field(),
            'me'       => Auth::user(),
            'flash'    => $this->getFlash(),
        ], 'layouts/market');
    }

    public function createForm(): void
    {
        $this->view('listings/create', [
            'title'      => 'Publier une annonce',
            'categories' => Listing::CATEGORIES,
            'csrf'       => Csrf::field(),
            'me'         => Auth::user(),
            'flash'      => $this->getFlash(),
        ], 'layouts/market');
    }

    public function store(): void
    {
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Jeton de sécurité invalide.');
            $this->redirect('publier');
        }

        $title = Security::clean((string) $this->input('title', ''));
        $description = trim((string) $this->input('description', ''));
        $city = Security::clean((string) $this->input('city', ''));
        $category = (string) $this->input('category', '');
        $priceRaw = str_replace([' ', ','], ['', '.'], (string) $this->input('price', ''));
        $price = $priceRaw === '' ? null : (float) $priceRaw;

        if ($title === '' || $city === '' || !isset(Listing::CATEGORIES[$category])) {
            $this->flash('error', 'Titre, catégorie et ville sont obligatoires.');
            $this->redirect('');
        }

        $files = $this->collectImages();
        if (isset($files['error'])) {
            $this->flash('error', $files['error']);
            $this->redirect('');
        }

        $id = (new Listing())->create([
            'user_id'     => Auth::id(),
            'title'       => mb_substr($title, 0, 160),
            'description' => mb_substr($description, 0, 4000),
            'price'       => $price,
            'category'    => $category,
            'city'        => mb_substr($city, 0, 80),
        ]);
        $model = new Listing();
        foreach ($files as $path) {
            $model->addImage($id, $path);
        }
        $this->flash('success', 'Publication ajoutée.');
        $this->redirect('');
    }

    public function editForm(string $id): void
    {
        $listing = (new Listing())->findOwned((int) $id, Auth::id());
        if (!$listing) {
            $this->flash('error', 'Publication introuvable.');
            $this->redirect('');
        }
        $this->view('listings/edit', [
            'title'      => 'Modifier la publication',
            'listing'    => $listing,
            'categories' => Listing::CATEGORIES,
            'csrf'       => Csrf::field(),
            'me'         => Auth::user(),
            'flash'      => $this->getFlash(),
        ], 'layouts/market');
    }

    public function update(string $id): void
    {
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Jeton de sécurité invalide.');
            $this->redirect('annonces/' . $id . '/edit');
        }

        $model = new Listing();
        $listing = $model->findOwned((int) $id, Auth::id());
        if (!$listing) {
            $this->flash('error', 'Publication introuvable.');
            $this->redirect('');
        }

        $title = Security::clean((string) $this->input('title', ''));
        $description = trim((string) $this->input('description', ''));
        $city = Security::clean((string) $this->input('city', ''));
        $category = (string) $this->input('category', '');
        $priceRaw = str_replace([' ', ','], ['', '.'], (string) $this->input('price', ''));
        $price = $priceRaw === '' ? null : (float) $priceRaw;

        if ($title === '' || $city === '' || !isset(Listing::CATEGORIES[$category])) {
            $this->flash('error', 'Titre, catégorie et ville sont obligatoires.');
            $this->redirect('annonces/' . $id . '/edit');
        }

        $model->update((int) $id, Auth::id(), [
            'title'       => mb_substr($title, 0, 160),
            'description' => mb_substr($description, 0, 4000),
            'price'       => $price,
            'category'    => $category,
            'city'        => mb_substr($city, 0, 80),
        ]);

        $files = $this->collectImages();
        if (isset($files['error'])) {
            $this->flash('error', $files['error']);
            $this->redirect('annonces/' . $id . '/edit');
        }
        foreach ($files as $path) {
            $model->addImage((int) $id, $path);
        }

        $this->flash('success', 'Publication mise à jour.');
        $this->redirect('annonces/' . $id);
    }

    public function destroy(string $id): void
    {
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Jeton de sécurité invalide.');
            $this->redirect('annonces/' . $id);
        }

        $ok = (new Listing())->softDelete((int) $id, Auth::id());
        if (!$ok) {
            $this->flash('error', 'Impossible de supprimer cette publication.');
            $this->redirect('annonces/' . $id);
        }

        $this->flash('success', 'Publication supprimée.');
        $this->redirect('');
    }

    public function save(string $id): void
    {
        if (!Auth::check()) {
            $this->redirect('login');
        }
        if (!$this->validateCsrf()) {
            $this->redirect('annonces/' . $id);
        }
        (new Listing())->toggleSave(Auth::id(), (int) $id);
        $this->back('annonces/' . $id);
    }

    public function like(string $id): void
    {
        if (!$this->validateCsrf()) {
            $this->back('annonces/' . $id);
        }
        $model = new Listing();
        $listing = $model->findPublic((int) $id, Auth::id());
        if (!$listing) {
            $this->flash('error', 'Publication introuvable.');
            $this->redirect('');
        }

        $liked = $model->toggleLike(Auth::id(), (int) $id);
        $ownerId = (int) $listing['user_id'];
        $me = Auth::user();
        if ($liked && $ownerId !== (int) Auth::id()) {
            (new Notification())->notify(
                $ownerId,
                'listing_like',
                'Nouveau j’aime',
                ($me['prenom'] ?? 'Quelqu’un') . ' a aimé « ' . $listing['title'] . ' »',
                'annonces/' . $id
            );
        }
        $this->back('annonces/' . $id);
    }

    public function comment(string $id): void
    {
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Jeton de sécurité invalide.');
            $this->back('annonces/' . $id);
        }

        $contenu = trim((string) $this->input('contenu', ''));
        $contenu = Security::clean($contenu);
        if ($contenu === '' || mb_strlen($contenu) > 1000) {
            $this->flash('error', 'Commentaire invalide.');
            $this->back('annonces/' . $id);
        }

        $model = new Listing();
        $listing = $model->findPublic((int) $id, Auth::id());
        if (!$listing) {
            $this->flash('error', 'Publication introuvable.');
            $this->redirect('');
        }

        $model->addComment((int) $id, Auth::id(), mb_substr($contenu, 0, 1000));
        $ownerId = (int) $listing['user_id'];
        $me = Auth::user();
        if ($ownerId !== (int) Auth::id()) {
            (new Notification())->notify(
                $ownerId,
                'listing_comment',
                'Nouveau commentaire',
                ($me['prenom'] ?? 'Quelqu’un') . ' a commenté « ' . $listing['title'] . ' »',
                'annonces/' . $id . '#comments'
            );
        }
        $this->back('annonces/' . $id . '#comments');
    }

    private function collectImages(): array
    {
        if (empty($_FILES['images']['name']) || !is_array($_FILES['images']['name'])) {
            return [];
        }
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $stored = [];
        foreach ($_FILES['images']['name'] as $i => $name) {
            if ($_FILES['images']['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if (count($stored) >= 6) {
                return ['error' => '6 photos maximum.'];
            }
            if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK || $_FILES['images']['size'][$i] > 8 * 1024 * 1024) {
                return ['error' => 'Chaque photo doit faire moins de 8 Mo.'];
            }
            $mime = $finfo->file($_FILES['images']['tmp_name'][$i]);
            if (!isset($allowed[$mime])) {
                return ['error' => 'Formats acceptés : JPG, PNG, WEBP.'];
            }
            $filename = 'listing_' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
            $dest = dirname(__DIR__, 2) . '/public/uploads/listings/' . $filename;
            if (!move_uploaded_file($_FILES['images']['tmp_name'][$i], $dest)) {
                return ['error' => 'Impossible d’enregistrer une photo.'];
            }
            $stored[] = 'listings/' . $filename;
        }
        return $stored;
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
