@extends('layouts.admin_layout')

@section('title', 'Soft Skill Points')

@section('content')
    <style>
        .page-header {
            display: grid;
            grid-template-columns: 1fr auto;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 2px solid #1f1f1f;
        }
        .page-header h2 {
            margin: 0;
            font-size: 24px;
        }
        .page-search input {
            width: min(420px, 100%);
            border: 1px solid #3a3a3a;
            border-radius: 4px;
            padding: 8px 12px;
            font-size: 15px;
            background: #fff;
        }
        .status-banner {
            margin-top: 12px;
            padding: 10px 12px;
            border: 1px solid #d0d0d0;
            border-radius: 8px;
            background: #f8f8f8;
        }
        .setting-list {
            margin-top: 16px;
            display: grid;
            gap: 14px;
            max-width: 980px;
        }
        .global-card {
            border: 1px solid #9fb6d8;
            border-radius: 10px;
            padding: 14px;
            background: #f6faff;
            display: grid;
            gap: 10px;
            max-width: 980px;
            margin-top: 14px;
        }
        .global-card h3 {
            margin: 0;
            font-size: 20px;
            color: #1f3f67;
        }
        .setting-card {
            border: 1px solid #d0d0d0;
            border-radius: 10px;
            padding: 14px;
            background: #fff;
            display: grid;
            gap: 10px;
        }
        .setting-card h3 {
            margin: 0;
            font-size: 20px;
        }
        .setting-meta {
            font-size: 13px;
            color: #4a4a4a;
        }
        .setting-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(220px, 1fr));
            gap: 10px;
        }
        .field label {
            display: block;
            font-size: 13px;
            color: #333;
            margin-bottom: 6px;
        }
        .field input {
            width: 100%;
            border: 1px solid #cfcfcf;
            border-radius: 6px;
            padding: 10px 12px;
            font-size: 14px;
            background: #fff;
        }
        .position-list {
            display: grid;
            gap: 8px;
        }
        .position-row {
            display: grid;
            grid-template-columns: 1fr 160px auto;
            gap: 8px;
            align-items: center;
        }
        .position-row button,
        .position-add,
        .setting-save {
            border: 1px solid #1f1f1f;
            border-radius: 6px;
            background: #fff;
            padding: 8px 12px;
            cursor: pointer;
            font-size: 14px;
        }
        .setting-actions {
            display: flex;
            justify-content: flex-end;
        }
        .empty-state {
            margin-top: 18px;
            border: 1px dashed #d0d0d0;
            border-radius: 10px;
            padding: 18px;
            color: #4a4a4a;
            max-width: 980px;
        }
        @media (max-width: 900px) {
            .page-header {
                grid-template-columns: 1fr;
            }
            .setting-grid {
                grid-template-columns: 1fr;
            }
            .position-row {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="page-header">
        <h2>Soft Skill Points</h2>
        <form class="page-search" method="GET" action="{{ route('admin.soft-skills.index') }}">
            <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search event or club">
        </form>
    </div>

    @if (session('status'))
        <div class="status-banner">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="status-banner">{{ $errors->first() }}</div>
    @endif

    <form class="global-card setting-card" method="POST" action="{{ route('admin.soft-skills.apply-all') }}">
        @csrf
        <h3>Apply Same Schema To All Approved Events</h3>
        <div class="setting-meta">
            Use this when you want all approved events to share one soft-skill schema.
        </div>
        <div class="setting-grid">
            <div class="field">
                <label>Participant points (attended student)</label>
                <input
                    type="number"
                    name="participant_points"
                    min="0"
                    max="1000"
                    value="{{ old('participant_points', 0) }}"
                    required
                >
            </div>
            <div class="field">
                <label>Volunteer base points (committee)</label>
                <input
                    type="number"
                    name="volunteer_base_points"
                    min="0"
                    max="1000"
                    value="{{ old('volunteer_base_points', 0) }}"
                    required
                >
            </div>
        </div>
        <div class="field">
            <label>Volunteer position points</label>
            <div class="position-list" data-position-list>
                <div class="position-row">
                    <input type="text" name="position_name[]" placeholder="e.g. Chairperson">
                    <input type="number" name="position_points[]" min="0" max="1000" placeholder="Points">
                    <button type="button" data-position-remove>Remove</button>
                </div>
            </div>
            <button type="button" class="position-add" data-position-add>Add Position Rule</button>
        </div>
        <div class="setting-actions">
            <button type="submit" class="setting-save">Apply To All Approved Events</button>
        </div>
    </form>

    @if ($events->isEmpty())
        <div class="empty-state">No approved events found.</div>
    @else
        <div class="setting-list">
            @foreach ($events as $event)
                @php
                    $setting = $event->softSkillSetting;
                    $positions = $setting?->positionPoints ?? collect();
                @endphp
                <form class="setting-card" method="POST" action="{{ route('admin.soft-skills.update', $event) }}">
                    @csrf
                    <h3>{{ $event->name }}</h3>
                    <div class="setting-meta">
                        Organizer: {{ $event->club?->display_name ?: ($event->club?->name ?? '-') }}
                    </div>
                    <div class="setting-grid">
                        <div class="field">
                            <label>Participant points (attended student)</label>
                            <input
                                type="number"
                                name="participant_points"
                                min="0"
                                max="1000"
                                value="{{ old('participant_points', $setting->participant_points ?? 0) }}"
                                required
                            >
                        </div>
                        <div class="field">
                            <label>Volunteer base points (committee)</label>
                            <input
                                type="number"
                                name="volunteer_base_points"
                                min="0"
                                max="1000"
                                value="{{ old('volunteer_base_points', $setting->volunteer_base_points ?? 0) }}"
                                required
                            >
                        </div>
                    </div>

                    <div class="field">
                        <label>Volunteer position points</label>
                        <div class="position-list" data-position-list>
                            @if ($positions->isEmpty())
                                <div class="position-row">
                                    <input type="text" name="position_name[]" placeholder="e.g. Chairperson">
                                    <input type="number" name="position_points[]" min="0" max="1000" placeholder="Points">
                                    <button type="button" data-position-remove>Remove</button>
                                </div>
                            @else
                                @foreach ($positions as $position)
                                    <div class="position-row">
                                        <input type="text" name="position_name[]" value="{{ $position->position_name }}" placeholder="e.g. Chairperson">
                                        <input type="number" name="position_points[]" min="0" max="1000" value="{{ $position->points }}" placeholder="Points">
                                        <button type="button" data-position-remove>Remove</button>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                        <button type="button" class="position-add" data-position-add>Add Position Rule</button>
                    </div>

                    <div class="setting-actions">
                        <button type="submit" class="setting-save">Save Points</button>
                    </div>
                </form>
            @endforeach
        </div>
    @endif

    <script>
        (function () {
            const cards = document.querySelectorAll('.setting-card');
            cards.forEach((card) => {
                const list = card.querySelector('[data-position-list]');
                const addBtn = card.querySelector('[data-position-add]');
                if (!list || !addBtn) {
                    return;
                }

                const bindRemove = () => {
                    list.querySelectorAll('[data-position-remove]').forEach((btn) => {
                        if (btn.dataset.bound) {
                            return;
                        }
                        btn.dataset.bound = 'true';
                        btn.addEventListener('click', () => {
                            const rows = list.querySelectorAll('.position-row');
                            if (rows.length <= 1) {
                                const nameInput = rows[0]?.querySelector('input[name="position_name[]"]');
                                const pointInput = rows[0]?.querySelector('input[name="position_points[]"]');
                                if (nameInput) nameInput.value = '';
                                if (pointInput) pointInput.value = '';
                                return;
                            }
                            btn.closest('.position-row')?.remove();
                        });
                    });
                };

                addBtn.addEventListener('click', () => {
                    const row = document.createElement('div');
                    row.className = 'position-row';
                    row.innerHTML =
                        '<input type="text" name="position_name[]" placeholder="e.g. Chairperson">' +
                        '<input type="number" name="position_points[]" min="0" max="1000" placeholder="Points">' +
                        '<button type="button" data-position-remove>Remove</button>';
                    list.appendChild(row);
                    bindRemove();
                });

                bindRemove();
            });
        })();
    </script>
@endsection
