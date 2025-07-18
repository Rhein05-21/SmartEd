<?php
session_start();
header('Content-Type: application/json');

$chat_file = __DIR__ . '/../data/chat_messages.json';

function get_messages() {
    global $chat_file;
    if (!file_exists($chat_file)) {
        file_put_contents($chat_file, '[]');
    }
    return json_decode(file_get_contents($chat_file), true);
}

function save_messages($messages) {
    global $chat_file;
    file_put_contents($chat_file, json_encode($messages));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['message']) && isset($input['sender']) && isset($input['sender_type'])) {
        $messages = get_messages();
        
        $new_message = [
            'id' => uniqid(),
            'message' => htmlspecialchars($input['message']),
            'sender' => htmlspecialchars($input['sender']),
            'sender_type' => $input['sender_type'],
            'timestamp' => time()
        ];
        
        $messages[] = $new_message;
        
        // Keep only the last 100 messages
        if (count($messages) > 100) {
            $messages = array_slice($messages, -100);
        }
        
        save_messages($messages);
        echo json_encode(['success' => true, 'message' => $new_message]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid message format']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $messages = get_messages();
    echo json_encode(['messages' => $messages]);
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    if (!isset($_SESSION['admin_id'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    
    // Create a backup of the current messages
    $messages = get_messages();
    if (!empty($messages)) {
        $backup_file = __DIR__ . '/../data/chat_backup_' . date('Y-m-d_H-i-s') . '.json';
        file_put_contents($backup_file, json_encode($messages));
    }
    
    // Clear the current messages
    file_put_contents($chat_file, '[]');
    echo json_encode(['success' => true, 'message' => 'Chat cleared']);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'clear') {
    if (!isset($_SESSION['admin_id'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    
    // Create a backup of the current messages
    $messages = get_messages();
    if (!empty($messages)) {
        $backup_file = __DIR__ . '/../data/chat_backup_' . date('Y-m-d_H-i-s') . '.json';
        file_put_contents($backup_file, json_encode($messages));
        
        // Clear the current messages
        save_messages([]);
        echo json_encode(['success' => true, 'message' => 'Chat cleared and backup created']);
    } else {
        echo json_encode(['success' => true, 'message' => 'No messages to clear']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}