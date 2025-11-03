<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60; // 1 minute
    public $tries = 3;
    public $maxExceptions = 2;

    
    public function __construct(
        public string $type,
        public array $recipients,
        public array $data,
        public array $options = []
    ) {
        $this->onQueue('notifications');
    }

    
    public function handle(): void
    {
        try {
            Log::info("Sending notification", [
                'type' => $this->type,
                'recipients_count' => count($this->recipients)
            ]);

            switch ($this->type) {
                case 'email':
                    $this->sendEmailNotification();
                    break;
                    
                case 'system':
                    $this->sendSystemNotification();
                    break;
                    
                case 'alert':
                    $this->sendAlertNotification();
                    break;
                    
                default:
                    throw new \InvalidArgumentException("Unsupported notification type: {$this->type}");
            }

            Log::info("Notification sent successfully", [
                'type' => $this->type,
                'recipients_count' => count($this->recipients)
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to send notification", [
                'type' => $this->type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    
    private function sendEmailNotification(): void
    {
        $subject = $this->data['subject'] ?? 'Portfolio Analytics Notification';
        $message = $this->data['message'] ?? '';
        $template = $this->data['template'] ?? 'emails.notification';

        foreach ($this->recipients as $recipient) {
            try {
                Mail::send($template, $this->data, function ($mail) use ($recipient, $subject) {
                    $mail->to($recipient['email'], $recipient['name'] ?? '')
                         ->subject($subject);
                });
                
                Log::debug("Email sent to: {$recipient['email']}");
            } catch (\Exception $e) {
                Log::error("Failed to send email to: {$recipient['email']}", [
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    
    private function sendSystemNotification(): void
    {
        
        foreach ($this->recipients as $recipient) {
            try {
                
                Log::info("System notification", [
                    'recipient' => $recipient,
                    'title' => $this->data['title'] ?? 'Notification',
                    'message' => $this->data['message'] ?? '',
                    'priority' => $this->data['priority'] ?? 'normal'
                ]);
                
            } catch (\Exception $e) {
                Log::error("Failed to send system notification", [
                    'recipient' => $recipient,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    
    private function sendAlertNotification(): void
    {
        $alertLevel = $this->data['level'] ?? 'info';
        $alertMessage = $this->data['message'] ?? 'Alert notification';
        
        foreach ($this->recipients as $recipient) {
            try {
                
                Log::alert("Alert notification", [
                    'level' => $alertLevel,
                    'message' => $alertMessage,
                    'recipient' => $recipient,
                    'timestamp' => now()->toISOString(),
                    'context' => $this->data['context'] ?? []
                ]);
                
            } catch (\Exception $e) {
                Log::error("Failed to send alert notification", [
                    'recipient' => $recipient,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    
    public function failed(\Throwable $exception): void
    {
        Log::error("Notification job failed permanently", [
            'type' => $this->type,
            'recipients_count' => count($this->recipients),
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        if ($this->type !== 'alert') {
            try {
                Log::critical("Critical: Notification system failure", [
                    'original_type' => $this->type,
                    'failure_reason' => $exception->getMessage()
                ]);
            } catch (\Exception $e) {
                error_log("Notification system critical failure: " . $exception->getMessage());
            }
        }
    }

    
    public function tags(): array
    {
        return ['notification', $this->type, 'recipients:' . count($this->recipients)];
    }
}


