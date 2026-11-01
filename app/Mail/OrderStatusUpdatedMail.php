<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Order $order;
    public string $previousStatus;

    /**
     * Create a new message instance.
     */
    public function __construct(Order $order, string $previousStatus = '')
    {
        $this->order = $order;
        $this->previousStatus = $previousStatus;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $statusLabels = [
            'processing' => 'Is Being Packed & Prepared',
            'shipped' => 'Has Shipped with Courier 🚚',
            'delivered' => 'Has Been Delivered ✅',
            'cancelled' => 'Has Been Cancelled ⛔',
        ];

        $statusText = $statusLabels[$this->order->status] ?? "Status Updated to " . ucfirst($this->order->status);
        $storeName = $this->order->store->name ?? config('app.name', 'FlexiStore Pro');

        return new Envelope(
            subject: "Update on Order {$this->order->order_number}: {$statusText} — {$storeName}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.order_status_updated',
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
