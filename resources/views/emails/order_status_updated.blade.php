<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Order Status Update - {{ $order->order_number }}</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #1e293b; margin: 0; padding: 24px 0;">
  <table width="100%" border="0" cellspacing="0" cellpadding="0">
    <tr>
      <td align="center">
        <table width="600" border="0" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
          <!-- Header -->
          <tr>
            <td style="background-color: #0f172a; padding: 24px 32px; text-align: center;">
              <h1 style="color: #ffffff; margin: 0; font-size: 22px; font-weight: 800; letter-spacing: 0.05em; text-transform: uppercase;">
                {{ $order->store->name ?? 'FlexiStore Pro' }}
              </h1>
              <p style="color: #94a3b8; margin: 4px 0 0 0; font-size: 13px; text-transform: uppercase; letter-spacing: 0.08em;">
                Shipment & Delivery Notification
              </p>
            </td>
          </tr>

          <!-- Dynamic Status Banner -->
          @php
            $statusColors = [
              'processing' => ['bg' => '#fef3c7', 'text' => '#92400e', 'title' => 'Order is Being Packed', 'desc' => 'Our fulfillment team is preparing and quality-inspecting your package.'],
              'shipped' => ['bg' => '#e0f2fe', 'text' => '#0369a1', 'title' => 'Order Dispatched with Courier!', 'desc' => 'Your parcel is in transit with our express delivery partner.'],
              'delivered' => ['bg' => '#dcfce7', 'text' => '#15803d', 'title' => 'Order Delivered Successfully!', 'desc' => 'Your parcel has been delivered to your address. Thank you for shopping with us!'],
              'cancelled' => ['bg' => '#fee2e2', 'text' => '#b91c1c', 'title' => 'Order Has Been Cancelled', 'desc' => 'This order was cancelled. If you believe this was in error, please contact customer support.'],
            ];
            $currentStatus = $statusColors[$order->status] ?? ['bg' => '#f1f5f9', 'text' => '#334155', 'title' => 'Status Updated to ' . ucfirst($order->status), 'desc' => 'Your order status has changed.'];
          @endphp

          <tr>
            <td style="padding: 28px 32px 16px 32px; text-align: center;">
              <div style="display: inline-block; background-color: {{ $currentStatus['bg'] }}; color: {{ $currentStatus['text'] }}; padding: 10px 24px; border-radius: 999px; font-weight: 800; font-size: 14px; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px;">
                {{ $currentStatus['title'] }}
              </div>
              <p style="font-size: 14px; color: #64748b; margin: 0; line-height: 1.5;">
                {{ $currentStatus['desc'] }}
              </p>
            </td>
          </tr>

          <!-- Logistics & Rider Details (If Shipped) -->
          @if($order->status === 'shipped' && $order->deliveryRider)
          <tr>
            <td style="padding: 0 32px 20px 32px;">
              <table width="100%" style="background-color: #f0fdf4; border: 1px solid #86efac; border-radius: 8px; padding: 14px; font-size: 13px;">
                <tr>
                  <td>
                    <div style="font-weight: 800; color: #166534; font-size: 12px; text-transform: uppercase; margin-bottom: 4px;">
                      🚚 ASSIGNED COURIER RIDER:
                    </div>
                    <div style="font-size: 14px; font-weight: 800; color: #0f172a;">{{ $order->deliveryRider->name }}</div>
                    <div style="color: #16a34a; font-weight: 700; margin-top: 2px;">Rider Contact: {{ $order->deliveryRider->phone ?? 'Available upon dispatch' }}</div>
                    <div style="color: #64748b; font-size: 12px; margin-top: 4px;">Estimated Arrival: 24–48 hours</div>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          @endif

          <!-- Order Summary Card -->
          <tr>
            <td style="padding: 0 32px 20px 32px;">
              <table width="100%" style="background-color: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0; padding: 16px; font-size: 13px;">
                <tr>
                  <td style="color: #64748b; font-weight: 600;">ORDER NUMBER:</td>
                  <td align="right" style="font-weight: 800; color: #0f172a;">{{ $order->order_number }}</td>
                </tr>
                <tr>
                  <td style="color: #64748b; font-weight: 600; padding-top: 6px;">TOTAL AMOUNT:</td>
                  <td align="right" style="font-weight: 800; color: #0f172a; padding-top: 6px;">Rs. {{ number_format($order->final_amount, 0) }}</td>
                </tr>
                <tr>
                  <td style="color: #64748b; font-weight: 600; padding-top: 6px;">DELIVERY ADDRESS:</td>
                  <td align="right" style="font-weight: 600; color: #334155; padding-top: 6px;">{{ $order->shipping_address }}</td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Track Live Status Button -->
          <tr>
            <td style="padding: 0 32px 28px 32px; text-align: center;">
              <a href="http://localhost:5173/track-order?order_number={{ $order->order_number }}&phone={{ urlencode($order->phone ?? '') }}"
                 style="display: inline-block; background-color: #0f172a; color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 8px; font-size: 14px; font-weight: 800; letter-spacing: 0.03em;">
                🔍 View Live Order Status & Stepper
              </a>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 18px 32px; text-align: center; font-size: 12px; color: #94a3b8;">
              <p style="margin: 0;">© {{ date('Y') }} {{ $order->store->name ?? 'FlexiStore Pro' }}. All rights reserved.</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
