<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../helpers/ui/Flash.php';
require_once __DIR__ . '/../../helpers/security/CsrfHelper.php';
require_once __DIR__ . '/../../helpers/validation/ValidationHelper.php';
require_once __DIR__ . '/../../services/email/MailService.php';
require_once __DIR__ . '/../../services/payment/StripeService.php';
require_once __DIR__ . '/../../services/CheckoutService.php';
require_once __DIR__ . '/../../services/OrderService.php';
require_once __DIR__ . '/../../models/entities/Product.php';
require_once __DIR__ . '/../../models/repositories/Cart.php';
require_once __DIR__ . '/../../models/entities/Order.php';
require_once __DIR__ . '/../../models/entities/User.php';

class CheckoutController
{
    private PDO $pdo;
    private OrderService $orderService;
    private CheckoutService $checkoutService;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->orderService = new OrderService($pdo);
        $this->checkoutService = new CheckoutService($pdo);
    }

    // ─── Helpers privati ──────────────────────────────────────────────────────

    private function getUserId(): ?int
    {
        return isset($_SESSION['user_id'])
            ? (int)$_SESSION['user_id']
            : (isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null);
    }

    private function getAppUrl(): string
    {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                 || ((int)($_SERVER['SERVER_PORT'] ?? 80) === 443);
        return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . BASE_URL;
    }

    // ─── Pagina checkout ──────────────────────────────────────────────────────

    public function index(): void
    {
        $userId = $this->getUserId();
        $items = $this->checkoutService->getCartItems($userId);

        if (empty($items)) {
            header('Location: ' . BASE_URL . '/index.php?r=cart/index');
            exit;
        }

        try {
            $this->orderService->validateCartStock($items);
        } catch (Throwable $e) {
            Flash::error($e->getMessage(), BASE_URL . '/index.php?r=cart/index');
        }

        $walletBalance = $userId ? $this->checkoutService->getWalletBalance($userId) : 0.0;
        $total = $this->checkoutService->getTotal($items);
        $flash = Flash::get();
        $pdo = $this->pdo;

        require __DIR__ . '/../../views/layouts/header.php';
        require __DIR__ . '/../../views/checkout/index.php';
        require __DIR__ . '/../../views/layouts/footer.php';
    }

    public function success(): void
    {
        $orderId = $_SESSION['last_order_id'] ?? null;
        $orderEmail = $_SESSION['last_order_email'] ?? null;

        if (!empty($_GET['session_id'])) {
            $order = (new Order($this->pdo))->findByStripeSessionId($_GET['session_id']);
            if ($order) {
                $orderId = $order['id'];
                $orderEmail = $order['customer_email'];
                if ($order['status'] !== 'paid') {
                    $this->orderService->completeOrder($orderId);
                }
            }
        }

        if (!$orderId && !empty($_SESSION['last_order_id'])) {
            $orderId = $_SESSION['last_order_id'];
            $orderEmail = $_SESSION['last_order_email'] ?? $orderEmail;
            $order = (new Order($this->pdo))->findById($orderId);
            if ($order && $order['status'] !== 'paid') {
                $this->orderService->completeOrder($orderId);
            }
        }

        unset($_SESSION['last_order_id'], $_SESSION['last_order_email']);

        if (!$orderId) {
            header('Location: ' . BASE_URL . '/index.php');
            exit;
        }

        $pdo = $this->pdo;
        require __DIR__ . '/../../views/layouts/header.php';
        require __DIR__ . '/../../views/checkout/success.php';
        require __DIR__ . '/../../views/layouts/footer.php';
    }

    // ─── Process ordine ───────────────────────────────────────────────────────

    public function process(): void
    {
        CsrfHelper::validate();

        $userId = $this->getUserId();
        $items = $this->checkoutService->getCartItems($userId);
        if (empty($items)) {
            header('Location: ' . BASE_URL . '/index.php?r=cart/index');
            exit;
        }

        $total = $this->checkoutService->getTotal($items);
        $walletBalance = $userId ? $this->checkoutService->getWalletBalance($userId) : 0.0;

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $paymentMethod = trim($_POST['payment_method'] ?? 'card');
        $notes = trim($_POST['notes'] ?? '');
        $noteValue = $notes !== '' ? $notes : null;
        $redirectError = BASE_URL . '/index.php?r=checkout/index';

        if (!ValidationHelper::notEmpty($name) || !ValidationHelper::notEmpty($email)) {
            Flash::error('Nome ed email sono obbligatori.', $redirectError);
        }

        if (!ValidationHelper::email($email)) {
            Flash::error('Indirizzo email non valido.', $redirectError);
        }

        try {
            $this->orderService->validateCartStock($items);
        } catch (Throwable $e) {
            Flash::error($e->getMessage(), $redirectError);
        }

        $orderModel = new Order($this->pdo);

        try {
            if ($paymentMethod === 'wallet') {
                $orderId = $this->checkoutService->processWalletOrder($userId ?? 0, $name, $email, $noteValue, $items, $total);
                if (isset($_SESSION['user'])) {
                    $_SESSION['user']['wallet_balance'] = max(0, $walletBalance - $total);
                }
                $_SESSION['last_order_id'] = $orderId;
                $_SESSION['last_order_email'] = $email;
                header('Location: ' . BASE_URL . '/index.php?r=checkout/success');
                exit;
            }

            if ($paymentMethod === 'card') {
                $orderId = $this->checkoutService->processCardOrder($userId, $name, $email, $noteValue, $items, $total);
                $_SESSION['last_order_id'] = $orderId;
                $_SESSION['last_order_email'] = $email;
                $this->redirectToStripe($orderId, $total, $email, 'Ordine #' . $orderId, $orderModel);
            }

            if ($paymentMethod === 'mixed') {
                $result = $this->checkoutService->processMixedOrder($userId ?? 0, $name, $email, $noteValue, $items, $total, $walletBalance);
                $orderId = $result['order_id'];
                $stripeAmount = $result['stripe_amount'];
                $_SESSION['last_order_id'] = $orderId;
                $_SESSION['last_order_email'] = $email;

                if (isset($_SESSION['user'])) {
                    $_SESSION['user']['wallet_balance'] = max(0, $walletBalance - min($walletBalance, $total));
                }

                $this->redirectToStripe($orderId, $stripeAmount, $email, 'Saldo residuo ordine #' . $orderId, $orderModel);
            }

            if ($paymentMethod === 'paypal') {
                Flash::error('PayPal non è ancora disponibile. Scegli un altro metodo.', $redirectError);
            }

            Flash::error('Metodo di pagamento non valido.', $redirectError);
        } catch (Throwable $e) {
            Flash::error('Errore checkout: ' . $e->getMessage(), $redirectError);
        }
    }

    // ─── Redirect a Stripe ────────────────────────────────────────────────────

    private function redirectToStripe(int $orderId, float $amount, string $email, string $productName, Order $orderModel): never
    {
        $redirectError = BASE_URL . '/index.php?r=checkout/index';
        try {
            $stripe  = new StripeService();
            $session = $stripe->createOrderSession($orderId, $amount, $email, $productName, $this->getAppUrl());
            $orderModel->updateStripeSessionId($orderId, $session->id);
            header('Location: ' . $session->url);
            exit;
        } catch (Throwable $e) {
            Flash::error('Errore Stripe: ' . $e->getMessage(), $redirectError);
        }
    }
}
