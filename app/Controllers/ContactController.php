<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\BlockedUser;
use App\Models\Contact;
use App\Models\User;

class ContactController extends Controller
{
    public function index(): void
    {
        $contacts = (new Contact())->listForUser(Auth::id());
        $blocked  = (new BlockedUser())->listBlocked(Auth::id());

        $this->view('contacts/index', [
            'title'    => 'Contacts',
            'contacts' => $contacts,
            'blocked'  => $blocked,
            'flash'    => $this->getFlash(),
            'csrf'     => Csrf::field(),
        ]);
    }

    public function add(): void
    {
        if (!$this->validateCsrf()) {
            $this->jsonOrRedirect(false, 'Jeton de sécurité invalide.');
        }

        $contactId = (int) $this->input('contact_id', 0);
        $user = (new User())->find($contactId);

        if (!$user || $user['statut'] !== 'actif') {
            $this->jsonOrRedirect(false, 'Utilisateur introuvable.');
        }

        if ((new BlockedUser())->isEitherBlocked(Auth::id(), $contactId)) {
            $this->jsonOrRedirect(false, 'Action impossible (blocage actif).');
        }

        (new Contact())->add(Auth::id(), $contactId);
        $this->jsonOrRedirect(true, 'Contact ajouté.', 'contacts');
    }

    public function remove(): void
    {
        if (!$this->validateCsrf()) {
            $this->jsonOrRedirect(false, 'Jeton de sécurité invalide.');
        }

        $contactId = (int) $this->input('contact_id', 0);
        (new Contact())->remove(Auth::id(), $contactId);
        $this->jsonOrRedirect(true, 'Contact supprimé.', 'contacts');
    }

    public function block(): void
    {
        if (!$this->validateCsrf()) {
            $this->jsonOrRedirect(false, 'Jeton de sécurité invalide.');
        }

        $blockedId = (int) $this->input('user_id', 0);
        if ($blockedId === Auth::id()) {
            $this->jsonOrRedirect(false, 'Action impossible.');
        }

        $blockModel = new BlockedUser();
        $blockModel->block(Auth::id(), $blockedId);
        (new Contact())->remove(Auth::id(), $blockedId);

        $this->jsonOrRedirect(true, 'Utilisateur bloqué.', 'contacts');
    }

    public function unblock(): void
    {
        if (!$this->validateCsrf()) {
            $this->jsonOrRedirect(false, 'Jeton de sécurité invalide.');
        }

        $blockedId = (int) $this->input('user_id', 0);
        (new BlockedUser())->unblock(Auth::id(), $blockedId);
        $this->jsonOrRedirect(true, 'Utilisateur débloqué.', 'contacts');
    }

    private function jsonOrRedirect(bool $ok, string $message, string $redirect = 'contacts'): void
    {
        if (isset($_POST['ajax']) || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
            $this->json(['success' => $ok, 'message' => $message], $ok ? 200 : 400);
        }
        $this->flash($ok ? 'success' : 'error', $message);
        $this->redirect($redirect);
    }
}
