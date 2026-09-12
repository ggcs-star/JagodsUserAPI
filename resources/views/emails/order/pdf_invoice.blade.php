<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Tax Invoice - {{ $order->order_code }}</title>

    <style>
        @page {
            margin: 25px 28px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: DejaVu Sans, Arial, sans-serif;
            color: #1f2937;
            font-size: 12px;
            line-height: 1.5;
            background: #ffffff;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .muted {
            color: #6b7280;
        }

        .small {
            font-size: 10px;
        }

        .header {
            width: 100%;
            margin-bottom: 20px;
            padding-bottom: 14px;
            border-bottom: 2px solid #e5e7eb;
        }

        .header-logo {
            width: 170px;
            max-height: 60px;
        }

        .invoice-title {
            font-size: 24px;
            font-weight: bold;
            color: #111827;
            margin: 0;
        }

        .invoice-number {
            font-size: 11px;
            color: #6b7280;
            margin-top: 4px;
        }

        .section-title {
            font-size: 11px;
            font-weight: bold;
            color: #111827;
            text-transform: uppercase;
            margin-bottom: 7px;
            letter-spacing: 0.3px;
        }

        .info-box {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            padding: 12px;
            vertical-align: top;
        }

        .info-box-left {
            width: 49%;
        }

        .info-box-right {
            width: 49%;
        }

        .info-label {
            color: #6b7280;
            font-size: 10px;
        }

        .info-value {
            color: #111827;
            font-weight: bold;
            font-size: 11px;
        }

        .spacer {
            height: 10px;
        }

        .items-table {
            margin-top: 20px;
            border: 1px solid #e5e7eb;
        }

        .items-table th {
            background: #f3f4f6;
            color: #374151;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 9px 8px;
            border-bottom: 1px solid #d1d5db;
        }

        .items-table td {
            padding: 9px 8px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }

        .items-table tr:last-child td {
            border-bottom: none;
        }

        .item-name {
            font-weight: bold;
            font-size: 11px;
            color: #111827;
        }

        .customization {
            margin-top: 3px;
            color: #6b7280;
            font-size: 9px;
        }

        .option {
            margin-top: 2px;
            color: #6b7280;
            font-size: 9px;
        }

        .summary-wrapper {
            margin-top: 18px;
        }

        .summary-spacer {
            width: 55%;
        }

        .summary {
            width: 45%;
        }

        .summary td {
            padding: 5px 8px;
            font-size: 11px;
        }

        .summary-label {
            color: #6b7280;
        }

        .summary-value {
            color: #111827;
            text-align: right;
        }

        .discount {
            color: #16a34a;
        }

        .grand-total td {
            padding-top: 10px;
            padding-bottom: 10px;
            border-top: 2px solid #111827;
            font-size: 15px;
            font-weight: bold;
        }

        .payment-status {
            margin-top: 15px;
            padding: 10px 12px;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
        }

        .footer {
            margin-top: 30px;
            padding-top: 14px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 10px;
        }

        .thank-you {
            font-size: 13px;
            font-weight: bold;
            color: #111827;
            margin-bottom: 4px;
        }

        .avoid-break {
            page-break-inside: avoid;
        }
    </style>
</head>

<body>

    @php
        $misc = json_decode($order->misc ?? '{}', true) ?: [];

        $orderCode = data_get($misc, 'order_code', $order->order_code ?? 'ORD-' . $order->id);

        $customerName = optional($order->user)->name ?? (optional($order->customer)->name ?? 'Customer');

        $customerEmail = optional($order->user)->email ?? (optional($order->customer)->email ?? null);

        $customerPhone =
            $order->mobile ?? (optional($order->user)->phone ?? (optional($order->customer)->phone ?? null));
        use App\Enums\Module;
        $restaurantName = match ((int) $order->module_id) {
            Module::YOUR_CITY => 'Your City',
            Module::ALL_OVER_INDIA => 'All Over India',
            default => optional($order->restaurant)->name ?? 'Jagods',
        };

        $restaurantPhone = optional($order->restaurant)->phone ?? null;

        $rawAddress = $order->address ?? (optional($order->addressRelation)->address ?? null);

        $orderAddressData = [];

        if (!empty($rawAddress)) {
            // Decode first level
            $addressData = $rawAddress;

            if (is_string($addressData)) {
                $addressData = json_decode($addressData, true);
            }

            // If still string, decode again
            if (is_string($addressData)) {
                $addressData = json_decode($addressData, true);
            }

            $orderAddressData = is_array($addressData) ? $addressData : [];
        }

        // -----------------------------------------
        // 1. ORDER TYPE LOGIC FIX (String/Int Handle)
        // -----------------------------------------
        $orderTypeId = (int) $order->order_type;

        $orderTypeName = match ($orderTypeId) {
            (int) \App\Enums\OrderTypeStatus::DELIVERY => 'Delivery',
            (int) \App\Enums\OrderTypeStatus::PICKUP => 'Pickup',
            (int) \App\Enums\OrderTypeStatus::TABLE => 'Table',
            default => 'Unknown',
        };

        // -----------------------------------------
        // 2. ADDRESS JSON PARSING LOGIC (Double Encode Fix)
        // -----------------------------------------
        $orderAddressData = [];

        if (!empty($rawAddress)) {
            // First decode
            $decoded = is_string($rawAddress) ? json_decode($rawAddress, true) : $rawAddress;
            $decoded = is_object($decoded) ? (array) $decoded : $decoded;

            // Fix for double-encoded JSON inside the 'address' key
            if (is_array($decoded) && isset($decoded['address']) && is_string($decoded['address'])) {
                if (str_starts_with(trim($decoded['address']), '{')) {
                    $doubleDecoded = json_decode($decoded['address'], true);
                    if (is_array($doubleDecoded)) {
                        $decoded = array_merge($decoded, $doubleDecoded);
                    }
                }
            }

            // Fallback for overall double stringified object
            if (is_string($decoded) && str_starts_with(trim($decoded), '{')) {
                $decoded = json_decode($decoded, true);
            }

            $orderAddressData = is_array($decoded) ? $decoded : [];
        }

        $paymentMethodName = match ((int) $order->payment_method) {
            \App\Enums\PaymentMethod::CASH_ON_DELIVERY => 'Cash on Delivery',
            default => 'Online Payment',
        };

        $paymentStatusName = (int) $order->payment_status === (int) \App\Enums\PaymentStatus::PAID ? 'Paid' : 'Pending';

        $invoiceNumber = $order->invoice_id ?? $order->id;

        $totalItems = $order->orderLines->sum('quantity');
    @endphp

    <!-- HEADER -->
    <table class="header">
        <tr>
            <td width="55%" valign="middle">

                <img class="header-logo"
                    src="{{ themeSetting('site_logo')
                        ? themeSetting('site_logo')->logo
                        : rtrim(env('MEDIA_URL'), '/') . '/images/seeder/settings/logo.png' }}"
                    alt="Jagods">

                <div style="margin-top: 6px;">
                    <strong>{{ $restaurantName }}</strong>
                </div>

                @if ($restaurantPhone)
                    <div class="small muted">
                        {{ $restaurantPhone }}
                    </div>
                @endif

            </td>

            <td width="45%" class="text-right">

                <div class="invoice-title">
                    TAX INVOICE
                </div>

                <div class="invoice-number">
                    Invoice #{{ $invoiceNumber }}
                </div>

                <div class="invoice-number">
                    Order #{{ $orderCode }}
                </div>

                <div class="invoice-number">
                    {{ optional($order->created_at)->format('d M Y, h:i A') }}
                </div>

            </td>
        </tr>
    </table>


    <!-- CUSTOMER / ORDER INFORMATION -->
    <table>
        <tr>

            <td class="info-box info-box-left">

                <div class="section-title">
                    Customer Details
                </div>

                <div>
                    <span class="info-label">Name</span><br>
                    <span class="info-value">
                        {{ $customerName }}
                    </span>
                </div>

                @if ($customerPhone)
                    <div style="margin-top: 5px;">
                        <span class="info-label">Phone</span><br>
                        <span class="info-value">
                            {{ $customerPhone }}
                        </span>
                    </div>
                @endif

                @if ($customerEmail)
                    <div style="margin-top: 5px;">
                        <span class="info-label">Email</span><br>
                        <span class="info-value">
                            {{ $customerEmail }}
                        </span>
                    </div>
                @endif

            </td>


            <td width="2%"></td>


            <td class="info-box info-box-right">

                <div class="section-title">
                    Order Details
                </div>

                <div>
                    <span class="info-label">Order Type</span><br>
                    <span class="info-value">
                        {{ $orderTypeName }}
                    </span>
                </div>


                <div style="margin-top: 5px;">
                    <span class="info-label">Payment Method</span><br>
                    <span class="info-value">
                        {{ $paymentMethodName }}
                    </span>
                </div>

                <div style="margin-top: 5px;">
                    <span class="info-label">Payment Status</span><br>
                    <span class="info-value">
                        {{ $paymentStatusName }}
                    </span>
                </div>

                <div style="margin-top: 5px;">
                    <span class="info-label">Total Items</span><br>
                    <span class="info-value">
                        {{ $totalItems }}
                    </span>
                </div>

            </td>

        </tr>
    </table>


    <!-- DELIVERY ADDRESS -->
    @if (!empty($orderAddressData))

        <div class="spacer"></div>

        <table>
            <tr>
                <td class="info-box">

                    <div class="section-title">
                        Delivery Address
                    </div>

                    <div
                        style="
                    font-size: 11px;
                    color: #374151;
                    line-height: 1.7;
                ">

                        @if (!empty($orderAddressData['address']))
                            <strong>
                                {{ $orderAddressData['address'] }}
                            </strong>
                        @endif

                        @if (!empty($orderAddressData['apartment']))
                            <br>
                            {{ $orderAddressData['apartment'] }}
                        @endif

                        @if (!empty($orderAddressData['landmark']))
                            <br>
                            <strong>Landmark:</strong>
                            {{ $orderAddressData['landmark'] }}
                        @endif

                        @if (!empty($orderAddressData['city']))
                            <br>
                            {{ $orderAddressData['city'] }}
                        @endif

                        @if (!empty($orderAddressData['state']))
                            @if (!empty($orderAddressData['city']))
                                ,
                            @endif
                            {{ $orderAddressData['state'] }}
                        @endif

                        @if (!empty($orderAddressData['pincode']))
                            <br>
                            <strong>Pincode:</strong>
                            {{ $orderAddressData['pincode'] }}
                        @endif

                    </div>

                </td>
            </tr>
        </table>

    @endif


    <!-- ORDER ITEMS -->
    <table class="items-table">
        <thead>
            <tr>
                <th width="42%">
                    Item
                </th>

                <th width="10%" class="text-center">
                    Qty
                </th>

                <th width="18%" class="text-right">
                    Unit Price
                </th>

                <th width="12%" class="text-right">
                    Discount
                </th>

                <th width="18%" class="text-right">
                    Total
                </th>
            </tr>
        </thead>

        <tbody>

            @foreach ($order->orderLines as $item)
                @php
                    $baseUnitPrice = (float) ($item->unit_price ?? 0);
                    $discountedPrice = (float) ($item->discounted_price ?? 0);

                    $variationPrice = (float) ($item->variation_price ?? 0);
                    $optionTotal = (float) ($item->options_total ?? 0);

                    $finalUnitPrice =
                        (float) ($item->final_unit_price ?? $discountedPrice + $variationPrice + $optionTotal);

                    $quantity = (int) ($item->quantity ?? 1);

                    $itemTotal = (float) ($item->item_total ?? $finalUnitPrice * $quantity);

                    $itemDiscount = max(0, $baseUnitPrice - $discountedPrice);
                @endphp

                <tr>

                    <td>

                        <div class="item-name">
                            {{ $item->menu_item_name ?? (optional($item->menuItem)->name ?? 'Unknown Item') }}
                        </div>

                        @if (!empty($item->variation_name))
                            <div class="customization">
                                <strong>
                                    {{ $item->variation_group_name ?? 'Variation' }}:
                                </strong>

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
                                $options = is_string($item->options)
                                    ? json_decode($item->options, true)
                                    : $item->options;

                                $options = is_array($options) ? $options : [];
                            @endphp

                            @foreach ($options as $option)
                                <div class="option">

                                    <strong>
                                        {{ $option['group_name'] ?? ($option['option_group_name'] ?? 'Add-on') }}:
                                    </strong>

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

                    </td>


                    <td class="text-center">
                        {{ $quantity }}
                    </td>


                    <td class="text-right">
                        ₹{{ number_format($baseUnitPrice, 2) }}
                    </td>


                    <td class="text-right">
                        @if ($itemDiscount > 0)
                            <span class="discount">
                                - ₹{{ number_format($itemDiscount, 2) }}
                            </span>
                        @else
                            ₹0.00
                        @endif
                    </td>


                    <td class="text-right">
                        ₹{{ number_format($itemTotal, 2) }}
                    </td>

                </tr>
            @endforeach

        </tbody>
    </table>


    <!-- TOTALS -->
    <div class="summary-wrapper">

        <table>
            <tr>

                <td class="summary-spacer"></td>

                <td class="summary">

                    <table>

                        <tr>
                            <td class="summary-label">
                                Subtotal
                            </td>

                            <td class="summary-value">
                                ₹{{ number_format((float) $order->sub_total, 2) }}
                            </td>
                        </tr>


                        @if ((float) $order->product_discount > 0)
                            <tr>
                                <td class="summary-label discount">
                                    Product Discount
                                </td>

                                <td class="summary-value discount">
                                    - ₹{{ number_format((float) $order->product_discount, 2) }}
                                </td>
                            </tr>
                        @endif


                        @if ((float) $order->discount > 0)
                            <tr>
                                <td class="summary-label discount">
                                    Coupon Discount
                                </td>

                                <td class="summary-value discount">
                                    - ₹{{ number_format((float) $order->discount, 2) }}
                                </td>
                            </tr>
                        @endif


                        @if ((float) $order->packing_charge > 0)
                            <tr>
                                <td class="summary-label">
                                    Packaging Fee
                                </td>

                                <td class="summary-value">
                                    ₹{{ number_format((float) $order->packing_charge, 2) }}
                                </td>
                            </tr>
                        @endif


                        @if ((float) $order->platform_fee > 0)
                            <tr>
                                <td class="summary-label">
                                    Platform Fee
                                </td>

                                <td class="summary-value">
                                    ₹{{ number_format((float) $order->platform_fee, 2) }}
                                </td>
                            </tr>
                        @endif


                        @if ((float) $order->handling_fee > 0)
                            <tr>
                                <td class="summary-label">
                                    Handling Fee
                                </td>

                                <td class="summary-value">
                                    ₹{{ number_format((float) $order->handling_fee, 2) }}
                                </td>
                            </tr>
                        @endif


                        @if ((float) $order->large_order_fee > 0)
                            <tr>
                                <td class="summary-label">
                                    Large Order Fee
                                </td>

                                <td class="summary-value">
                                    ₹{{ number_format((float) $order->large_order_fee, 2) }}
                                </td>
                            </tr>
                        @endif


                        @if ((float) $order->surge_fee > 0)
                            <tr>
                                <td class="summary-label">
                                    Surge Fee
                                </td>

                                <td class="summary-value">
                                    ₹{{ number_format((float) $order->surge_fee, 2) }}
                                </td>
                            </tr>
                        @endif


                        @if ((float) $order->gst_amount > 0)
                            <tr>
                                <td class="summary-label">
                                    GST
                                </td>

                                <td class="summary-value">
                                    ₹{{ number_format((float) $order->gst_amount, 2) }}
                                </td>
                            </tr>
                        @endif


                        @if ((float) $order->delivery_charge > 0)
                            <tr>
                                <td class="summary-label">
                                    Delivery Fee
                                </td>

                                <td class="summary-value">
                                    ₹{{ number_format((float) $order->delivery_charge, 2) }}
                                </td>
                            </tr>
                        @endif


                        @if ((float) $order->tip_amount > 0)
                            <tr>
                                <td class="summary-label">
                                    Tip
                                </td>

                                <td class="summary-value">
                                    ₹{{ number_format((float) $order->tip_amount, 2) }}
                                </td>
                            </tr>
                        @endif


                        <tr class="grand-total">
                            <td>
                                Total Paid
                            </td>

                            <td class="text-right">
                                ₹{{ number_format((float) $order->total, 2) }}
                            </td>
                        </tr>

                    </table>

                </td>

            </tr>
        </table>

    </div>


    <!-- PAYMENT STATUS -->
    <div class="payment-status avoid-break">

        <strong>
            Payment {{ $paymentStatusName }}
        </strong>

        @if ($order->paid_amount !== null)
            &nbsp; | &nbsp;

            Paid Amount:
            <strong>
                ₹{{ number_format((float) $order->paid_amount, 2) }}
            </strong>
        @endif

        @if (!empty($order->payment_id))
            &nbsp; | &nbsp;

            Transaction ID:
            <strong>
                {{ $order->payment_id }}
            </strong>
        @endif

    </div>


    <!-- ORDER INSTRUCTIONS -->
    @if (!empty($order->order_instructions))
        <div style="margin-top: 18px;" class="avoid-break">

            <div class="section-title">
                Order Instructions
            </div>

            <div class="info-box">
                {{ $order->order_instructions }}
            </div>

        </div>
    @endif


    <!-- FOOTER -->
    <div class="footer">

        <div class="thank-you">
            Thank you for ordering with Jagods!
        </div>

        <div>
            We appreciate your business and hope to serve you again.
        </div>

        <div style="margin-top: 6px;">
            This is a computer-generated invoice and does not require a signature.
        </div>

        <div style="margin-top: 5px;">
            &copy; {{ date('Y') }} Jagods. All rights reserved.
        </div>

    </div>

</body>

</html>
