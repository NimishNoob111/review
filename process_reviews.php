<?php
header('Content-Type: application/json');

$jsonFile = 'feedbacks.json';

// Ensure the storage file exists safely
if (!file_exists($jsonFile)) {
    file_put_contents($jsonFile, json_encode([]));
}

// METHOD 1: Fetching all global reviews out to a browser
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo file_get_contents($jsonFile);
    exit;
}

// METHOD 2: Handling incoming new submissions, likes, or comments
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['action'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid data payload']);
        exit;
    }

    $currentData = json_decode(file_get_contents($jsonFile), true);
    $action = $input['action'];

    // Action A: Adding a fresh new review
    if ($action === 'new_review') {
        $newReview = [
            'id' => time() . rand(100, 999), // Secure unique ID generation key
            'username' => htmlspecialchars($input['username']),
            'time' => date('M j, Y • g:i A'),
            'rating' => intval($input['rating']),
            'title' => htmlspecialchars($input['title']),
            'body' => htmlspecialchars($input['body']),
            'likes' => 0,
            'comments' => []
        ];
        array_unshift($currentData, $newReview);
    } 
    
    // Action B: Registering an interaction like click
    elseif ($action === 'toggle_like') {
        $reviewId = $input['id'];
        $isIncrement = $input['increment']; // True if liking, False if unliking
        foreach ($currentData as &$review) {
            if ($review['id'] == $reviewId) {
                $review['likes'] = $isIncrement ? ($review['likes'] + 1) : max(0, $review['likes'] - 1);
                break;
            }
        }
    } 
    
    // Action C: Publishing a threaded response reply
    elseif ($action === 'add_reply') {
        $reviewId = $input['id'];
        $newReply = [
            'author' => htmlspecialchars($input['author']),
            'text' => htmlspecialchars($input['text'])
        ];
        foreach ($currentData as &$review) {
            if ($review['id'] == $reviewId) {
                $review['comments'][] = $newReply;
                break;
            }
        }
    }

    // Write modified data arrays back out onto your server's disk space
    file_put_contents($jsonFile, json_encode($currentData, JSON_PRETTY_PRINT));
    echo json_encode(['status' => 'success', 'data' => $currentData]);
    exit;
}
?>