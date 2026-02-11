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
        .checkout-breakdown {
            display: grid;
            gap: 8px;
            margin-bottom: 14px;
        }
        .checkout-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            font-size: 14px;
            color: #333;
        }
        .checkout-row strong {
            font-size: 16px;
            color: #111;
        }
        .checkout-row input {
            border: 1px solid #cfcfcf;
            border-radius: 6px;
            padding: 6px 8px;
            width: 84px;
            font-size: 14px;
        }
        .bundle-note {
            font-size: 13px;
            color: #5a5a5a;
            margin-bottom: 12px;
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
            Base price: {{ $setting->currency }} {{ number_format($setting->price, 2) }}
        </div>
        <div class="checkout-breakdown">
            <div class="checkout-row">
                <span>Quantity</span>
                <input id="ticket-quantity" type="number" min="1" max="100" step="1" value="1">
            </div>
            <div class="checkout-row">
                <span>Bundle discount</span>
                <span id="bundle-discount">0%</span>
            </div>
            <div class="checkout-row">
                <span>Total</span>
                <strong id="checkout-total">{{ $setting->currency }} {{ number_format($setting->price, 2) }}</strong>
            </div>
        </div>
        <div class="bundle-note" id="bundle-note"></div>
        <div id="paypal-button-container"></div>
        <div id="checkout-status" class="status-text" style="display:none;"></div>
    </div>

    <script src="https://www.paypal.com/sdk/js?client-id={{ $paypalClientId }}&currency={{ $setting->currency }}"></script>
    <script>
        (function () {
            var statusBox = document.getElementById('checkout-status');
            var quantityInput = document.getElementById('ticket-quantity');
            var discountText = document.getElementById('bundle-discount');
            var totalText = document.getElementById('checkout-total');
            var bundleNote = document.getElementById('bundle-note');
            var currency = "{{ $setting->currency }}";
            var basePrice = {{ number_format((float) $setting->price, 2, '.', '') }};
            var bundleDiscounts = @json($bundleDiscounts ?? []);

            function showStatus(message, isError) {
                if (!statusBox) return;
                statusBox.textContent = message;
                statusBox.style.display = 'block';
                statusBox.style.color = isError ? '#b00020' : '#1f7a1f';
            }

            function currentQuantity() {
                var value = parseInt((quantityInput && quantityInput.value) || '1', 10);
                if (isNaN(value) || value < 1) value = 1;
                if (value > 100) value = 100;
                if (quantityInput) quantityInput.value = String(value);
                return value;
            }

            function resolveDiscount(quantity) {
                for (var i = 0; i < bundleDiscounts.length; i++) {
                    if (parseInt(bundleDiscounts[i].quantity, 10) === quantity) {
                        return parseFloat(bundleDiscounts[i].discount_percent || 0) || 0;
                    }
                }
                return 0;
            }

            function updateSummary() {
                var qty = currentQuantity();
                var discount = resolveDiscount(qty);
                var subtotal = basePrice * qty;
                var total = Math.max(subtotal - (subtotal * (discount / 100)), 0);
                if (discountText) {
                    discountText.textContent = discount.toFixed(2).replace(/\.00$/, '') + '%';
                }
                if (totalText) {
                    totalText.textContent = currency + ' ' + total.toFixed(2);
                }
                if (bundleNote) {
                    if (bundleDiscounts.length === 0) {
                        bundleNote.textContent = 'No bundle discounts for this event.';
                    } else if (discount > 0) {
                        bundleNote.textContent = 'Bundle price applied for quantity ' + qty + '.';
                    } else {
                        bundleNote.textContent = 'No bundle discount for quantity ' + qty + '.';
                    }
                }
            }

            if (quantityInput) {
                quantityInput.addEventListener('input', updateSummary);
                quantityInput.addEventListener('change', updateSummary);
            }
            updateSummary();

            paypal.Buttons({
                createOrder: function () {
                    var qty = currentQuantity();
                    return fetch("{{ route('tickets.paypal.create', $event) }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': "{{ csrf_token() }}"
                        },
                        body: JSON.stringify({ quantity: qty })
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
                    var qty = currentQuantity();
                    return fetch("{{ route('tickets.paypal.capture', $event) }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': "{{ csrf_token() }}"
                        },
                        body: JSON.stringify({ orderID: data.orderID, quantity: qty })
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
