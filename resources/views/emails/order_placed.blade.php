<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Order Confirmation - {{ $order->order_number }}</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #1e293b; margin: 0; padding: 24px 0;">
  <table width="100%" border="0" cellspacing="0" cellpadding="0">
    <tr>
      <td align="center">
        <table width="600" border="0" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
          <!-- Header -->
          <tr>
            <td style="background-color: #0f172a; padding: 28px 32px; text-align: center;">
              <h1 style="color: #ffffff; margin: 0; font-size: 24px; font-weight: 800; letter-spacing: 0.05em; text-transform: uppercase;">
                {{ $order->store->name ?? 'FlexiStore Pro' }}
              </h1>
              <p style="color: #94a3b8; margin: 6px 0 0 0; font-size: 13px; text-transform: uppercase; letter-spacing: 0.08em;">
                Official Order Receipt & Confirmation
              </p>
            </td>
          </tr>

          <!-- Confirmation Banner -->
          <tr>
            <td style="padding: 32px 32px 20px 32px; text-align: center;">
              <div style="display: inline-block; width: 52px; height: 52px; background-color: #dcfce7; color: #16a34a; border-radius: 50%; line-height: 52px; font-size: 26px; font-weight: bold; margin-bottom: 12px;">
                ✓
              </div>
              <h2 style="font-size: 20px; font-weight: 800; color: #0f172a; margin: 0 0 8px 0;">
                Thank You for Your Order, {{ $order->customer->name ?? 'Customer' }}!
              </h2>
              <p style="font-size: 14px; color: #64748b; margin: 0; line-height: 1.5;">
                Your order has been confirmed and is now queued for fulfillment at our dispatch atelier.
              </p>
            </td>
          </tr>

          <!-- Order Summary Card -->
          <tr>
            <td style="padding: 0 32px 20px 32px;">
              <table width="100%" style="background-color: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0; padding: 16px;">
                <tr>
                  <td style="font-size: 13px; color: #64748b; font-weight: 600;">ORDER REFERENCE:</td>
                  <td align="right" style="font-size: 14px; font-weight: 800; color: #0f172a;">{{ $order->order_number }}</td>
                </tr>
                <tr>
                  <td style="font-size: 13px; color: #64748b; font-weight: 600; padding-top: 6px;">ORDER DATE:</td>
                  <td align="right" style="font-size: 13px; color: #334155; padding-top: 6px;">{{ $order->created_at ? $order->created_at->format('M d, Y h:i A') : now()->format('M d, Y') }}</td>
                </tr>
                <tr>
                  <td style="font-size: 13px; color: #64748b; font-weight: 600; padding-top: 6px;">PAYMENT METHOD:</td>
                  <td align="right" style="font-size: 13px; font-weight: 700; color: {{ $order->payment_method === 'cod' ? '#b91c1c' : '#059669' }}; padding-top: 6px;">
                    {{ $order->payment_method === 'cod' ? '💵 Cash on Delivery (COD)' : '💳 Paid Online via Card' }}
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Items Table -->
          <tr>
            <td style="padding: 0 32px 20px 32px;">
              <h3 style="font-size: 14px; font-weight: 800; color: #475569; text-transform: uppercase; margin: 0 0 10px 0; letter-spacing: 0.05em;">
                Purchased Items ({{ count($order->items ?? []) }})
              </h3>
              <table width="100%" cellspacing="0" cellpadding="8" style="border-collapse: collapse; font-size: 13px;">
                <thead>
                  <tr style="border-bottom: 2px solid #e2e8f0; background-color: #f1f5f9; text-align: left;">
                    <th style="padding: 8px 10px; color: #475569; font-weight: 700;">Item</th>
                    <th style="padding: 8px 10px; color: #475569; font-weight: 700; text-align: center;">Qty</th>
                    <th style="padding: 8px 10px; color: #475569; font-weight: 700; text-align: right;">Price</th>
                    <th style="padding: 8px 10px; color: #475569; font-weight: 700; text-align: right;">Total</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($order->items as $item)
                  <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 10px;">
                      <div style="font-weight: 700; color: #0f172a;">{{ $item->product_name }}</div>
                      @if(!empty($item->selected_attributes))
                      <div style="font-size: 11px; color: #64748b; margin-top: 2px;">
                        @foreach($item->selected_attributes as $k => $v)
                          {{ strtoupper($k) }}: {{ $v }} &nbsp;
                        @endforeach
                      </div>
                      @endif
                    </td>
                    <td align="center" style="padding: 10px; font-weight: 700;">{{ $item->quantity }}</td>
                    <td align="right" style="padding: 10px; color: #64748b;">Rs. {{ number_format($item->price, 0) }}</td>
                    <td align="right" style="padding: 10px; font-weight: 700; color: #0f172a;">Rs. {{ number_format($item->subtotal ?? ($item->price * $item->quantity), 0) }}</td>
                  </tr>
                  @endforeach
                </tbody>
              </table>

              <!-- Totals -->
              <table width="100%" style="margin-top: 14px; font-size: 13px;">
                <tr>
                  <td style="color: #64748b;">Items Subtotal:</td>
                  <td align="right" style="color: #0f172a; font-weight: 600;">Rs. {{ number_format($order->total_amount ?? $order->final_amount, 0) }}</td>
                </tr>
                @if(($order->discount_amount ?? 0) > 0)
                <tr>
                  <td style="color: #16a34a; padding-top: 4px;">Discount:</td>
                  <td align="right" style="color: #16a34a; font-weight: 600; padding-top: 4px;">- Rs. {{ number_format($order->discount_amount, 0) }}</td>
                </tr>
                @endif
                <tr>
                  <td style="color: #64748b; padding-top: 4px;">Courier Express Delivery:</td>
                  <td align="right" style="color: #0f172a; font-weight: 600; padding-top: 4px;">
                    {{ ($order->shipping_cost ?? 0) > 0 ? 'Rs. ' . number_format($order->shipping_cost, 0) : 'Free Complimentary' }}
                  </td>
                </tr>
                <tr style="border-top: 2px solid #0f172a;">
                  <td style="padding-top: 8px; font-size: 16px; font-weight: 900; color: #0f172a;">TOTAL PAYABLE:</td>
                  <td align="right" style="padding-top: 8px; font-size: 17px; font-weight: 900; color: #0f172a;">Rs. {{ number_format($order->final_amount, 0) }}</td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Delivery Destination -->
          <tr>
            <td style="padding: 0 32px 28px 32px;">
              <table width="100%" style="background-color: #f1f5f9; border-radius: 8px; padding: 14px; font-size: 13px;">
                <tr>
                  <td>
                    <div style="font-weight: 800; color: #475569; text-transform: uppercase; font-size: 11px; margin-bottom: 4px;">
                      🚚 DELIVERY DESTINATION:
                    </div>
                    <div style="font-weight: 700; color: #0f172a;">{{ $order->customer->name ?? 'Customer' }}</div>
                    <div style="color: #475569; margin-top: 2px;">{{ $order->shipping_address ?? 'Address on file' }}</div>
                    <div style="color: #059669; font-weight: 700; margin-top: 4px;">Phone: {{ $order->phone ?? $order->customer->phone ?? 'N/A' }}</div>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Track Order CTA Button -->
          <tr>
            <td style="padding: 0 32px 32px 32px; text-align: center;">
              <a href="http://localhost:5173/track-order?order_number={{ $order->order_number }}&phone={{ urlencode($order->phone ?? '') }}"
                 style="display: inline-block; background-color: #0f172a; color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 8px; font-size: 14px; font-weight: 800; letter-spacing: 0.03em;">
                🔍 Track Live Parcel Progress
              </a>
              <p style="font-size: 12px; color: #94a3b8; margin: 10px 0 0 0;">
                Click above to monitor live packing and courier delivery updates anytime.
              </p>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 20px 32px; text-align: center; font-size: 12px; color: #94a3b8;">
              <p style="margin: 0;">Have questions about your order? Reach out to support at <a href="mailto:support@flexistore.local" style="color: #0f172a; font-weight: 600;">support@flexistore.local</a></p>
              <p style="margin: 6px 0 0 0;">© {{ date('Y') }} {{ $order->store->name ?? 'FlexiStore Pro' }}. All rights reserved.</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
