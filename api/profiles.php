<?php
/**
 * Profiles API
 * RESTful API for managing child assessment profiles
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database configuration
define('DB_PATH', __DIR__ . '/../data/profiles.db');

// Ensure data directory exists
$dataDir = dirname(DB_PATH);
if (!file_exists($dataDir)) {
    mkdir($dataDir, 0755, true);
}

// Initialize database
function initDatabase() {
    try {
        $db = new PDO('sqlite:' . DB_PATH);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create profiles table
        $db->exec("
            CREATE TABLE IF NOT EXISTS profiles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                child_name TEXT NOT NULL,
                child_age INTEGER,
                profile_data TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Create index on child_name for faster searches
        $db->exec("
            CREATE INDEX IF NOT EXISTS idx_child_name ON profiles(child_name)
        ");
        
        return $db;
    } catch (PDOException $e) {
        sendError('Database initialization failed: ' . $e->getMessage(), 500);
    }
}

// Helper function to send JSON response
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

// Helper function to send error response
function sendError($message, $statusCode = 400) {
    sendResponse(['success' => false, 'error' => $message], $statusCode);
}

// Get database connection
$db = initDatabase();

// Get action from query parameter
$action = $_GET['action'] ?? '';

switch ($action) {
    
    // Save profile
    case 'save':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            sendError('Method not allowed', 405);
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            sendError('Invalid JSON data');
        }
        
        $childName = $input['childName'] ?? '';
        $childAge = $input['childAge'] ?? null;
        $profileData = $input['profileData'] ?? null;
        
        if (empty($childName)) {
            sendError('Child name is required');
        }
        
        if (!$profileData) {
            sendError('Profile data is required');
        }
        
        try {
            $stmt = $db->prepare("
                INSERT INTO profiles (child_name, child_age, profile_data)
                VALUES (:child_name, :child_age, :profile_data)
            ");
            
            $stmt->execute([
                ':child_name' => $childName,
                ':child_age' => $childAge,
                ':profile_data' => json_encode($profileData, JSON_UNESCAPED_UNICODE)
            ]);
            
            $profileId = $db->lastInsertId();
            
            sendResponse([
                'success' => true,
                'id' => $profileId,
                'message' => 'Профиль успешно сохранен'
            ]);
            
        } catch (PDOException $e) {
            sendError('Failed to save profile: ' . $e->getMessage(), 500);
        }
        break;
    
    // List all profiles
    case 'list':
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            sendError('Method not allowed', 405);
        }
        
        try {
            $search = $_GET['search'] ?? '';
            
            if ($search) {
                $stmt = $db->prepare("
                    SELECT id, child_name, child_age, created_at, updated_at
                    FROM profiles
                    WHERE child_name LIKE :search
                    ORDER BY created_at DESC
                ");
                $stmt->execute([':search' => '%' . $search . '%']);
            } else {
                $stmt = $db->query("
                    SELECT id, child_name, child_age, created_at, updated_at
                    FROM profiles
                    ORDER BY created_at DESC
                ");
            }
            
            $profiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            sendResponse([
                'success' => true,
                'profiles' => $profiles,
                'count' => count($profiles)
            ]);
            
        } catch (PDOException $e) {
            sendError('Failed to fetch profiles: ' . $e->getMessage(), 500);
        }
        break;
    
    // Get single profile
    case 'get':
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            sendError('Method not allowed', 405);
        }
        
        $id = $_GET['id'] ?? '';
        
        if (empty($id)) {
            sendError('Profile ID is required');
        }
        
        try {
            $stmt = $db->prepare("
                SELECT id, child_name, child_age, profile_data, created_at, updated_at
                FROM profiles
                WHERE id = :id
            ");
            
            $stmt->execute([':id' => $id]);
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$profile) {
                sendError('Profile not found', 404);
            }
            
            // Decode profile_data JSON
            $profile['profile_data'] = json_decode($profile['profile_data'], true);
            
            sendResponse([
                'success' => true,
                'profile' => $profile
            ]);
            
        } catch (PDOException $e) {
            sendError('Failed to fetch profile: ' . $e->getMessage(), 500);
        }
        break;
    
    // Delete profile
    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            sendError('Method not allowed', 405);
        }
        
        $id = $_GET['id'] ?? '';
        
        if (empty($id)) {
            sendError('Profile ID is required');
        }
        
        try {
            $stmt = $db->prepare("DELETE FROM profiles WHERE id = :id");
            $stmt->execute([':id' => $id]);
            
            if ($stmt->rowCount() === 0) {
                sendError('Profile not found', 404);
            }
            
            sendResponse([
                'success' => true,
                'message' => 'Профиль успешно удален'
            ]);
            
        } catch (PDOException $e) {
            sendError('Failed to delete profile: ' . $e->getMessage(), 500);
        }
        break;
    
    // Update profile
    case 'update':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
            sendError('Method not allowed', 405);
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            sendError('Invalid JSON data');
        }
        
        $id = $input['id'] ?? '';
        $childName = $input['childName'] ?? '';
        $childAge = $input['childAge'] ?? null;
        $profileData = $input['profileData'] ?? null;
        
        if (empty($id)) {
            sendError('Profile ID is required');
        }
        
        if (empty($childName)) {
            sendError('Child name is required');
        }
        
        if (!$profileData) {
            sendError('Profile data is required');
        }
        
        try {
            $stmt = $db->prepare("
                UPDATE profiles
                SET child_name = :child_name,
                    child_age = :child_age,
                    profile_data = :profile_data,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            
            $stmt->execute([
                ':id' => $id,
                ':child_name' => $childName,
                ':child_age' => $childAge,
                ':profile_data' => json_encode($profileData, JSON_UNESCAPED_UNICODE)
            ]);
            
            if ($stmt->rowCount() === 0) {
                sendError('Profile not found', 404);
            }
            
            sendResponse([
                'success' => true,
                'message' => 'Профиль успешно обновлен'
            ]);
            
        } catch (PDOException $e) {
            sendError('Failed to update profile: ' . $e->getMessage(), 500);
        }
        break;
    
    default:
        sendError('Invalid action. Available actions: save, list, get, delete, update');
}
