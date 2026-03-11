@extends('layouts.club')

@section('title', 'E-Ticket Settings')

@section('content')
    <style>
        .ticket-header {
            padding: 12px 0;
            border-bottom: 1px solid #dbe4f0;
        }
        .ticket-header h2 {
            margin: 0;
            font-size: 26px;
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
            border: 1px solid #c4d6ed;
            border-radius: 10px;
            padding: 8px 10px;
            font-size: 14px;
            min-width: 260px;
            max-width: 360px;
        }
        .ticket-search button,
        .ticket-search a {
            padding: 8px 12px;
            border-radius: 10px;
            border: 1px solid #aac4e6;
            background: #f8fbff;
            cursor: pointer;
            color: #0b4ea5;
            font-weight: 700;
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
            border: 1px solid #dbe4f0;
            border-radius: 14px;
            background: linear-gradient(180deg, #fff 0%, #f9fbff 100%);
            padding: 16px 18px;
            display: grid;
            gap: 12px;
            box-shadow: 0 16px 30px -28px rgba(15, 23, 42, 0.7);
        }
        .ticket-card h3 {
            margin: 0;
            font-size: 20px;
        }
        .ticket-meta {
            color: #355070;
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
            border: 1px solid #c4d6ed;
            border-radius: 10px;
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
            border-radius: 10px;
            border: 1px solid #0b4ea5;
            background: #f8fbff;
            color: #0b4ea5;
            font-weight: 700;
            cursor: pointer;
        }
        .bundle-box {
            border: 1px solid #dbe4f0;
            border-radius: 12px;
            padding: 12px;
            display: grid;
            gap: 10px;
            background: #f8fbff;
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
            border-radius: 8px;
            border: 1px solid #aac4e6;
            background: #fff;
            cursor: pointer;
            font-size: 13px;
            color: #0b4ea5;
            font-weight: 700;
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
            border-radius: 8px;
            border: 1px solid #c4d6ed;
            background: #fff;
            cursor: pointer;
            font-size: 13px;
        }
        .bundle-empty {
            font-size: 13px;
            color: #666;
        }
        .eb-select-row {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 8px;
            align-items: center;
        }
        .eb-add {
            padding: 8px 10px;
            border-radius: 8px;
            border: 1px solid #aac4e6;
            background: #fff;
            cursor: pointer;
            font-size: 13px;
            color: #0b4ea5;
            font-weight: 700;
        }
        .eb-selected-list {
            display: grid;
            gap: 8px;
        }
        .eb-chip {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            border: 1px solid #cfe0f5;
            border-radius: 10px;
            background: #fff;
            padding: 8px 10px;
            font-size: 13px;
            color: #1f1f1f;
        }
        .eb-remove {
            padding: 6px 8px;
            border-radius: 8px;
            border: 1px solid #c4d6ed;
            background: #fff;
            cursor: pointer;
            font-size: 12px;
            color: #555;
        }
        .status-banner {
            margin-top: 12px;
            padding: 10px 12px;
            border: 1px solid #b8cae5;
            border-radius: 10px;
            background: #f6faff;
            color: #355070;
        }
        .empty-state {
            margin-top: 20px;
            padding: 24px;
            border: 1px dashed #bfd2ea;
            border-radius: 12px;
            text-align: center;
            color: #4b6079;
            background: #f8fbff;
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
                    $earlyBirdEnabled = $useOldBundle
                        ? old('early_bird_enabled') === '1'
                        : (bool) ($setting?->early_bird_enabled ?? false);
                    $selectedEarlyBirdFaculties = $useOldBundle
                        ? (array) old('early_bird_faculties', [])
                        : (array) ($setting?->early_bird_faculties ?? []);
                    $selectedEarlyBirdYears = $useOldBundle
                        ? (array) old('early_bird_study_years', [])
                        : (array) ($setting?->early_bird_study_years ?? []);
                    $selectedEarlyBirdRoles = $useOldBundle
                        ? (array) old('early_bird_roles', [])
                        : (array) ($setting?->early_bird_roles ?? []);
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
                        <div class="bundle-box">
                            <div class="bundle-header">
                                <div class="bundle-title">Early Bird Settings</div>
                            </div>
                            <div class="field">
                                <label>
                                    <input type="checkbox" name="early_bird_enabled" value="1" @checked($earlyBirdEnabled)>
                                    Enable early bird access
                                </label>
                            </div>
                            <div class="row">
                                <div class="field">
                                    <label>Early Bird Start</label>
                                    <input type="datetime-local" name="early_bird_start_at" value="{{ old('early_bird_start_at', optional($setting?->early_bird_start_at)->format('Y-m-d\\TH:i')) }}">
                                </div>
                                <div class="field">
                                    <label>Early Bird End</label>
                                    <input type="datetime-local" name="early_bird_end_at" value="{{ old('early_bird_end_at', optional($setting?->early_bird_end_at)->format('Y-m-d\\TH:i')) }}">
                                </div>
                            </div>
                            <div class="field">
                                <label>Early Bird Discount %</label>
                                <input type="number" name="early_bird_discount_percent" min="0.01" max="100" step="0.01" value="{{ old('early_bird_discount_percent', $setting?->early_bird_discount_percent ?? '') }}" placeholder="e.g. 20">
                            </div>
                            <div class="field">
                                <label>Eligible Faculties</label>
                                <div class="eb-select-row">
                                    <select data-eb-select>
                                        <option value="">Select faculty</option>
                                        @foreach (($availableFaculties ?? []) as $faculty)
                                            <option value="{{ $faculty }}">{{ $faculty }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="eb-add" data-eb-add data-input-name="early_bird_faculties[]">Add</button>
                                </div>
                                <div class="eb-selected-list" data-eb-list data-input-name="early_bird_faculties[]">
                                    @forelse ($selectedEarlyBirdFaculties as $faculty)
                                        <div class="eb-chip" data-eb-item data-value="{{ $faculty }}">
                                            <span>{{ $faculty }}</span>
                                            <input type="hidden" name="early_bird_faculties[]" value="{{ $faculty }}">
                                            <button type="button" class="eb-remove" data-eb-remove>Remove</button>
                                        </div>
                                    @empty
                                        <div class="bundle-empty" data-eb-empty>No faculty selected.</div>
                                    @endforelse
                                </div>
                            </div>
                            <div class="field">
                                <label>Eligible Student Sessions / Years</label>
                                <div class="eb-select-row">
                                    <select data-eb-select>
                                        <option value="">Select session/year</option>
                                        @foreach (($availableStudyYears ?? []) as $studyYear)
                                            <option value="{{ $studyYear }}">{{ $studyYear }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="eb-add" data-eb-add data-input-name="early_bird_study_years[]">Add</button>
                                </div>
                                <div class="eb-selected-list" data-eb-list data-input-name="early_bird_study_years[]">
                                    @forelse ($selectedEarlyBirdYears as $studyYear)
                                        <div class="eb-chip" data-eb-item data-value="{{ $studyYear }}">
                                            <span>{{ $studyYear }}</span>
                                            <input type="hidden" name="early_bird_study_years[]" value="{{ $studyYear }}">
                                            <button type="button" class="eb-remove" data-eb-remove>Remove</button>
                                        </div>
                                    @empty
                                        <div class="bundle-empty" data-eb-empty>No session/year selected.</div>
                                    @endforelse
                                </div>
                            </div>
                            <div class="field">
                                <label>Eligible Roles</label>
                                <div class="eb-select-row">
                                    <select data-eb-select>
                                        <option value="">Select role</option>
                                        @foreach (($availableEarlyBirdRoles ?? []) as $earlyRole)
                                            <option value="{{ $earlyRole }}">{{ ucfirst($earlyRole) }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="eb-add" data-eb-add data-input-name="early_bird_roles[]">Add</button>
                                </div>
                                <div class="eb-selected-list" data-eb-list data-input-name="early_bird_roles[]">
                                    @forelse ($selectedEarlyBirdRoles as $earlyRole)
                                        <div class="eb-chip" data-eb-item data-value="{{ $earlyRole }}">
                                            <span>{{ ucfirst($earlyRole) }}</span>
                                            <input type="hidden" name="early_bird_roles[]" value="{{ $earlyRole }}">
                                            <button type="button" class="eb-remove" data-eb-remove>Remove</button>
                                        </div>
                                    @empty
                                        <div class="bundle-empty" data-eb-empty>No role selected.</div>
                                    @endforelse
                                </div>
                            </div>
                            <div class="bundle-empty">Tip: If no faculty/year/role is selected, all students are eligible during early bird window.</div>
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

            function refreshEarlyBirdEmpty(list, message) {
                if (!list) return;
                var itemCount = list.querySelectorAll('[data-eb-item]').length;
                var empty = list.querySelector('[data-eb-empty]');
                if (itemCount === 0 && !empty) {
                    var hint = document.createElement('div');
                    hint.className = 'bundle-empty';
                    hint.setAttribute('data-eb-empty', '1');
                    hint.textContent = message || 'No selection.';
                    list.appendChild(hint);
                }
                if (itemCount > 0 && empty) {
                    empty.remove();
                }
            }

            document.querySelectorAll('[data-eb-add]').forEach(function (button) {
                button.addEventListener('click', function () {
                    var field = button.closest('.field');
                    if (!field) return;
                    var select = field.querySelector('[data-eb-select]');
                    var list = field.querySelector('[data-eb-list]');
                    if (!select || !list) return;

                    var value = (select.value || '').trim();
                    if (!value) return;

                    var exists = Array.from(list.querySelectorAll('[data-eb-item]')).some(function (node) {
                        return (node.getAttribute('data-value') || '') === value;
                    });
                    if (exists) return;

                    var option = select.options[select.selectedIndex];
                    var label = option ? option.text : value;
                    var inputName = button.getAttribute('data-input-name') || '';

                    var row = document.createElement('div');
                    row.className = 'eb-chip';
                    row.setAttribute('data-eb-item', '1');
                    row.setAttribute('data-value', value);
                    row.innerHTML = '<span></span><input type="hidden"><button type="button" class="eb-remove" data-eb-remove>Remove</button>';
                    row.querySelector('span').textContent = label;
                    var hidden = row.querySelector('input[type=\"hidden\"]');
                    hidden.name = inputName;
                    hidden.value = value;

                    list.appendChild(row);
                    var hint = list.querySelector('[data-eb-empty]');
                    if (hint) hint.remove();
                });
            });

            document.querySelectorAll('[data-eb-list]').forEach(function (list) {
                list.addEventListener('click', function (event) {
                    var target = event.target;
                    if (!(target instanceof HTMLElement) || !target.matches('[data-eb-remove]')) {
                        return;
                    }
                    var row = target.closest('[data-eb-item]');
                    if (row) row.remove();
                    var message = 'No selection.';
                    if ((list.getAttribute('data-input-name') || '') === 'early_bird_faculties[]') message = 'No faculty selected.';
                    if ((list.getAttribute('data-input-name') || '') === 'early_bird_study_years[]') message = 'No session/year selected.';
                    if ((list.getAttribute('data-input-name') || '') === 'early_bird_roles[]') message = 'No role selected.';
                    refreshEarlyBirdEmpty(list, message);
                });

                var msg = 'No selection.';
                if ((list.getAttribute('data-input-name') || '') === 'early_bird_faculties[]') msg = 'No faculty selected.';
                if ((list.getAttribute('data-input-name') || '') === 'early_bird_study_years[]') msg = 'No session/year selected.';
                if ((list.getAttribute('data-input-name') || '') === 'early_bird_roles[]') msg = 'No role selected.';
                refreshEarlyBirdEmpty(list, msg);
            });
        })();
    </script>
@endsection
