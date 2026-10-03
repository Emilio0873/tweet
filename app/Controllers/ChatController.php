<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Helpers\Security;
use App\Models\BlockedUser;
use App\Models\Conversation;
use App\Models\Notification;
use App\Models\User;

class ChatController extends Controller
{
    public function index(): void
    {
        $this->screen(null);
    }

    public function show(string $id): void
    {
        $this->screen((int) $id);
    }

    public function start(): void
    {
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Jeton de sécurité invalide.');
            $this->redirect('chat');
        }

        $otherId = (int) $this->input('user_id', 0);
        $userModel = new User();
        $other = $userModel->publicProfile($otherId);
        if (!$other || $otherId === Auth::id()) {
            $this->flash('error', 'Impossible de démarrer cette discussion.');
            $this->redirect('chat');
        }
        if ((new BlockedUser())->isEitherBlocked(Auth::id(), $otherId)) {
            $this->flash('error', 'Cette discussion est bloquée.');
            $this->redirect('user/' . $otherId);
        }

        $isContact = (new \App\Models\Contact())->exists(Auth::id(), $otherId);
        if (!$userModel->canSee($other['privacy_contact'] ?? 'everyone', $otherId, Auth::id(), $isContact)) {
            $this->flash('error', 'Cette personne n’accepte pas les messages.');
            $this->redirect('user/' . $otherId);
        }

        $id = (new Conversation())->openPrivate(Auth::id(), $otherId);
        $this->redirect('chat/' . $id);
    }

    public function send(string $id): void
    {
        if (!$this->validateCsrf()) {
            $this->redirect('chat/' . $id);
        }

        $conversationId = (int) $id;
        $model = new Conversation();
        $other = $model->participant($conversationId, Auth::id());
        if (!$other) {
            $this->flash('error', 'Discussion introuvable.');
            $this->redirect('chat');
        }
        if ((new BlockedUser())->isEitherBlocked(Auth::id(), (int) $other['id'])) {
            $this->flash('error', 'Message non envoyé.');
            $this->redirect('chat/' . $conversationId);
        }

        $contenu = trim(Security::clean((string) $this->input('contenu', '')));
        if ($contenu === '') {
            $this->redirect('chat/' . $conversationId);
        }
        $contenu = mb_substr($contenu, 0, 2000);
        $message = $model->send($conversationId, Auth::id(), $contenu);

        $me = Auth::user();
        (new Notification())->notify(
            (int) $other['id'],
            'message',
            'Nouveau message',
            $me['prenom'] . ' vous a écrit.',
            'chat/' . $conversationId
        );

        if ($this->wantsJson()) {
            $this->json(['message' => $this->present($message, Auth::id())]);
        }
        $this->redirect('chat/' . $conversationId);
    }

    public function poll(string $id): void
    {
        $conversationId = (int) $id;
        $model = new Conversation();
        if (!$model->participant($conversationId, Auth::id())) {
            $this->json(['messages' => []], 404);
        }
        $after = max(0, (int) $this->input('after', 0));
        $rows = $model->messages($conversationId, Auth::id(), $after);
        if ($rows) {
            $model->markRead($conversationId, Auth::id());
        }
        $me = Auth::id();
        $this->json([
            'messages' => array_map(fn($row) => $this->present($row, $me), $rows),
        ]);
    }

    private function screen(?int $conversationId): void
    {
        $model = new Conversation();
        $me = Auth::id();
        $inbox = $model->inbox($me);
        $active = null;
        $messages = [];

        if ($conversationId) {
            $active = $model->participant($conversationId, $me);
            if (!$active) {
                $this->flash('error', 'Discussion introuvable.');
                $this->redirect('chat');
            }
            $active['conversation_id'] = $conversationId;
            $messages = $model->messages($conversationId, $me);
            $model->markRead($conversationId, $me);
        }

        $this->view('chat/index', [
            'title'     => 'Discussions',
            'user'      => Auth::user(),
            'inbox'     => $inbox,
            'active'    => $active,
            'messages'  => $messages,
            'csrf'      => Csrf::field(),
            'flash'     => $this->getFlash(),
            'me'        => Auth::user(),
            'meId'      => $me,
            'pageClass' => 'page-chat',
        ], 'layouts/market');
    }

    private function present(array $message, int $meId): array
    {
        return [
            'id' => (int) $message['id'],
            'mine' => (int) $message['sender_id'] === $meId,
            'contenu' => $message['is_deleted_for_all'] ?? 0 ? 'Message supprimé' : (string) $message['contenu'],
            'time' => date('H:i', strtotime($message['created_at'])),
        ];
    }

    private function wantsJson(): bool
    {
        return isset($_POST['ajax']) || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    }
}
