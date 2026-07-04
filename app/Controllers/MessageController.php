<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Security;
use App\Core\View;
use App\Models\ChatMessage;
use App\Models\User;

class MessageController
{
    public function index(): void
    {
        Auth::requireLogin();
        $contactId = (int) ($_GET['contact_id'] ?? 0);
        $query = trim((string) ($_GET['q'] ?? ''));
        $chat = new ChatMessage();
        $userModel = new User();
        $recentContacts = $chat->contactsForUser(Auth::id());
        $directoryContacts = $userModel->messagingDirectory(Auth::id(), Auth::type(), $query);
        $contactsById = [];

        foreach (array_merge($recentContacts, $directoryContacts) as $contact) {
            $id = (int) ($contact['id'] ?? 0);
            if (!$id || isset($contactsById[$id])) {
                continue;
            }
            $contactsById[$id] = $contact;
        }

        $contacts = array_values(array_filter($contactsById, fn(array $contact): bool => $this->canMessageType(Auth::type(), (string) ($contact['user_type'] ?? ''))));
        $activeContact = $contactId ? $userModel->contactProfile($contactId) : null;
        if ($activeContact && !$this->canMessageType(Auth::type(), (string) ($activeContact['user_type'] ?? ''))) {
            $activeContact = null;
            $contactId = 0;
            $conversation = [];
        }
        $conversation = [];

        if ($contactId && $activeContact) {
            $conversation = $chat->conversation(Auth::id(), $contactId);
            $chat->markConversationRead(Auth::id(), $contactId);
        }

        View::render('messages', [
            'title' => 'Messages',
            'contacts' => $contacts,
            'contactId' => $contactId,
            'activeContact' => $activeContact,
            'conversation' => $conversation,
            'query' => $query,
        ]);
    }

    public function fetch(): void
    {
        Auth::requireLogin();
        header('Content-Type: application/json');
        $contactId = Security::cleanInt($_GET['contact_id'] ?? 0);
        if (!$contactId) {
            echo json_encode(['messages' => []]);
            return;
        }

        (new ChatMessage())->markConversationRead(Auth::id(), $contactId);
        echo json_encode(['messages' => (new ChatMessage())->conversation(Auth::id(), $contactId)]);
    }

    public function send(): void
    {
        Auth::requireLogin();
        header('Content-Type: application/json');

        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
            return;
        }

        $receiverId = Security::cleanInt($_POST['receiver_id'] ?? 0);
        $message = trim((string) ($_POST['message'] ?? ''));
        if (!$receiverId || $message === '') {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Message cannot be empty.']);
            return;
        }

        $receiver = (new User())->contactProfile($receiverId);
        if (!$receiver || !$this->canMessageType(Auth::type(), (string) ($receiver['user_type'] ?? ''))) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'This contact is not available for your care role.']);
            return;
        }

        (new ChatMessage())->send(Auth::id(), $receiverId, $message);
        echo json_encode(['success' => true]);
    }

    private function canMessageType(?string $currentType, string $contactType): bool
    {
        $allowed = match ($currentType) {
            'patient' => ['doctor'],
            'doctor' => ['patient', 'hospital'],
            'hospital' => ['doctor'],
            'admin' => ['patient', 'doctor', 'hospital'],
            default => ['patient', 'doctor', 'hospital', 'admin'],
        };

        return in_array($contactType, $allowed, true);
    }
}
