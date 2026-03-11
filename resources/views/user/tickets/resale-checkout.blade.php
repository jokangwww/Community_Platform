@extends('layouts.user_layout')

@section('title', 'Resale Checkout')

@section('content')
    <style>
        .checkout-header {
            padding: 12px 0;
            border-bottom: 2px solid #1f1f1f;
        }
        .checkout-header h2 {
            margin: 0;
            font-size: 24px;
        }
        .checkout-card {
            margin-top: 16px;
            border: 1px solid #d6d6d6;
            border-radius: 10px;
            background: #fff;
            padding: 18px 20px;
            max-width: 560px;
        }
        .checkout-card h3 {
            margin: 0 0 8px;
            font-size: 20px;
        }
        .checkout-meta {
            color: #4a4a4a;
            font-size: 14px;
            margin-bottom: 12px;
            display: grid;
            gap: 4px;
        }
        .checkout-amount {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 12px;
        }
        .status-text {
            margin-top: 12px;
            font-size: 13px;
            color: #1f7a1f;
        }
        .back-link {
            display: inline-flex;
            margin-top: 10px;
            font-size: 14px;
            text-decoration: none;
            color: #0b4ea5;
        }
    </style>

    <div class="checkout-header">
        <h2>Resale Checkout</h2>
    </div>

    <div class="checkout-card">
        <h3>{{ $event->name ?? 'Event' }}</h3>
        <div class="checkout-meta">
            <div><strong>Ticket Number:</strong> {{ $ticket->ticket_number }}</div>
            <div><strong>Seller:</strong> {{ $seller->name ?? 'Student' }} ({{ $seller->student_id ?? '-' }})</div>
            <div><strong>Original Price:</strong> {{ $ticket->currency }} {{ number_format((float) $ticket->amount, 2) }}</div>
        </div>
        <div class="checkout-amount">
            Resale Price: {{ $ticket->currency }} {{ number_format((float) ($ticket->resale_price ?? 0), 2) }}
        </div>
        <div id="paypal-button-container"></div>
        <div id="checkout-status" class="status-text" style="display:none;"></div>
        <a class="back-link" href="{{ route('user.tickets.index', ['tab' => 'resell']) }}">Back to Resell Marketplace</a>
    </div>

    <script src="https://www.paypal.com/sdk/js?client-id={{ $paypalClientId }}&currency={{ $ticket->currency }}"></script>
    <script>
        (function () {
            var statusBox = document.getElementById('checkout-status');

            function showStatus(message, isError) {
                if (!statusBox) return;
                statusBox.textContent = message;
                statusBox.style.display = 'block';
                statusBox.style.color = isError ? '#b00020' : '#1f7a1f';
            }

            paypal.Buttons({
                createOrder: function () {
                    return fetch("{{ route('user.tickets.resell.paypal.create', $ticket) }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': "{{ csrf_token() }}"
                        },
                        body: JSON.stringify({})
                    }).then(function (res) {
                        return res.json().then(function (data) {
                            if (!res.ok) {
                                throw new Error((data && data.message) ? data.message : 'Unable to create PayPal order.');
                            }
                            return data;
                        });
                    }).then(function (data) {
                        if (!data || !data.id) {
                            throw new Error('Order ID missing');
                        }
                        return data.id;
                    });
                },
                onApprove: function (data) {
                    return fetch("{{ route('user.tickets.resell.paypal.capture', $ticket) }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': "{{ csrf_token() }}"
                        },
                        body: JSON.stringify({ orderID: data.orderID })
                    }).then(function (res) {
                        return res.json().then(function (payload) {
                            if (!res.ok) {
                                throw new Error((payload && payload.message) ? payload.message : 'Payment capture failed.');
                            }
                            return payload;
                        });
                    }).then(function (result) {
                        if (result && result.redirect) {
                            window.location.href = result.redirect;
                            return;
                        }
                        showStatus('Payment completed but redirect is missing.', true);
                    }).catch(function (error) {
                        showStatus(error && error.message ? error.message : 'Payment failed. Please try again.', true);
                    });
                },
                onError: function (error) {
                    showStatus((error && error.message) ? error.message : 'PayPal error. Please try again.', true);
                }
            }).render('#paypal-button-container');
        })();
    </script>
@endsection
