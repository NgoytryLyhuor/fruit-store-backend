@php
    $frontendUrl = env('APP_FRONTEND_URL', 'http://localhost:5173');
@endphp
<!DOCTYPE html>
<html>
<head>
    <title>New Product Alert</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333333;
            margin: 0;
            padding: 20px 0;
            line-height: 1.6;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .header {
            background-color: #2c5aa0;
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }

        .header p {
            margin: 8px 0 0 0;
            font-size: 16px;
            opacity: 0.9;
        }

        .content {
            padding: 30px;
        }

        .greeting {
            font-size: 18px;
            color: #333333;
            margin-bottom: 15px;
        }

        .intro-text {
            color: #666666;
            margin-bottom: 25px;
            font-size: 16px;
        }

        .product-card {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 25px;
            text-align: center;
            margin: 25px 0;
        }

        .product-image {
            max-width: 200px;
            height: auto;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
        }

        .product-name {
            font-size: 22px;
            font-weight: 600;
            color: #2c5aa0;
            margin: 0 0 10px 0;
        }

        .product-price {
            font-size: 20px;
            font-weight: 600;
            color: #28a745;
            margin: 0 0 15px 0;
        }

        .product-description {
            color: #666666;
            font-size: 16px;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        .cta-button {
            display: inline-block;
            background-color: #2c5aa0;
            color: white !important;
            padding: 12px 30px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
        }

        .cta-button:hover {
            background-color: #1e3a6f;
        }

        .features {
            margin: 30px 0;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 6px;
        }

        .features h3 {
            color: #2c5aa0;
            margin: 0 0 15px 0;
            font-size: 18px;
        }

        .features ul {
            margin: 0;
            padding-left: 20px;
            color: #666666;
        }

        .features li {
            margin-bottom: 5px;
        }

        .closing {
            margin: 25px 0;
            padding: 20px;
            background-color: #e9f4ff;
            border-radius: 6px;
            border-left: 4px solid #2c5aa0;
        }

        .closing p {
            margin: 0 0 10px 0;
            color: #333333;
        }

        .signature {
            font-weight: 600;
            color: #2c5aa0;
        }

        .footer {
            background-color: #6c757d;
            color: white;
            padding: 25px 30px;
            text-align: center;
            font-size: 14px;
        }

        .footer p {
            margin: 0 0 10px 0;
            opacity: 0.9;
        }

        .footer a {
            color: #ffffff;
            text-decoration: underline;
        }

        .footer a:hover {
            text-decoration: none;
        }

        @media (max-width: 600px) {
            .email-container {
                margin: 10px;
                border-radius: 0;
            }

            .header, .content, .footer {
                padding: 20px;
            }

            .product-card {
                padding: 20px;
            }

            .product-image {
                max-width: 150px;
            }

            .product-name {
                font-size: 20px;
            }

            .product-price {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>New Product Available</h1>
            <p>Fresh arrivals in our store</p>
        </div>

        <div class="content">
            <div class="greeting">Hello {{ $user->name }},</div>

            <p class="intro-text">
                We're pleased to announce a new product has been added to our collection.
                We thought you'd be interested in taking a look.
            </p>

            <div class="product-card">
                @if ($fruit->image_url)
                    <img src="{{ $fruit->image_url }}" alt="{{ $fruit->name }}" class="product-image">
                @endif

                <h2 class="product-name">{{ $fruit->name }}</h2>
                <div class="product-price">${{ number_format($fruit->price, 2) }}</div>
                <p class="product-description">{{ $fruit->description }}</p>

                <a href="{{ $frontendUrl }}/fruits/{{ $fruit->id }}" class="cta-button">
                    View Product
                </a>
            </div>

            <div class="features">
                <h3>Why Choose Our Products?</h3>
                <ul>
                    <li>Fresh and high-quality produce</li>
                    <li>Competitive pricing</li>
                    <li>Fast and reliable delivery</li>
                    <li>Customer satisfaction guarantee</li>
                </ul>
            </div>

            <div class="closing">
                <p>Thank you for being a valued customer. Browse our full selection of fresh fruits and vegetables in our online store.</p>
                <p class="signature">Best regards,<br>The Store Team</p>
            </div>
        </div>

        <div class="footer">
            <p>You received this email because you subscribed to product notifications.</p>
            <p>
                <a href="{{ url('/notification-settings') }}">Manage your email preferences</a> |
                <a href="#">Unsubscribe</a>
            </p>
        </div>
    </div>
</body>
</html>
