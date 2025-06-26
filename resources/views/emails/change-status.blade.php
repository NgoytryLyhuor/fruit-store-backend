<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Status Update</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            background: #ffffff;
            max-width: 600px;
            margin: 0 auto;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .header {
            background-color: #333333;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 30px;
        }
        .status-update {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
            text-align: center;
        }
        .status {
            font-size: 18px;
            font-weight: bold;
            color: #28a745;
        }
        .order-details {
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 20px;
            margin: 20px 0;
        }
        .order-info {
            margin-bottom: 10px;
        }
        .label {
            font-weight: bold;
            display: inline-block;
            width: 120px;
        }
        .items-title {
            font-size: 18px;
            font-weight: bold;
            margin: 25px 0 15px 0;
            border-bottom: 2px solid #333333;
            padding-bottom: 5px;
        }
        .item {
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }
        .item-image {
            width: 60px;
            height: 60px;
            border-radius: 4px;
            object-fit: cover;
            margin-right: 15px;
            border: 1px solid #ccc;
        }
        .item-details {
            flex: 1;
        }
        .item-name {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .item-info {
            color: #666;
            font-size: 14px;
        }
        .item-price {
            text-align: right;
            font-weight: bold;
        }
        .total-row {
            border-top: 2px solid #333333;
            padding-top: 10px;
            margin-top: 15px;
            text-align: right;
        }
        .total-amount {
            font-size: 20px;
            font-weight: bold;
            color: #333333;
        }
        .delivery-address {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
        }
        .address-title {
            font-weight: bold;
            margin-bottom: 8px;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #666;
            font-size: 14px;
            border-top: 1px solid #dee2e6;
        }

        @media (max-width: 600px) {
            .container {
                margin: 10px;
            }
            .content {
                padding: 20px;
            }
            .item {
                flex-direction: column;
                text-align: center;
            }
            .item-image {
                margin-right: 0;
                margin-bottom: 10px;
            }
            .item-price {
                text-align: center;
                margin-top: 10px;
            }
            .label {
                width: auto;
                display: block;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Order Status Update</h1>
        </div>

        <div class="content">
            <p>Dear <strong>{{ $user->first_name ?? $user->name ?? 'Customer' }}</strong>,</p>

            <p>We would like to inform you that the status of your order has been updated.</p>

            <div class="status-update">
                <div class="status">Status: {{ ucfirst($order->status) }}</div>
            </div>

            <div class="order-details">
                <div class="order-info">
                    <span class="label">Order Number:</span> #{{ $order->id }}
                </div>
                <div class="order-info">
                    <span class="label">Order Date:</span> {{ $order->created_at->format('M d, Y') }}
                </div>
                <div class="order-info">
                    <span class="label">Total Amount:</span> ${{ number_format($order->total_amount, 2) }}
                </div>
            </div>

            @if($order->delivery_address && is_array($order->delivery_address))
            <div class="delivery-address">
                <div class="address-title">Delivery Address:</div>
                <div>
                    {{ $order->delivery_address['street'] ?? '' }}<br>
                    {{ $order->delivery_address['city'] ?? '' }} {{ $order->delivery_address['postal_code'] ?? '' }}
                </div>
            </div>
            @endif

            <div class="items-title">Order Items</div>

            @foreach($order->orderItems as $item)
            <div class="item">
                <img src="{{ $item->fruit->image_url }}" alt="{{ $item->fruit->name }}" class="item-image">
                <div class="item-details">
                    <div class="item-name">{{ $item->fruit->name }}</div>
                    <div class="item-info">
                        Quantity: {{ $item->quantity }} |
                        Unit Price: ${{ number_format($item->price, 2) }}
                    </div>
                </div>
                <div class="item-price">
                    ${{ number_format($item->price * $item->quantity, 2) }}
                </div>
            </div>
            @endforeach

            <div class="total-row">
                <div class="total-amount">Total: ${{ number_format($order->total_amount, 2) }}</div>
            </div>

            <p>If you have any questions about your order, please contact our support team.</p>

            <p>Thank you for your business!</p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} Fruit Store. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
