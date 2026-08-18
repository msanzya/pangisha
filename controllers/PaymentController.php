<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/auth.php';

class PaymentController {
    private $db;
    
    public function __construct() {
        global $db;
        $this->db = $db;
    }
    
    public function processRentPayment() {
        if(!isLoggedIn()) {
            header("Location: ".BASE_URL."login.php");
            exit;
        }
        
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errors = [];
            
            $contractId = (int)$_POST['contract_id'];
            $amount = (float)$_POST['amount'];
            $paymentMethod = $_POST['payment_method'];
            $phone = $_POST['phone'] ?? null;
            
            // Validate payment
            $stmt = $this->db->prepare("
                SELECT c.*, p.title as property_title
                FROM rent_contracts c
                JOIN properties p ON c.property_id = p.id
                WHERE c.id = ? AND c.tenant_id = (
                    SELECT id FROM tenants WHERE user_id = ?
                )
            ");
            $stmt->execute([$contractId, $_SESSION['user_id']]);
            $contract = $stmt->fetch();
            
            if(!$contract) {
                $errors[] = "Invalid contract";
            } elseif($amount <= 0) {
                $errors[] = "Amount must be positive";
            } elseif($amount < $contract['monthly_rent']) {
                $errors[] = "Amount cannot be less than monthly rent";
            }
            
            if(empty($errors)) {
                try {
                    $this->db->beginTransaction();
                    
                    // Process payment (simulated)
                    $transactionId = 'PAY-'.uniqid();
                    $status = 'success'; // Assume success for demo
                    
                    // Record payment
                    $stmt = $this->db->prepare("
                        INSERT INTO payments (
                            contract_id, tenant_id, amount, payment_type,
                            payment_method, status, transaction_id
                        ) VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $contractId,
                        $contract['tenant_id'],
                        $amount,
                        'rent',
                        $paymentMethod,
                        $status,
                        $transactionId
                    ]);
                    
                    // Update contract if needed
                    if($status === 'success') {
                        $stmt = $this->db->prepare("
                            UPDATE rent_contracts
                            SET last_payment_date = NOW()
                            WHERE id = ?
                        ");
                        $stmt->execute([$contractId]);
                    }
                    
                    $this->db->commit();
                    
                    $_SESSION['payment_success'] = [
                        'amount' => $amount,
                        'property' => $contract['property_title'],
                        'transaction_id' => $transactionId
                    ];
                    
                    header("Location: ".BASE_URL."payment_success.php");
                    exit;
                } catch(Exception $e) {
                    $this->db->rollBack();
                    $errors[] = "Payment processing failed: ".$e->getMessage();
                }
            }
            
            $_SESSION['payment_errors'] = $errors;
            header("Location: ".BASE_URL."pay_rent.php?contract=".$contractId);
            exit;
        }
    }
    
    public function paymentForm() {
        if(!isLoggedIn()) {
            header("Location: ".BASE_URL."login.php");
            exit;
        }
        
        if(!isTenant()) {
            header("Location: ".BASE_URL."login.php");
            exit;
        }
        
        $contractId = isset($_GET['contract']) ? (int)$_GET['contract'] : null;
        
        if($contractId) {
            $stmt = $this->db->prepare("
                SELECT c.*, p.title as property_title, p.rent_price
                FROM rent_contracts c
                JOIN properties p ON c.property_id = p.id
                WHERE c.id = ? AND c.tenant_id = (
                    SELECT id FROM tenants WHERE user_id = ?
                ) AND c.status = 'active'
            ");
            $stmt->execute([$contractId, $_SESSION['user_id']]);
            $contract = $stmt->fetch();
            
            if(!$contract) {
                header("Location: ".BASE_URL."dashboard.php");
                exit;
            }
        } else {
            // Get tenant's active contract
            $stmt = $this->db->prepare("
                SELECT c.*, p.title as property_title, p.rent_price
                FROM rent_contracts c
                JOIN properties p ON c.property_id = p.id
                WHERE c.tenant_id = (
                    SELECT id FROM tenants WHERE user_id = ?
                ) AND c.status = 'active'
                LIMIT 1
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $contract = $stmt->fetch();
            
            if(!$contract) {
                header("Location: ".BASE_URL."dashboard.php");
                exit;
            }
        }
        
        $pageTitle = "Pay Rent";
        require_once __DIR__.'/../views/layouts/header.php';
        require_once __DIR__.'/../views/payments/rent.php';
        require_once __DIR__.'/../views/layouts/footer.php';
    }
    
    public function paymentSuccess() {
        if(!isLoggedIn()) {
            header("Location: ".BASE_URL."login.php");
            exit;
        }
        
        if(!isset($_SESSION['payment_success'])) {
            header("Location: ".BASE_URL."dashboard.php");
            exit;
        }
        
        $payment = $_SESSION['payment_success'];
        unset($_SESSION['payment_success']);
        
        $pageTitle = "Payment Successful";
        require_once __DIR__.'/../views/layouts/header.php';
        require_once __DIR__.'/../views/payments/success.php';
        require_once __DIR__.'/../views/layouts/footer.php';
    }
    
    public function paymentHistory() {
        if(!isLoggedIn()) {
            header("Location: ".BASE_URL."login.php");
            exit;
        }
        
        $tenantId = null;
        $whereClause = "";
        $params = [];
        
        if(isTenant()) {
            $stmt = $this->db->prepare("SELECT id FROM tenants WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $tenant = $stmt->fetch();
            $tenantId = $tenant['id'];
            $whereClause = "WHERE p.tenant_id = ?";
            $params[] = $tenantId;
        } elseif(isLandlord()) {
            // Landlord can see payments for their properties
            $whereClause = "WHERE c.property_id IN (
                SELECT id FROM properties WHERE landlord_id = (
                    SELECT id FROM landlords WHERE user_id = ?
                )
            )";
            $params[] = $_SESSION['user_id'];
        } elseif(isAgent()) {
            // Agent can see payments for properties they manage
            $whereClause = "WHERE c.property_id IN (
                SELECT id FROM properties WHERE agent_id = (
                    SELECT id FROM agents WHERE user_id = ?
                )
            )";
            $params[] = $_SESSION['user_id'];
        }
        
        $stmt = $this->db->prepare("
            SELECT p.*, c.property_id, pr.title as property_title,
                   t.name as tenant_name, u.name as landlord_name
            FROM payments p
            JOIN rent_contracts c ON p.contract_id = c.id
            JOIN properties pr ON c.property_id = pr.id
            JOIN tenants t ON p.tenant_id = t.id
            LEFT JOIN landlords l ON pr.landlord_id = l.id
            LEFT JOIN users u ON l.user_id = u.id
            $whereClause
            ORDER BY p.payment_date DESC
        ");
        $stmt->execute($params);
        $payments = $stmt->fetchAll();
        
        $pageTitle = "Payment History";
        require_once __DIR__.'/../views/layouts/header.php';
        require_once __DIR__.'/../views/payments/history.php';
        require_once __DIR__.'/../views/layouts/footer.php';
    }
}

// Route handling
$controller = new PaymentController();

if(isset($_GET['action'])) {
    switch($_GET['action']) {
        case 'rent':
            $controller->paymentForm();
            break;
        case 'process':
            $controller->processRentPayment();
            break;
        case 'success':
            $controller->paymentSuccess();
            break;
        case 'history':
            $controller->paymentHistory();
            break;
        default:
            header("Location: ".BASE_URL."dashboard.php");
    }
} else {
    header("Location: ".BASE_URL."dashboard.php");
}