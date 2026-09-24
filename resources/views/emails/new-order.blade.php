<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>New Order Received</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #f4f6f8;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #374151;
            -webkit-font-smoothing: antialiased;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        .wrapper {
            width: 100%;
            padding: 40px 15px;
            background: #f4f6f8;
        }

        .container {
            max-width: 700px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        .header {
            padding: 35px 30px;
            background: #111827;
            color: #ffffff;
            text-align: center;
        }

        .header-title {
            font-size: 26px;
            font-weight: 700;
            margin: 0;
            letter-spacing: 0.5px;
        }

        .header-subtitle {
            margin-top: 8px;
            font-size: 15px;
            color: #9ca3af;
        }

        .order-code {
            display: inline-block;
            background: #374151;
            color: #ffffff;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
            margin-top: 15px;
            letter-spacing: 1px;
        }

        .content {
            padding: 35px 30px;
        }

        .status-box {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            border-radius: 8px;
            padding: 16px 20px;
            margin-bottom: 30px;
            text-align: center;
        }

        .status-title {
            color: #059669;
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .status-text {
            color: #047857;
            font-size: 14px;
        }

        .section-title {
            font-size: 14px;
            font-weight: 700;
            color: #111827;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 30px 0 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #f3f4f6;
        }

        .info-table {
            width: 100%;
            background: #f9fafb;
            border-radius: 8px;
            overflow: hidden;
        }

        .info-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }

        .info-table tr:last-child td {
            border-bottom: none;
        }

        .label {
            width: 35%;
            color: #6b7280;
            font-weight: 500;
        }

        .value {
            color: #111827;
            font-weight: 600;
        }

        /* INTEGRATED ITEMS STYLING FROM INVOICE */
        .items-table {
            width: 100%;
            margin-top: 10px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
        }

        .items-table th {
            background: #f3f4f6;
            color: #374151;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 14px 12px;
            text-align: left;
            border-bottom: 1px solid #d1d5db;
        }

        .items-table td {
            padding: 16px 12px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 13px;
            vertical-align: top;
        }

        .items-table tr:last-child td {
            border-bottom: none;
        }

        .item-name {
            font-weight: 700;
            font-size: 14px;
            color: #111827;
        }

        .customization {
            margin-top: 6px;
            color: #6b7280;
            font-size: 12px;
        }

        .option {
            margin-top: 4px;
            color: #6b7280;
            font-size: 12px;
        }

        .text-center { text-align: center !important; }
        .text-right { text-align: right !important; }
        
        .discount-text {
            color: #16a34a;
        }

        .summary-wrapper {
            margin-top: 25px;
            width: 100%;
        }

        .summary-table {
            width: 100%;
            max-width: 350px;
            margin-left: auto;
        }

        .summary-table td {
            padding: 8px 0;
            font-size: 14px;
        }

        .summary-label {
            text-align: right;
            color: #6b7280;
            padding-right: 20px !important;
        }

        .summary-value {
            text-align: right;
            color: #111827;
            font-weight: 600;
        }

        .grand-total td {
            border-top: 2px solid #111827;
            padding-top: 15px;
            margin-top: 5px;
            font-size: 18px;
            font-weight: 700;
            color: #111827;
        }

        .customer-box {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
        }

        .customer-box p {
            margin: 6px 0;
            font-size: 14px;
            line-height: 1.5;
        }

        .footer {
            background: #f9fafb;
            border-top: 1px solid #e5e7eb;
            padding: 30px;
            text-align: center;
            color: #6b7280;
            font-size: 13px;
            line-height: 1.6;
        }

        /* Mobile Responsiveness */
        @media only screen and (max-width: 600px) {
            .wrapper { padding: 15px 10px; }
            .content { padding: 25px 20px; }
            .header { padding: 25px 20px; }
            .header-title { font-size: 22px; }
            
            .items-table th, .items-table td {
                padding: 12px 8px;
                font-size: 12px;
            }
            .item-name { font-size: 13px; }
            .customization, .option { font-size: 11px; }
            
            /* Hide discount column on very small screens to save space, or keep it compact */
            .hide-mobile { display: none; }
            .summary-table { max-width: 100%; }
        }
    </style>
</head>

<body>

    @php
        use App\Enums\OrderTypeStatus;
        $orderCode = null;
        if (!empty($order->misc)) {
            $misc = is_array($order->misc) ? $order->misc : json_decode($order->misc, true);
            if (is_array($misc)) {
                $orderCode = $misc['order_code'] ?? null;
            }
        }
        
        $subTotal = (float) ($order->sub_total ?? 0);
        $discount = (float) ($order->discount ?? 0);
        $deliveryCharge = (float) ($order->delivery_charge ?? 200);
        $searchFee = (float) ($order->search_fee ?? 0);
        $handlingFee = (float) ($order->handling_fee ?? 0);
        $packagingFee = (float) ($order->packaging_fee ?? 0);
        $gstAmount = (float) ($order->gst_amount ?? 0);
        $total = (float) ($order->total ?? 0);

        $paymentStatus = $order->payment_status ?? '-';
        if (is_object($paymentStatus)) {
            $paymentStatus = $paymentStatus->value ?? '-';
        }

        $paymentMethod = $order->payment_method ?? '-';
        if (is_object($paymentMethod)) {
            $paymentMethod = $paymentMethod->value ?? '-';
        }

        $rawOrderType = (string) $order->order_type;
        if (str_ends_with($rawOrderType, '1') || $rawOrderType === '1') {
            $orderTypeName = 'Delivery';
        } elseif (str_ends_with($rawOrderType, '2') || $rawOrderType === '2') {
            $orderTypeName = 'Pickup';
        } elseif (str_ends_with($rawOrderType, '3') || $rawOrderType === '3') {
            $orderTypeName = 'Table';
        } else {
            $orderTypeName = '-';
        }

        $rawAddress = $order->address ?? null;
        $orderAddressData = [];

        if (!empty($rawAddress)) {
            $decoded = is_string($rawAddress) ? json_decode($rawAddress, true) : $rawAddress;
            $decoded = is_object($decoded) ? (array) $decoded : $decoded;

            if (is_array($decoded) && isset($decoded['address']) && is_string($decoded['address'])) {
                if (str_starts_with(trim($decoded['address']), '{')) {
                    $doubleDecoded = json_decode($decoded['address'], true);
                    if (is_array($doubleDecoded)) {
                        $decoded = array_merge($decoded, $doubleDecoded);
                    }
                }
            }

            if (is_string($decoded) && str_starts_with(trim($decoded), '{')) {
                $decoded = json_decode($decoded, true);
            }

            $orderAddressData = is_array($decoded) ? $decoded : [];
        }
    @endphp

    <div class="wrapper">
        <div class="container">

            <!-- HEADER -->
            <div class="header">
                <p class="header-title">New Order Received</p>
                <p class="header-subtitle">A new order has been successfully placed.</p>

                @if($orderCode)
                <div class="order-code">
                    #{{ $orderCode }}
                </div>
                @endif
            </div>

            <div class="content">

                <!-- SUCCESS MESSAGE -->
                <div class="status-box">
                    <div class="status-title">✓ Order Successfully Placed</div>
                    <div class="status-text">Please review the order details and process it accordingly.</div>
                </div>

                <!-- ORDER INFORMATION -->
                <div class="section-title">Order Information</div>
                <table class="info-table">
                    <tr>
                        <td class="label">System Order ID</td>
                        <td class="value">#{{ $order->id }}</td>
                    </tr>
                    <tr>
                        <td class="label">Order Type</td>
                        <td class="value">{{ $orderTypeName }}</td>
                    </tr>
                    <tr>
                        <td class="label">Order Date</td>
                        <td class="value">{{ optional($order->created_at)->format('d M Y, h:i A') }}</td>
                    </tr>
                   
                </table>

                <!-- RESTAURANT -->
                @if($order->restaurant)
                <div class="section-title">Restaurant Details</div>
                <table class="info-table">
                    <tr>
                        <td class="label">Restaurant</td>
                        <td class="value">{{ $order->restaurant->name ?? '-' }}</td>
                    </tr>
                    @if(!empty($order->restaurant->address))
                    <tr>
                        <td class="label">Address</td>
                        <td class="value">{{ $order->restaurant->address }}</td>
                    </tr>
                    @endif
                </table>
                @endif

                <!-- CUSTOMER -->
                @if($order->user || !empty($order->mobile))
                <div class="section-title">Customer Details</div>
                <div class="customer-box">
                    <p><strong>Name:</strong> {{ $order->user->name ?? ($order->customer->name ?? '-') }}</p>
                    <p><strong>Email:</strong> {{ $order->user->email ?? ($order->customer->email ?? '-') }}</p>
                    @if(!empty($order->mobile) || !empty($order->user->phone))
                    <p><strong>Mobile:</strong> {{ $order->mobile ?? $order->user->phone }}</p>
                    @endif
                </div>
                @endif

                <!-- DELIVERY ADDRESS -->
                @if(!empty($rawAddress))
                <div class="section-title">Delivery Address</div>
                <div class="customer-box">
                    @if(!empty($orderAddressData))
                        @foreach($orderAddressData as $key => $value)
                            @if(!is_array($value) && !(is_string($value) && str_starts_with(trim($value), '{')))
                            <p><strong>{{ ucwords(str_replace('_', ' ', $key)) }}:</strong> {{ $value }}</p>
                            @endif
                        @endforeach
                    @else
                        <p>{{ is_string($rawAddress) ? $rawAddress : json_encode($rawAddress) }}</p>
                    @endif
                </div>
                @endif

                <!-- ORDER ITEMS (MATCHED EXACTLY WITH INVOICE TEMPLATE) -->
                <div class="section-title">Order Items</div>

                <table class="items-table">
                    <thead>
                        <tr>
                            <th width="42%">Item</th>
                            <th width="10%" class="text-center">Qty</th>
                            <th width="18%" class="text-right">Unit Price</th>
                            <th width="12%" class="text-right hide-mobile">Discount</th>
                            <th width="18%" class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($order->orderLines as $item)
                            @php
                                $baseUnitPrice = (float) ($item->unit_price ?? 0);
                                $discountedPrice = (float) ($item->discounted_price ?? 0);
                                $variationPrice = (float) ($item->variation_price ?? 0);
                                $optionTotal = (float) ($item->options_total ?? 0);

                                // Product discount logic
                                $productPrice = $discountedPrice > 0 ? $discountedPrice : $baseUnitPrice;
                                $itemDiscount = max(0, $baseUnitPrice - $productPrice);

                                // Variation + options
                                $finalUnitPrice = $productPrice + $variationPrice + $optionTotal;
                                $quantity = (int) ($item->quantity ?? 1);
                                $itemTotal = $finalUnitPrice * $quantity;
                            @endphp

                            <tr>
                                <td>
                                    <div class="item-name">
                                        {{ $item->menu_item_name ?? (optional($item->menuItem)->name ?? 'Unknown Item') }}
                                    </div>

                                    @if (!empty($item->variation_name))
                                        <div class="customization">
                                            <strong>{{ $item->variation_group_name ?? 'Variation' }}:</strong>
                                            {{ $item->variation_name }}
                                            @if ($variationPrice > 0)
                                                (+ ₹{{ number_format($variationPrice, 2) }})
                                            @endif
                                        </div>
                                    @elseif($item->variation)
                                        <div class="customization">
                                            <strong>Variation:</strong>
                                            {{ $item->variation->name }}
                                            @if ($variationPrice > 0)
                                                (+ ₹{{ number_format($variationPrice, 2) }})
                                            @endif
                                        </div>
                                    @endif

                                    {{-- OPTIONS / ADD-ONS --}}
                                    @if (!empty($item->options))
                                        @php
                                            $options = is_string($item->options) ? json_decode($item->options, true) : $item->options;
                                            $options = is_array($options) ? $options : [];
                                        @endphp

                                        @foreach ($options as $option)
                                            <div class="option">
                                                <strong>{{ $option['group_name'] ?? ($option['option_group_name'] ?? 'Add-on') }}:</strong>
                                                {{ $option['name'] ?? 'Option' }}

                                                @if (isset($option['quantity']) && (int) $option['quantity'] > 1)
                                                    × {{ (int) $option['quantity'] }}
                                                @endif

                                                @if (isset($option['price']) && (float) $option['price'] > 0)
                                                    (+ ₹{{ number_format((float) $option['price'], 2) }})
                                                @endif
                                            </div>
                                        @endforeach
                                    @endif
                                    
                                    @if(!empty($item->instructions))
                                        <div class="option" style="color: #ea580c; margin-top: 6px;">
                                            <strong>Note:</strong> {{ $item->instructions }}
                                        </div>
                                    @endif
                                </td>

                                <td class="text-center">{{ $quantity }}</td>

                                <td class="text-right">₹{{ number_format($baseUnitPrice, 2) }}</td>

                                <td class="text-right hide-mobile">
                                    @if ($itemDiscount > 0)
                                        <span class="discount-text">- ₹{{ number_format($itemDiscount, 2) }}</span>
                                    @else
                                        ₹0.00
                                    @endif
                                </td>

                                <td class="text-right" style="font-weight: 600;">₹{{ number_format($itemTotal, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center" style="padding: 20px; color: #6b7280;">No items found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <!-- PRICE SUMMARY -->
                <div class="summary-wrapper">
                    <table class="summary-table">
                        <tr>
                            <td class="summary-label">Sub Total</td>
                            <td class="summary-value">₹{{ number_format($subTotal, 2) }}</td>
                        </tr>

                        @if((float) $order->product_discount > 0)
                        <tr>
                            <td class="summary-label discount-text">Product Discount</td>
                            <td class="summary-value discount-text">- ₹{{ number_format((float) $order->product_discount, 2) }}</td>
                        </tr>
                        @endif

                        @if($discount > 0)
                        <tr>
                            <td class="summary-label discount-text">Coupon Discount</td>
                            <td class="summary-value discount-text">- ₹{{ number_format($discount, 2) }}</td>
                        </tr>
                        @endif

                        @if($packagingFee > 0)
                        <tr>
                            <td class="summary-label">Packaging Fee</td>
                            <td class="summary-value">₹{{ number_format($packagingFee, 2) }}</td>
                        </tr>
                        @endif

                        @if($handlingFee > 0)
                        <tr>
                            <td class="summary-label">Handling Fee</td>
                            <td class="summary-value">₹{{ number_format($handlingFee, 2) }}</td>
                        </tr>
                        @endif

                        @if($searchFee > 0)
                        <tr>
                            <td class="summary-label">Search Fee</td>
                            <td class="summary-value">₹{{ number_format($searchFee, 2) }}</td>
                        </tr>
                        @endif

                        @if($gstAmount > 0)
                        <tr>
                            <td class="summary-label">GST</td>
                            <td class="summary-value">₹{{ number_format($gstAmount, 2) }}</td>
                        </tr>
                        @endif

                        @if($deliveryCharge > 0)
                        <tr>
                            <td class="summary-label">Delivery Fee</td>
                            <td class="summary-value">₹{{ number_format($deliveryCharge, 2) }}</td>
                        </tr>
                        @endif

                        <tr class="grand-total">
                            <td class="summary-label">Grand Total</td>
                            <td class="summary-value">₹{{ number_format($total, 2) }}</td>
                        </tr>
                    </table>
                </div>

                <!-- NOTES -->
                @php
                    $remarks = null;
                    if (!empty($order->misc)) {
                        $misc = is_array($order->misc) ? $order->misc : json_decode($order->misc, true);
                        if (is_array($misc)) {
                            $remarks = $misc['remarks'] ?? null;
                        }
                    }
                @endphp

                @if($remarks || !empty($order->order_instructions))
                <div class="section-title">Order Notes</div>
                <div class="customer-box">
                    <p>{{ $remarks ?? $order->order_instructions }}</p>
                </div>
                @endif

            </div>

            <!-- FOOTER -->
            <div class="footer">
                <strong>New Order Notification</strong><br>
                This is an automated email generated by the system.<br><br>
                System Order #{{ $order->id }}
            </div>

        </div>
    </div>
</body>

</html>