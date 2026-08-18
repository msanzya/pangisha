<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/auth.php';

class PropertyController {
    private $db;
    
    public function __construct() {
        global $db;
        $this->db = $db;
    }
    
    public function index() {
        if(!isLoggedIn()) {
            header("Location: ".BASE_URL."login.php");
            exit;
        }
        
        $stmt = $this->db->query("
            SELECT p.*, u.name as landlord_name, a.user_id as agent_id
            FROM properties p
            LEFT JOIN landlords l ON p.landlord_id = l.id
            LEFT JOIN users u ON l.user_id = u.id
            LEFT JOIN agents a ON p.agent_id = a.id
            ORDER BY p.created_at DESC
        ");
        $properties = $stmt->fetchAll();
        
        $pageTitle = "All Properties";
        require_once __DIR__.'/../views/layouts/header.php';
        require_once __DIR__.'/../views/properties/index.php';
        require_once __DIR__.'/../views/layouts/footer.php';
    }
    
    public function show($id) {
        if(!isLoggedIn()) {
            header("Location: ".BASE_URL."login.php");
            exit;
        }
        
        $stmt = $this->db->prepare("
            SELECT p.*, u.name as landlord_name,
                   a.user_id as agent_id, u2.name as agent_name
            FROM properties p
            LEFT JOIN landlords l ON p.landlord_id = l.id
            LEFT JOIN users u ON l.user_id = u.id
            LEFT JOIN agents a ON p.agent_id = a.id
            LEFT JOIN users u2 ON a.user_id = u2.id
            WHERE p.id = ?
        ");
        $stmt->execute([$id]);
        $property = $stmt->fetch();
        
        if(!$property) {
            header("Location: ".BASE_URL."properties.php");
            exit;
        }
        
        $pageTitle = $property['title'];
        require_once __DIR__.'/../views/layouts/header.php';
        require_once __DIR__.'/../views/properties/show.php';
        require_once __DIR__.'/../views/layouts/footer.php';
    }
    
    public function create() {
        if(!isLoggedIn()) {
            header("Location: ".BASE_URL."login.php");
            exit;
        }
        
        if(isLandlord() || isAgent()) {
            $pageTitle = "Add New Property";
            require_once __DIR__.'/../views/layouts/header.php';
            require_once __DIR__.'/../views/properties/create.php';
            require_once __DIR__.'/../views/layouts/footer.php';
        } else {
            header("Location: ".BASE_URL."login.php");
            exit;
        }
    }
    
    public function store() {
        if(!isLoggedIn()) {
            header("Location: ".BASE_URL."login.php");
            exit;
        }
        
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errors = [];
            
            // Validate inputs
            $title = trim($_POST['title']);
            $type = $_POST['type'];
            $description = trim($_POST['description']);
            $location = trim($_POST['location']);
            $rentPrice = (float)$_POST['rent_price'];
            $bedrooms = (int)$_POST['bedrooms'];
            $bathrooms = (int)$_POST['bathrooms'];
            
            if(empty($title)) $errors[] = "Title is required";
            if(empty($description)) $errors[] = "Description is required";
            if(empty($location)) $errors[] = "Location is required";
            if($rentPrice <= 0) $errors[] = "Rent price must be positive";
            
            // Handle file uploads
            $images = [];
            if(isset($_FILES['images'])) {
                $uploadDir = __DIR__.'/../../public/uploads/properties/';
                
                if(!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                foreach($_FILES['images']['tmp_name'] as $key => $tmpName) {
                    if($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                        $fileName = uniqid().'_'.basename($_FILES['images']['name'][$key]);
                        $targetPath = $uploadDir.$fileName;
                        
                        if(move_uploaded_file($tmpName, $targetPath)) {
                            $images[] = '/uploads/properties/'.$fileName;
                        }
                    }
                }
            }
            
            if(empty($errors)) {
                try {
                    $this->db->beginTransaction();
                    
                    // Get landlord/agent ID
                    if(isLandlord()) {
                        $stmt = $this->db->prepare("SELECT id FROM landlords WHERE user_id = ?");
                        $stmt->execute([$_SESSION['user_id']]);
                        $landlord = $stmt->fetch();
                        $landlordId = $landlord['id'];
                        $agentId = null;
                    } elseif(isAgent()) {
                        $stmt = $this->db->prepare("SELECT id FROM agents WHERE user_id = ?");
                        $stmt->execute([$_SESSION['user_id']]);
                        $agent = $stmt->fetch();
                        $agentId = $agent['id'];
                        
                        $landlordId = isset($_POST['landlord_id']) ? (int)$_POST['landlord_id'] : null;
                    }
                    
                    // Insert property
                    $stmt = $this->db->prepare("
                        INSERT INTO properties (
                            landlord_id, agent_id, title, type, description,
                            location, rent_price, bedrooms, bathrooms, images
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $landlordId,
                        $agentId,
                        $title,
                        $type,
                        $description,
                        $location,
                        $rentPrice,
                        $bedrooms,
                        $bathrooms,
                        json_encode($images)
                    ]);
                    
                    $this->db->commit();
                    
                    $_SESSION['success'] = "Property added successfully!";
                    header("Location: ".BASE_URL."properties.php");
                    exit;
                } catch(Exception $e) {
                    $this->db->rollBack();
                    $errors[] = "Error saving property: ".$e->getMessage();
                }
            }
            
            // If we got here, there were errors
            $_SESSION['errors'] = $errors;
            $_SESSION['form_data'] = $_POST;
            header("Location: ".BASE_URL."properties.php?action=create");
            exit;
        }
    }
    
    // ... other methods (edit, update, delete, etc)
}

// Route handling
$controller = new PropertyController();

if(isset($_GET['action'])) {
    switch($_GET['action']) {
        case 'show':
            $controller->show($_GET['id']);
            break;
        case 'create':
            $controller->create();
            break;
        case 'store':
            $controller->store();
            break;
        default:
            $controller->index();
    }
} else {
    $controller->index();
}