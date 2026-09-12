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
            font-family: Arial, Helvetica, sans-serif;
            color: #333333;
        }

        table {
            border-collapse: collapse;
        }

        .wrapper {
            width: 100%;
            padding: 30px 15px;
            background: #f4f6f8;
        }

        .container {
            max-width: 750px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.08);
        }

        .header {
            padding: 28px 30px;
            background: #111827;
            color: #ffffff;
        }

        .header-title {
            font-size: 24px;
            font-weight: 700;
            margin: 0;
        }

        .header-subtitle {
            margin-top: 7px;
            font-size: 14px;
            color: #d1d5db;
        }

        .content {
            padding: 30px;
        }

        .status-box {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            border-radius: 8px;
            padding: 14px 16px;
            margin-bottom: 25px;
        }

        .status-title {
            color: #047857;
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .status-text {
            color: #065f46;
            font-size: 13px;
        }

        .section-title {
            font-size: 17px;
            font-weight: 700;
            color: #111827;
            margin: 25px 0 12px;
        }

        .info-table {
            width: 100%;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
        }

        .info-table td {
            padding: 11px 14px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 13px;
        }

        .info-table tr:last-child td {
            border-bottom: none;
        }

        .label {
            width: 35%;
            color: #6b7280;
        }

        .value {
            color: #111827;
            font-weight: 600;
        }

        .items-table {
            width: 100%;
            margin-top: 10px;
            border: 1px solid #e5e7eb;
        }

        .items-table th {
            background: #f9fafb;
            color: #374151;
            font-size: 12px;
            padding: 12px 10px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        .items-table td {
            padding: 13px 10px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 13px;
            vertical-align: top;
        }

        .items-table tr:last-child td {
            border-bottom: none;
        }

        .item-name {
            font-weight: 700;
            color: #111827;
        }

        .item-sub {
            margin-top: 4px;
            color: #6b7280;
            font-size: 11px;
        }

        .text-right {
            text-align: right;
        }

        .summary-table {
            width: 100%;
            margin-top: 20px;
        }

        .summary-table td {
            padding: 7px 0;
            font-size: 13px;
        }

        .summary-label {
            text-align: right;
            color: #6b7280;
            padding-right: 25px !important;
        }

        .summary-value {
            text-align: right;
            color: #111827;
            font-weight: 600;
        }

        .grand-total td {
            border-top: 2px solid #111827;
            padding-top: 14px;
            font-size: 18px;
            font-weight: 700;
            color: #111827;
        }

        .customer-box {
            background: #f9fafb;
            border-radius: 8px;
            padding: 18px;
        }

        .customer-box p {
            margin: 5px 0;
            font-size: 13px;
        }

        .footer {
            background: #f9fafb;
            border-top: 1px solid #e5e7eb;
            padding: 22px 30px;
            text-align: center;
            color: #6b7280;
            font-size: 12px;
        }

        .order-code {
            display: inline-block;
            background: #ffffff;
            color: #111827;
            padding: 6px 10px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 12px;
            margin-top: 10px;
        }

        @media only screen and (max-width: 600px) {
            .wrapper {
                padding: 10px;
            }

            .content {
                padding: 20px 15px;
            }

            .header {
                padding: 22px 20px;
            }

            .header-title {
                font-size: 20px;
            }

            .items-table th,
            .items-table td {
                padding: 9px 6px;
                font-size: 11px;
            }

            .info-table td {
                padding: 9px;
            }
        }
    </style>
</head>

<body>

    @php
    use App\Enums\OrderTypeStatus;
    $orderCode = null;
    if (!empty($order->misc)) {
    $misc = is_array($order->misc)
    ? $order->misc
    : json_decode($order->misc, true);

    if (is_array($misc)) {
    $orderCode = $misc['order_code'] ?? null;
    }
    }
    $subTotal = (float) ($order->sub_total ?? 0);
    $discount = (float) ($order->discount ?? 0);
    $deliveryCharge = 200;
    $searchFee = (float) ($order->search_fee ?? 0);
    $handlingFee = (float) ($order->handling_fee ?? 0);
    $packagingFee = (float) ($order->packaging_fee ?? 0);
    $gstAmount = (float) ($order->gst_amount ?? 0);
    $paidAmount = (float) ($order->paid_amount ?? 0);
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
                <p class="header-title">
                    New Order Received
                </p>
                <p class="header-subtitle">
                    A new order has been successfully placed.
                </p>

                @if($orderCode)
                <div class="order-code">
                    {{ $orderCode }}
                </div>
                @endif
            </div>

            <div class="content">

                <!-- SUCCESS MESSAGE -->
                <div class="status-box">
                    <div class="status-title">
                        ✓ Order Successfully Placed
                    </div>
                    <div class="status-text">
                        Please review the order details and process the order accordingly.
                    </div>
                </div>

                <!-- ORDER INFORMATION -->
                <div class="section-title">
                    Order Information
                </div>

                <table class="info-table">
                    <tr>
                        <td class="label">
                            Order ID
                        </td>
                        <td class="value">
                            #{{ $order->id }}
                        </td>
                    </tr>

                    @if($orderCode)
                    <tr>
                        <td class="label">
                            Order Code
                        </td>
                        <td class="value">
                            {{ $orderCode }}
                        </td>
                    </tr>
                    @endif

                    <tr>
                        <td class="label">
                            Order Type
                        </td>
                        <td class="value">
                            {{ $orderTypeName }}
                        </td>
                    </tr>

                    <tr>
                        <td class="label">
                            Order Date
                        </td>
                        <td class="value">
                            {{ optional($order->created_at)->format('d M Y, h:i A') }}
                        </td>
                    </tr>

                    <tr>
                        <td class="label">
                            Payment Method
                        </td>
                        <td class="value">
                            {{ $paymentMethod }}
                        </td>
                    </tr>

                    <tr>
                        <td class="label">
                            Payment Status
                        </td>
                        <td class="value">
                            {{ $paymentStatus }}
                        </td>
                    </tr>
                </table>

                <!-- RESTAURANT -->
                @if($order->restaurant)
                <div class="section-title">
                    Restaurant Details
                </div>
                <table class="info-table">
                    <tr>
                        <td class="label">
                            Restaurant
                        </td>
                        <td class="value">
                            {{ $order->restaurant->name ?? '-' }}
                        </td>
                    </tr>
                    @if(!empty($order->restaurant->address))
                    <tr>
                        <td class="label">
                            Address
                        </td>
                        <td class="value">
                            {{ $order->restaurant->address }}
                        </td>
                    </tr>
                    @endif
                </table>
                @endif

                <!-- CUSTOMER -->
                @if($order->user)
                <div class="section-title">
                    Customer Details
                </div>
                <div class="customer-box">
                    <p>
                        <strong>Name:</strong>
                        {{ $order->user->name ?? '-' }}
                    </p>
                    <p>
                        <strong>Email:</strong>
                        {{ $order->user->email ?? '-' }}
                    </p>
                    @if(!empty($order->mobile))
                    <p>
                        <strong>Mobile:</strong>
                        {{ $order->mobile }}
                    </p>
                    @endif
                </div>
                @endif

                <!-- DELIVERY ADDRESS -->
                @if(!empty($rawAddress))
                <div class="section-title">
                    Delivery Address
                </div>

                <div class="customer-box">
                    @if(!empty($orderAddressData))
                    @foreach($orderAddressData as $key => $value)
                    {{-- Hide raw nested json strings if they still exist --}}
                    @if(!is_array($value) && !(is_string($value) && str_starts_with(trim($value), '{')))
                    <p>
                        <strong>{{ ucwords(str_replace('_', ' ', $key)) }}:</strong>
                        {{ $value }}
                    </p>
                    @endif
                    @endforeach
                    @else
                    <p>
                        {{ is_string($rawAddress) ? $rawAddress : json_encode($rawAddress) }}
                    </p>
                    @endif
                </div>
                @endif

                <!-- ORDER ITEMS -->
                <div class="section-title">
                    Order Items
                </div>

                <table class="items-table">
                    <thead>
                        <tr>
                            <th style="width:45%;">Item</th>
                            <th style="width:15%;">Qty</th>
                            <th style="width:20%;">Unit Price</th>
                            <th style="width:20%;" class="text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                        @php
                        $itemName = 'Menu Item #' . ($item->menu_item_id ?? '-');
                        if (isset($item->menuItem) && $item->menuItem) {
                        $itemName = $item->menuItem->name ?? $itemName;
                        }
                        $quantity = (int) ($item->quantity ?? 0);
                        $unitPrice = (float) ($item->unit_price ?? 0);
                        $discountedPrice = (float) ($item->discounted_price ?? 0);
                        $itemTotal = (float) ($item->item_total ?? 0);
                        @endphp
                        <tr>
                            <td>
                                <div class="item-name">
                                    {{ $itemName }}
                                </div>
                                @if($item->menu_item_variation_id)
                                <div class="item-sub">
                                    Variation: #{{ $item->menu_item_variation_id }}
                                </div>
                                @endif
                                @if(!empty($item->instructions))
                                <div class="item-sub">
                                    Note: {{ $item->instructions }}
                                </div>
                                @endif
                            </td>
                            <td>
                                {{ $quantity }}
                            </td>
                            <td>
                                @if($discountedPrice > 0 && $discountedPrice < $unitPrice)
                                    <span style="text-decoration:line-through;color:#9ca3af;">
                                    ₹{{ number_format($unitPrice, 2) }}
                                    </span>
                                    <br>
                                    <strong>
                                        ₹{{ number_format($discountedPrice, 2) }}
                                    </strong>
                                    @else
                                    ₹{{ number_format($unitPrice, 2) }}
                                    @endif
                            </td>
                            <td class="text-right">
                                ₹{{ number_format($itemTotal, 2) }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" style="text-align:center;color:#6b7280;">
                                No order items found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>

                <!-- PRICE SUMMARY -->
                <div class="section-title">
                    Order Summary
                </div>

                <table class="summary-table">
                    <tr>
                        <td class="summary-label">
                            Sub Total
                        </td>
                        <td class="summary-value">
                            ₹{{ number_format($subTotal, 2) }}
                        </td>
                    </tr>

                    @if($discount > 0)
                    <tr>
                        <td class="summary-label">
                            Discount
                        </td>
                        <td class="summary-value">
                            - ₹{{ number_format($discount, 2) }}
                        </td>
                    </tr>
                    @endif

                    @if($deliveryCharge > 0)
                    <tr>
                        <td class="summary-label">
                            Delivery Charge
                        </td>
                        <td class="summary-value">
                            ₹{{ number_format($deliveryCharge, 2) }}
                        </td>
                    </tr>
                    @endif

                    @if($searchFee > 0)
                    <tr>
                        <td class="summary-label">
                            Search Fee
                        </td>
                        <td class="summary-value">
                            ₹{{ number_format($searchFee, 2) }}
                        </td>
                    </tr>
                    @endif

                    @if($handlingFee > 0)
                    <tr>
                        <td class="summary-label">
                            Handling Fee
                        </td>
                        <td class="summary-value">
                            ₹{{ number_format($handlingFee, 2) }}
                        </td>
                    </tr>
                    @endif

                    @if($packagingFee > 0)
                    <tr>
                        <td class="summary-label">
                            Packaging Fee
                        </td>
                        <td class="summary-value">
                            ₹{{ number_format($packagingFee, 2) }}
                        </td>
                    </tr>
                    @endif

                    @if($gstAmount > 0)
                    <tr>
                        <td class="summary-label">
                            GST
                        </td>
                        <td class="summary-value">
                            ₹{{ number_format($gstAmount, 2) }}
                        </td>
                    </tr>
                    @endif

                    <tr class="grand-total">
                        <td class="summary-label">
                            Grand Total
                        </td>
                        <td class="summary-value">
                            ₹{{ number_format($total, 2) }}
                        </td>
                    </tr>
                </table>

                <!-- NOTES -->
                @php
                $remarks = null;
                if (!empty($order->misc)) {
                $misc = is_array($order->misc)
                ? $order->misc
                : json_decode($order->misc, true);

                if (is_array($misc)) {
                $remarks = $misc['remarks'] ?? null;
                }
                }
                @endphp

                @if($remarks)
                <div class="section-title">
                    Order Notes
                </div>
                <div class="customer-box">
                    <p>
                        {{ $remarks }}
                    </p>
                </div>
                @endif

            </div>

            <!-- FOOTER -->
            <div class="footer">
                <strong>
                    New Order Notification
                </strong>
                <br>
                This is an automated email generated by the ordering system.
                <br><br>
                Order #{{ $order->id }}
            </div>

        </div>
    </div>
</body>

</html>