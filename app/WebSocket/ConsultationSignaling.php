<?php
namespace App\WebSocket;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use App\Models\ConsultationSession;
use App\Models\ConsultationSignal;

class ConsultationSignaling implements MessageComponentInterface
{
    protected $clients;
    protected $rooms;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->rooms = [];
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg, true);
        if (!$data || !isset($data['type'])) {
            return;
        }

        $sessionId = (int) ($data['session_id'] ?? 0);
        $userId = (int) ($data['user_id'] ?? 0);

        if ($data['type'] === 'join') {
            // Register client to a room
            if (!isset($this->rooms[$sessionId])) {
                $this->rooms[$sessionId] = [];
            }
            $this->rooms[$sessionId][$from->resourceId] = [
                'conn' => $from,
                'user_id' => $userId
            ];
            echo "Connection {$from->resourceId} joined session {$sessionId} as user {$userId}\n";
            return;
        }

        // Must be in a room to send messages
        if (!isset($this->rooms[$sessionId][$from->resourceId])) {
            return;
        }

        // Broadcast to other peers in the room
        $signalType = $data['signal_type'] ?? 'message';
        $payload = $data['payload'] ?? '';

        if (!isset($this->rooms[$sessionId])) {
            return;
        }

        foreach ($this->rooms[$sessionId] as $resourceId => $clientData) {
            if ($from !== $clientData['conn']) {
                $clientData['conn']->send(json_encode([
                    'id' => time(), // Dummy ID for real-time
                    'signal_type' => $signalType,
                    'payload' => is_string($payload) ? $payload : json_encode($payload),
                    'sender_id' => $userId
                ]));
            }
        }

        // Persist to database
        try {
            (new ConsultationSignal())->create([
                'session_id' => $sessionId,
                'sender_id' => $userId,
                'signal_type' => $signalType,
                'payload' => is_string($payload) ? trim($payload) : json_encode($payload),
            ]);

            if ($signalType === 'presence') {
                $sessionModel = new ConsultationSession();
                $session = $sessionModel->find($sessionId);
                if ($session && $session['status'] === 'waiting') {
                    if ($sessionModel->participantCount($sessionId) >= 2) {
                        $sessionModel->updateStatus($sessionId, 'active', date('Y-m-d H:i:s'));
                    }
                }
            } elseif ($signalType === 'hangup') {
                $sessionModel = new ConsultationSession();
                $sessionModel->updateStatus($sessionId, 'ended', null, date('Y-m-d H:i:s'));
            }
        } catch (\Exception $e) {
            echo "Error persisting signal: " . $e->getMessage() . "\n";
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        // Remove from rooms
        foreach ($this->rooms as $sessionId => $clients) {
            if (isset($clients[$conn->resourceId])) {
                unset($this->rooms[$sessionId][$conn->resourceId]);
                if (empty($this->rooms[$sessionId])) {
                    unset($this->rooms[$sessionId]);
                }
            }
        }
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}
