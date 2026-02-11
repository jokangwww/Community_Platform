@extends('layouts.club')

@section('title', 'E-Ticket Settings')

@section('content')
    <style>
        .ticket-header {
            padding: 12px 0;
            border-bottom: 2px solid #1f1f1f;
        }
        .ticket-header h2 {
            margin: 0;
            font-size: 22px;
        }
        .ticket-header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }
        .ticket-search {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .ticket-search input {
            border: 1px solid #cfcfcf;
            border-radius: 6px;
            padding: 8px 10px;
            font-size: 14px;
            min-width: 260px;
            max-width: 360px;
        }
        .ticket-search button,
        .ticket-search a {
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid #1f1f1f;
            background: #fff;
            cursor: pointer;
            color: inherit;
            text-decoration: none;
            font-size: 14px;
            line-height: 1.2;
        }
        .ticket-list {
            margin-top: 16px;
            display: grid;
            gap: 16px;
        }
        .ticket-card {
            border: 1px solid #cfcfcf;
            border-radius: 10px;
            background: #fff;
            padding: 16px 18px;
            display: grid;
            gap: 12px;
        }
        .ticket-card h3 {
            margin: 0;
            font-size: 20px;
        }
        .ticket-meta {
            color: #4a4a4a;
            font-size: 14px;
        }
        .ticket-form {
            display: grid;
            gap: 10px;
            max-width: 560px;
        }
        .ticket-form .field {
            display: grid;
            gap: 6px;
        }
        .ticket-form label {
            font-size: 13px;
            color: #2f2f2f;
        }
        .ticket-form input,
        .ticket-form select {
            border: 1px solid #cfcfcf;
            border-radius: 6px;
            padding: 8px 10px;
            font-size: 14px;
            background: #fff;
        }
        .ticket-form .row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        .ticket-actions {
            display: flex;
            gap: 10px;
        }
        .ticket-actions button {
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid #1f1f1f;
            background: #fff;
            cursor: pointer;
        }
        .bundle-box {
            border: 1px solid #d9d9d9;
            border-radius: 8px;
            padding: 12px;
            display: grid;
            gap: 10px;
            background: #fafafa;
        }
        .bundle-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }
        .bundle-title {
            font-size: 13px;
            color: #2f2f2f;
            font-weight: 600;
        }
        .bundle-add {
            padding: 6px 10px;
            border-radius: 6px;
            border: 1px solid #1f1f1f;
            background: #fff;
            cursor: pointer;
            font-size: 13px;
        }
        .bundle-list {
            display: grid;
            gap: 8px;
        }
        .bundle-row {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 8px;
            align-items: end;
        }
        .bundle-remove {
            padding: 8px 10px;
            border-radius: 6px;
            border: 1px solid #b8b8b8;
            background: #fff;
            cursor: pointer;
            font-size: 13px;
        }
        .bundle-empty {
            font-size: 13px;
            color: #666;
        }
        .status-banner {
            margin-top: 12px;
            padding: 10px 12px;
            border: 1px solid #c2c2c2;
            border-radius: 8px;
            background: #f7f7f7;
        }
        .empty-state {
            margin-top: 20px;
            padding: 24px;
            border: 1px dashed #c2c2c2;
            border-radius: 10px;
            text-align: center;
            color: #4a4a4a;
        }
        @media (max-width: 700px) {
            .ticket-search {
                width: 100%;
            }
            .ticket-search input {
                min-width: 0;
                width: 100%;
                max-width: none;
            }
            .bundle-row {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="ticket-header">
        <div class="ticket-header-row">
            <h2>E-Ticket Settings</h2>
            <form class="ticket-search" method="GET" action="{{ route('club.tickets.index') }}">
                <input type="text" name="q" value="{{ $search ?? '' }}" placeholder="Search by event name or ID">
                <button type="submit">Search</button>
                @if (!empty($search))
                    <a href="{{ route('club.tickets.index') }}">Clear</a>
                @endif
            </form>
        </div>
    </div>

    @if (session('status'))
        <div class="status-banner">{{ session('status') }}</div>
    @endif

    @if ($events->isEmpty())
        <div class="empty-state">
            {{ !empty($search) ? 'No ticket-required events match your search.' : 'No ticket-required events found.' }}
        </div>
    @else
        <div class="ticket-list">
            @foreach ($events as $event)
                @php
                    $setting = $event->ticketSetting;
                    $useOldBundle = (string) old('event_id') === (string) $event->id;
                    $bundles = $setting?->bundle_discounts ?? [];
                    if ($useOldBundle) {
                        $oldQty = old('bundle_quantity', []);
                        $oldPercent = old('bundle_discount_percent', []);
                        $bundles = [];
                        $max = max(count($oldQty), count($oldPercent));
                        for ($i = 0; $i < $max; $i++) {
                            $bundles[] = [
                                'quantity' => $oldQty[$i] ?? '',
                                'discount_percent' => $oldPercent[$i] ?? '',
                            ];
                        }
                    }
                @endphp
                <div class="ticket-card">
                    <div>
                        <h3>{{ $event->name }}</h3>
                        <div class="ticket-meta">Event ID: {{ $event->id }}</div>
                    </div>
                    <form class="ticket-form" method="POST" action="{{ route('club.tickets.update', $event) }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="event_id" value="{{ $event->id }}">
                        <div class="row">
                            <div class="field">
                                <label for="price-{{ $event->id }}">Ticket Price</label>
                                <input id="price-{{ $event->id }}" name="price" type="number" min="0.01" step="0.01" value="{{ old('price', $setting?->price ?? 0) }}" required>
                            </div>
                            <div class="field">
                                <label for="currency-{{ $event->id }}">Currency</label>
                                <input id="currency-{{ $event->id }}" name="currency" type="text" value="{{ old('currency', $setting?->currency ?? 'MYR') }}" maxlength="3">
                            </div>
                        </div>
                        <div class="row">
                            <div class="field">
                                <label for="prefix-{{ $event->id }}">Prefix (optional)</label>
                                <input id="prefix-{{ $event->id }}" name="prefix" type="text" value="{{ old('prefix', $setting?->prefix ?? '') }}" maxlength="20">
                            </div>
                            <div class="field">
                                <label for="suffix-{{ $event->id }}">Suffix (optional)</label>
                                <input id="suffix-{{ $event->id }}" name="suffix" type="text" value="{{ old('suffix', $setting?->suffix ?? '') }}" maxlength="20">
                            </div>
                        </div>
                        <div class="row">
                            <div class="field">
                                <label for="start-{{ $event->id }}">Start Number</label>
                                <input id="start-{{ $event->id }}" name="start_number" type="number" min="0" step="1" value="{{ old('start_number', $setting?->start_number ?? 1) }}" required>
                            </div>
                            <div class="field">
                                <label for="padding-{{ $event->id }}">Number Padding</label>
                                <input id="padding-{{ $event->id }}" name="number_padding" type="number" min="0" max="6" step="1" value="{{ old('number_padding', $setting?->number_padding ?? 0) }}">
                            </div>
                        </div>
                        <div class="bundle-box" data-bundle-box>
                            <div class="bundle-header">
                                <div class="bundle-title">Bundle Discounts (optional)</div>
                                <button type="button" class="bundle-add" data-bundle-add>+ Add Bundle</button>
                            </div>
                            <div class="bundle-list" data-bundle-list>
                                @forelse ($bundles as $bundle)
                                    <div class="bundle-row" data-bundle-row>
                                        <div class="field">
                                            <label>Buy Quantity</label>
                                            <input type="number" name="bundle_quantity[]" min="2" max="100" step="1" value="{{ $bundle['quantity'] ?? '' }}" placeholder="e.g. 2">
                                        </div>
                                        <div class="field">
                                            <label>Discount %</label>
                                            <input type="number" name="bundle_discount_percent[]" min="0" max="100" step="0.01" value="{{ $bundle['discount_percent'] ?? '' }}" placeholder="e.g. 10">
                                        </div>
                                        <button type="button" class="bundle-remove" data-bundle-remove>Remove</button>
                                    </div>
                                @empty
                                    <div class="bundle-empty" data-bundle-empty>No bundle rules added yet.</div>
                                @endforelse
                            </div>
                        </div>
                        <div class="ticket-actions">
                            <button type="submit">Save Ticket Settings</button>
                        </div>
                    </form>
                </div>
            @endforeach
        </div>
    @endif

    <template id="bundle-row-template">
        <div class="bundle-row" data-bundle-row>
            <div class="field">
                <label>Buy Quantity</label>
                <input type="number" name="bundle_quantity[]" min="2" max="100" step="1" placeholder="e.g. 2">
            </div>
            <div class="field">
                <label>Discount %</label>
                <input type="number" name="bundle_discount_percent[]" min="0" max="100" step="0.01" placeholder="e.g. 10">
            </div>
            <button type="button" class="bundle-remove" data-bundle-remove>Remove</button>
        </div>
    </template>

    <script>
        (function () {
            var template = document.getElementById('bundle-row-template');
            if (!template) {
                return;
            }

            function refreshEmptyState(box) {
                var list = box.querySelector('[data-bundle-list]');
                if (!list) return;
                var rows = list.querySelectorAll('[data-bundle-row]');
                var empty = list.querySelector('[data-bundle-empty]');
                if (rows.length === 0 && !empty) {
                    var msg = document.createElement('div');
                    msg.className = 'bundle-empty';
                    msg.setAttribute('data-bundle-empty', '1');
                    msg.textContent = 'No bundle rules added yet.';
                    list.appendChild(msg);
                }
                if (rows.length > 0 && empty) {
                    empty.remove();
                }
            }

            document.querySelectorAll('[data-bundle-box]').forEach(function (box) {
                var addBtn = box.querySelector('[data-bundle-add]');
                var list = box.querySelector('[data-bundle-list]');
                if (!addBtn || !list) {
                    return;
                }

                addBtn.addEventListener('click', function () {
                    var node = template.content.firstElementChild.cloneNode(true);
                    var empty = list.querySelector('[data-bundle-empty]');
                    if (empty) {
                        empty.remove();
                    }
                    list.appendChild(node);
                });

                box.addEventListener('click', function (event) {
                    var target = event.target;
                    if (!(target instanceof HTMLElement) || !target.matches('[data-bundle-remove]')) {
                        return;
                    }

                    var row = target.closest('[data-bundle-row]');
                    if (row) {
                        row.remove();
                    }
                    refreshEmptyState(box);
                });

                refreshEmptyState(box);
            });
        })();
    </script>
@endsection
