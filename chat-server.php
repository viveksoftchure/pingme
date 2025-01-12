<?php
require dirname(__DIR__) . '/pingme/lib/Ratchet/vendor/autoload.php'; // Autoload composer dependencies

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class ChatServer implements MessageComponentInterface {
    // Store the connected clients
    protected $clients;
    protected $queue;
    protected $badWords = ['badword1', 'badword2']; // Add any words you want to filter

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->queue = [];
    }

    // When a new client connects
    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";

        // Add the user to the queue
        $this->queue[] = $conn;

        // Pair users when there are at least 2 in the queue
        if (count($this->queue) >= 2) {
            $user1 = array_shift($this->queue);
            $user2 = array_shift($this->queue);

            // Notify the users that they are paired
            // $user1->send("You are now paired with User {$user2->resourceId}");
            // $user2->send("You are now paired with User {$user1->resourceId}");

            // Allow these two users to chat with each other
            // $user1->send("You can now start chatting with User {$user2->resourceId}");
            // $user2->send("You can now start chatting with User {$user1->resourceId}");
        }
    }

    // When a message is received from a client
    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);

        // Check if the message contains any bad words
        // foreach ($this->badWords as $badWord) {
        //     if (stripos($msg, $badWord) !== false) {
        //         // If it does, send a warning and ignore the message
        //         $from->send("Warning: Your message contains inappropriate content.");
        //         return;
        //     }
        // }

        // If the message is clean, send it to the paired user
        foreach ($this->clients as $client) {
            if ($from !== $client) {
                // $client->send("{$msg}");
                $client->send(json_encode($data));
            }
        }
    }

    // When a client disconnects
    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";

        // Remove from queue
        $this->queue = array_filter($this->queue, function($user) use ($conn) {
            return $user->resourceId !== $conn->resourceId;
        });

        // Notify others that the user has disconnected
        foreach ($this->clients as $client) {
            $client->send(json_encode(['status' => 'closed', 'type' => 'message', 'message' => "User {$conn->resourceId} has left the chat."]));
        }
    }

    // Handle errors
    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Error: {$e->getMessage()}\n";
        $conn->close();
    }
}

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

// Set up WebSocket server
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new ChatServer()
        )
    ),
    8080 // Port number
);

$server->run();