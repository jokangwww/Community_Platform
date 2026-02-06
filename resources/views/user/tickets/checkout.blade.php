@extends('layouts.user_layout')

@section('title', 'Checkout')

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
            max-width: 520px;
        }
        .checkout-card h3 {
            margin: 0 0 8px;
            font-size: 20px;
        }
        .checkout-meta {
            color: #4a4a4a;
            font-size: 14px;
            margin-bottom: 12px;
        }
        .checkout-amount {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 14px;
        }
        .status-text {
            margin-top: 12px;
            font-size: 13px;
            color: #1f7a1f;
        }
    </style>

    <div class="checkout-header">
        <h2>Checkout</h2>
    </div>

    <div class="checkout-card">
        <h3>{{ $event->name }}</h3>
        <div class="checkout-meta">Ticket required</div>
        <div class="checkout-amount">
            {{ $setting->currency }} {{ number_format($setting->price, 2) }}
        </div>
        <div id="paypal-button-container"></div>
        <div id="checkout-status" class="status-text" style="display:none;"></div>
    </div>

    <script src="https://www.paypal.com/sdk/js?client-id={{ $paypalClientId }}&currency={{ $setting->currency }}"></script>
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
                    return fetch("{{ route('tickets.paypal.create', $event) }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': "{{ csrf_token() }}"
                        },
                        body: JSON.stringify({})
                    }).then(function (res) {
                        return res.json();
                    }).then(function (data) {
                        if (!data || !data.id) {
                            throw new Error('Order ID missing');
                        }
                        return data.id;
                    });
                },
                onApprove: function (data) {
                    return fetch("{{ route('tickets.paypal.capture', $event) }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': "{{ csrf_token() }}"
                        },
                        body: JSON.stringify({ orderID: data.orderID })
                    }).then(function (res) {
                        return res.json();
                    }).then(function (result) {
                        if (result.ticketId) {
                            window.location.href = "{{ route('tickets.success', ['event' => $event, 'ticket' => '__TICKET__']) }}".replace('__TICKET__', result.ticketId);
                        } else {
                            showStatus('Payment completed but ticket not created.', true);
                        }
                    }).catch(function () {
                        showStatus('Payment failed. Please try again.', true);
                    });
                },
                onError: function () {
                    showStatus('PayPal error. Please try again.', true);
                }
            }).render('#paypal-button-container');
        })();
    </script>
@endsection
