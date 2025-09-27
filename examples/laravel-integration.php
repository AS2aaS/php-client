<?php

/**
 * Laravel Integration Examples
 * 
 * These examples show how to use AS2aaS in a Laravel application
 */

namespace App\Http\Controllers;

use AS2aaS\Client;
use AS2aaS\Laravel\Facades\AS2;
use AS2aaS\Exceptions\AS2Error;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Models\Order;
use App\Mail\MessageDeliveredMail;
use App\Mail\MessageFailedMail;
use Illuminate\Support\Facades\Mail;

/**
 * Order Controller - Managing EDI orders via AS2
 */
class OrderController extends Controller
{
    /**
     * Send purchase order via AS2
     */
    public function sendPurchaseOrder(Request $request, Client $as2): JsonResponse
    {
        try {
            // Get partner
            $partner = $as2->partners()->getByAs2Id($request->input('partner_as2_id'));
            
            // Generate EDI content (simplified)
            $ediContent = $this->generateEDIPurchaseOrder($request->all());
            
            // Send message
            $message = $as2->messages()->send(
                $partner,
                $ediContent,
                "Purchase Order #{$request->input('order_number')}"
            );
            
            // Store message reference
            Order::where('id', $request->input('order_id'))
                ->update([
                    'as2_message_id' => $message->getId(),
                    'status' => 'sent'
                ]);
            
            return response()->json([
                'success' => true,
                'message_id' => $message->getId(),
                'status' => $message->getStatus()
            ]);
            
        } catch (AS2Error $e) {
            Log::error('AS2 Error sending purchase order', [
                'error' => $e->getMessage(),
                'code' => $e->getErrorCode(),
                'order_id' => $request->input('order_id')
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }
    
    /**
     * Send multiple orders in batch
     */
    public function sendBatchOrders(Request $request): JsonResponse
    {
        $orders = $request->input('orders');
        $messages = [];
        
        foreach ($orders as $orderData) {
            $messages[] = [
                'partner' => $orderData['partner_as2_id'],
                'content' => $this->generateEDIPurchaseOrder($orderData),
                'subject' => "Purchase Order #{$orderData['order_number']}"
            ];
        }
        
        try {
            $results = AS2::messages()->sendBatch($messages);
            
            return response()->json([
                'success' => true,
                'sent' => count($results['successful']),
                'failed' => count($results['failed']),
                'total' => $results['total']
            ]);
            
        } catch (AS2Error $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }
    
    /**
     * Get order status
     */
    public function getOrderStatus(string $orderId): JsonResponse
    {
        $order = Order::findOrFail($orderId);
        
        if (!$order->as2_message_id) {
            return response()->json([
                'status' => 'not_sent',
                'message' => 'Order has not been sent via AS2'
            ]);
        }
        
        try {
            $message = AS2::messages()->get($order->as2_message_id);
            
            return response()->json([
                'status' => $message->getStatus(),
                'message_id' => $message->getId(),
                'sent_at' => $message->getSentAt()?->toISOString(),
                'delivered_at' => $message->getDeliveredAt()?->toISOString(),
                'has_error' => $message->hasError(),
                'error_message' => $message->getErrorMessage()
            ]);
            
        } catch (AS2Error $e) {
            return response()->json([
                'status' => 'error',
                'error' => $e->getMessage()
            ], 400);
        }
    }
    
    private function generateEDIPurchaseOrder(array $orderData): string
    {
        // Simplified EDI generation - in real app, use proper EDI library
        $isa = sprintf(
            "ISA*00*          *00*          *ZZ*%-15s*ZZ*%-15s*%s*%s*U*00401*%09d*0*T*>~",
            config('app.company_as2_id'),
            $orderData['partner_as2_id'],
            date('ymd'),
            date('Hi'),
            rand(1, 999999999)
        );
        
        return $isa . "GS*PO*SENDER*RECEIVER*" . date('Ymd') . "*" . date('Hi') . "*1*X*004010~" .
               "ST*850*0001~" .
               "BEG*00*SA*{$orderData['order_number']}*" . date('Ymd') . "~" .
               "SE*4*0001~" .
               "GE*1*1~" .
               "IEA*1*000000001~";
    }
}

/**
 * Partner Management Controller
 */
class PartnerController extends Controller
{
    /**
     * List all partners
     */
    public function index(): JsonResponse
    {
        try {
            $partners = AS2::partners()->list();
            
            return response()->json([
                'partners' => array_map(function($partner) {
                    return [
                        'id' => $partner->getId(),
                        'name' => $partner->getName(),
                        'as2_id' => $partner->getAs2Id(),
                        'active' => $partner->isActive(),
                        'type' => $partner->getType(),
                        'signing' => $partner->isSigningEnabled(),
                        'encryption' => $partner->isEncryptionEnabled()
                    ];
                }, $partners)
            ]);
            
        } catch (AS2Error $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
    
    /**
     * Test partner connectivity
     */
    public function testPartner(string $partnerId): JsonResponse
    {
        try {
            $result = AS2::partners()->test($partnerId);
            
            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'duration' => $result['duration']
            ]);
            
        } catch (AS2Error $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}

/**
 * Webhook Handler Controller
 */
class WebhookController extends Controller
{
    /**
     * Handle AS2aaS webhooks
     */
    public function handle(Request $request)
    {
        // Verify webhook signature
        $signature = $request->header('X-Signature');
        $payload = $request->getContent();
        
        if (!AS2::webhooks()->verifySignature($payload, $signature, config('as2aas.webhooks.secret'))) {
            abort(401, 'Invalid webhook signature');
        }
        
        $event = json_decode($payload, true);
        
        Log::info('AS2 Webhook received', ['event' => $event['type'], 'id' => $event['data']['id'] ?? null]);
        
        // Handle different event types
        AS2::webhooks()->handleEvent($event, [
            'message.delivered' => [$this, 'handleMessageDelivered'],
            'message.failed' => [$this, 'handleMessageFailed'],
            'message.received' => [$this, 'handleMessageReceived'],
            'partner.test.completed' => [$this, 'handlePartnerTestCompleted'],
        ]);
        
        return response('OK');
    }
    
    public function handleMessageDelivered(array $data): void
    {
        // Update order status
        Order::where('as2_message_id', $data['id'])
            ->update([
                'status' => 'delivered',
                'delivered_at' => now()
            ]);
        
        // Send notification email
        $order = Order::where('as2_message_id', $data['id'])->first();
        if ($order) {
            Mail::to($order->contact_email)->send(new MessageDeliveredMail($order, $data));
        }
        
        Log::info('Message delivered', ['message_id' => $data['id'], 'partner' => $data['partner']['name']]);
    }
    
    public function handleMessageFailed(array $data): void
    {
        // Update order status
        Order::where('as2_message_id', $data['id'])
            ->update([
                'status' => 'failed',
                'error_message' => $data['error']['message'] ?? 'Unknown error'
            ]);
        
        // Send alert email
        Mail::to(config('app.admin_email'))->send(new MessageFailedMail($data));
        
        Log::error('Message failed', [
            'message_id' => $data['id'],
            'error' => $data['error']['message'] ?? 'Unknown error'
        ]);
    }
    
    public function handleMessageReceived(array $data): void
    {
        // Process incoming message
        Log::info('Message received', [
            'message_id' => $data['id'],
            'from' => $data['partner']['name'],
            'subject' => $data['subject']
        ]);
        
        // Queue job to process the EDI content
        ProcessIncomingEDI::dispatch($data['id']);
    }
    
    public function handlePartnerTestCompleted(array $data): void
    {
        Log::info('Partner test completed', [
            'partner_id' => $data['partner_id'],
            'success' => $data['success'],
            'message' => $data['message']
        ]);
    }
}

/**
 * Certificate Management Controller
 */
class CertificateController extends Controller
{
    /**
     * Upload certificate
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string',
            'certificate' => 'required|file',
            'type' => 'required|in:identity,partner',
        ]);
        
        try {
            $certificate = AS2::certificates()->upload([
                'name' => $request->input('name'),
                'file' => $request->file('certificate')->getPathname(),
                'type' => $request->input('type'),
                'partnerId' => $request->input('partner_id'),
            ]);
            
            return response()->json([
                'success' => true,
                'certificate' => [
                    'id' => $certificate->getId(),
                    'name' => $certificate->getName(),
                    'type' => $certificate->getType(),
                    'expires_at' => $certificate->getExpiresAt()?->toISOString()
                ]
            ]);
            
        } catch (AS2Error $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }
    
    /**
     * List certificates with expiry warnings
     */
    public function index(): JsonResponse
    {
        try {
            $certificates = AS2::certificates()->list();
            
            return response()->json([
                'certificates' => array_map(function($cert) {
                    return [
                        'id' => $cert->getId(),
                        'name' => $cert->getName(),
                        'type' => $cert->getType(),
                        'active' => $cert->isActive(),
                        'expires_at' => $cert->getExpiresAt()?->toISOString(),
                        'days_until_expiry' => $cert->getDaysUntilExpiry(),
                        'is_expired' => $cert->isExpired(),
                        'is_expiring_soon' => $cert->isExpiringSoon(30)
                    ];
                }, $certificates)
            ]);
            
        } catch (AS2Error $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}

/**
 * Job for processing incoming EDI messages
 */
class ProcessIncomingEDI implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    private string $messageId;
    
    public function __construct(string $messageId)
    {
        $this->messageId = $messageId;
    }
    
    public function handle(): void
    {
        try {
            // Get message payload
            $content = AS2::messages()->getPayload($this->messageId);
            
            // Parse EDI content and create order/invoice/etc
            $this->processEDIContent($content);
            
            Log::info('Incoming EDI processed', ['message_id' => $this->messageId]);
            
        } catch (AS2Error $e) {
            Log::error('Failed to process incoming EDI', [
                'message_id' => $this->messageId,
                'error' => $e->getMessage()
            ]);
            
            throw $e; // Re-throw to trigger job retry
        }
    }
    
    private function processEDIContent(string $content): void
    {
        // Parse EDI and create appropriate records
        // This would use a proper EDI parsing library
        Log::info('Processing EDI content', ['length' => strlen($content)]);
    }
}
