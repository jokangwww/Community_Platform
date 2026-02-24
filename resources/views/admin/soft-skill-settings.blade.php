@extends('layouts.admin_layout')

@section('title', 'Soft Skill Points')

@section('content')
    <style>
        .page-header { display:grid; grid-template-columns:1fr auto; align-items:center; gap:12px; padding:12px 0; border-bottom:2px solid #1f1f1f; }
        .page-header h2 { margin:0; font-size:24px; }
        .page-search input { width:min(420px,100%); border:1px solid #3a3a3a; border-radius:4px; padding:8px 12px; font-size:15px; background:#fff; }
        .status-banner { margin-top:12px; padding:10px 12px; border:1px solid #d0d0d0; border-radius:8px; background:#f8f8f8; }
        .panel { margin-top:14px; max-width:1100px; border:1px solid #d0d0d0; border-radius:10px; background:#fff; padding:14px; display:grid; gap:10px; }
        .panel h3 { margin:0; font-size:20px; }
        .panel-meta { font-size:13px; color:#4a4a4a; }
        .score-grid { display:grid; grid-template-columns:repeat(7, minmax(80px,1fr)); gap:8px; }
        .score-grid .field label { display:block; font-size:12px; color:#555; margin-bottom:4px; }
        .field input, .field select { width:100%; border:1px solid #cfcfcf; border-radius:6px; padding:9px 10px; font-size:14px; background:#fff; }
        .row-grid { display:grid; grid-template-columns:1.2fr repeat(7, minmax(70px,1fr)) auto; gap:8px; align-items:center; }
        .rule-list { display:grid; gap:8px; }
        .btn { border:1px solid #1f1f1f; border-radius:6px; background:#fff; padding:8px 12px; cursor:pointer; font-size:14px; }
        .actions { display:flex; justify-content:space-between; gap:10px; align-items:center; flex-wrap:wrap; }
        .event-list { margin-top:14px; display:grid; gap:12px; max-width:1100px; }
        .event-card { border:1px solid #d0d0d0; border-radius:10px; padding:12px; background:#fff; display:grid; gap:8px; }
        .event-title { font-size:18px; font-weight:600; }
        .event-row { display:grid; grid-template-columns:1fr auto; gap:10px; align-items:center; }
        .event-row form { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
        .hint { font-size:12px; color:#666; }
        .empty-state { margin-top:18px; border:1px dashed #d0d0d0; border-radius:10px; padding:18px; color:#4a4a4a; max-width:1100px; }
        @media (max-width: 900px) {
            .page-header { grid-template-columns:1fr; }
            .score-grid { grid-template-columns:repeat(2,1fr); }
            .row-grid { grid-template-columns:1fr; }
            .event-row { grid-template-columns:1fr; }
        }
    </style>

    @php
        $elements = ['cs' => 'CS', 'ctps' => 'CTPS', 'ts' => 'TS', 'll' => 'LL', 'kk' => 'KK', 'em' => 'EM', 'ls' => 'LS'];
    @endphp

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

    <div class="panel">
        <h3>Create Soft Skill Category</h3>
        <div class="panel-meta">Admin defines category + participant 7-element score + position rules (7 elements each).</div>
        <form method="POST" action="{{ route('admin.soft-skills.categories.store') }}">
            @csrf
            <div class="field">
                <label>Category Name</label>
                <input type="text" name="name" value="{{ old('name') }}" placeholder="e.g. Orientation Programme" required>
            </div>
            <div class="panel-meta">Participant Score (0-3 per element)</div>
            <div class="score-grid">
                @foreach ($elements as $key => $label)
                    <div class="field">
                        <label>{{ $label }}</label>
                        <input type="number" name="participant_{{ $key }}" min="0" max="3" value="{{ old('participant_' . $key, 0) }}" required>
                    </div>
                @endforeach
            </div>
            <div class="panel-meta">Position Rules (0-3 per element)</div>
            <div class="rule-list" data-rule-list>
                <div class="row-grid">
                    <input type="text" name="rule_position_name[]" placeholder="Position name (e.g. Organising President)">
                    @foreach ($elements as $key => $label)
                        <input type="number" name="rule_{{ $key }}[]" min="0" max="3" placeholder="{{ $label }}">
                    @endforeach
                    <button type="button" class="btn" data-rule-remove>Remove</button>
                </div>
            </div>
            <div class="actions">
                <button type="button" class="btn" data-rule-add>Add Position Rule</button>
                <button type="submit" class="btn">Create Category</button>
            </div>
        </form>
    </div>

    @foreach (($categories ?? collect()) as $category)
        <div class="panel">
            <h3>{{ $category->name }}</h3>
            <div class="panel-meta">Update category scores and position rules.</div>
            <form method="POST" action="{{ route('admin.soft-skills.categories.update', $category) }}">
                @csrf
                <div class="field">
                    <label>Category Name</label>
                    <input type="text" name="name" value="{{ $category->name }}" required>
                </div>
                <div class="panel-meta">Participant Score (0-3 per element)</div>
                <div class="score-grid">
                    @foreach ($elements as $key => $label)
                        <div class="field">
                            <label>{{ $label }}</label>
                            <input type="number" name="participant_{{ $key }}" min="0" max="3" value="{{ $category->{'participant_' . $key} }}" required>
                        </div>
                    @endforeach
                </div>
                <div class="panel-meta">Position Rules (0-3 per element)</div>
                <div class="rule-list" data-rule-list>
                    @forelse ($category->positionRules as $rule)
                        <div class="row-grid">
                            <input type="text" name="rule_position_name[]" value="{{ $rule->position_name }}" placeholder="Position name">
                            @foreach ($elements as $key => $label)
                                <input type="number" name="rule_{{ $key }}[]" min="0" max="3" value="{{ $rule->{$key} }}" placeholder="{{ $label }}">
                            @endforeach
                            <button type="button" class="btn" data-rule-remove>Remove</button>
                        </div>
                    @empty
                        <div class="row-grid">
                            <input type="text" name="rule_position_name[]" placeholder="Position name">
                            @foreach ($elements as $key => $label)
                                <input type="number" name="rule_{{ $key }}[]" min="0" max="3" placeholder="{{ $label }}">
                            @endforeach
                            <button type="button" class="btn" data-rule-remove>Remove</button>
                        </div>
                    @endforelse
                </div>
                <div class="actions">
                    <button type="button" class="btn" data-rule-add>Add Position Rule</button>
                    <button type="submit" class="btn">Save Category</button>
                </div>
            </form>
        </div>
    @endforeach

    <div class="panel">
        <h3>Assign Category To Events</h3>
        <div class="panel-meta">Club event management will use the selected category's position rules.</div>
        <form method="POST" action="{{ route('admin.soft-skills.events.apply-category') }}" class="actions">
            @csrf
            <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                <span class="hint">Apply one category to all approved events:</span>
                <select name="soft_skill_category_id" required>
                    <option value="">Select category</option>
                    @foreach (($categories ?? collect()) as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn">Apply Category To All</button>
        </form>

        @if (($events ?? collect())->isEmpty())
            <div class="empty-state">No approved events found.</div>
        @else
            <div class="event-list">
                @foreach ($events as $event)
                    <div class="event-card">
                        <div class="event-title">{{ $event->name }}</div>
                        <div class="event-row">
                            <div class="hint">Organizer: {{ $event->club?->display_name ?: ($event->club?->name ?? '-') }}</div>
                            <form method="POST" action="{{ route('admin.soft-skills.events.assign-category', $event) }}">
                                @csrf
                                <select name="soft_skill_category_id">
                                    <option value="">No category</option>
                                    @foreach (($categories ?? collect()) as $category)
                                        <option value="{{ $category->id }}" @selected((int) $event->soft_skill_category_id === (int) $category->id)>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn">Save Event Category</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <script>
        (function () {
            const makeRuleRow = () => {
                const row = document.createElement('div');
                row.className = 'row-grid';
                row.innerHTML =
                    '<input type="text" name="rule_position_name[]" placeholder="Position name">' +
                    '<input type="number" name="rule_cs[]" min="0" max="3" placeholder="CS">' +
                    '<input type="number" name="rule_ctps[]" min="0" max="3" placeholder="CTPS">' +
                    '<input type="number" name="rule_ts[]" min="0" max="3" placeholder="TS">' +
                    '<input type="number" name="rule_ll[]" min="0" max="3" placeholder="LL">' +
                    '<input type="number" name="rule_kk[]" min="0" max="3" placeholder="KK">' +
                    '<input type="number" name="rule_em[]" min="0" max="3" placeholder="EM">' +
                    '<input type="number" name="rule_ls[]" min="0" max="3" placeholder="LS">' +
                    '<button type="button" class="btn" data-rule-remove>Remove</button>';
                return row;
            };

            document.querySelectorAll('.panel form').forEach((form) => {
                const list = form.querySelector('[data-rule-list]');
                const addBtn = form.querySelector('[data-rule-add]');
                if (!list || !addBtn) return;

                const bindRemove = () => {
                    list.querySelectorAll('[data-rule-remove]').forEach((btn) => {
                        if (btn.dataset.bound) return;
                        btn.dataset.bound = 'true';
                        btn.addEventListener('click', () => {
                            const rows = list.querySelectorAll('.row-grid');
                            if (rows.length <= 1) {
                                rows[0]?.querySelectorAll('input').forEach((input) => input.value = '');
                                return;
                            }
                            btn.closest('.row-grid')?.remove();
                        });
                    });
                };

                addBtn.addEventListener('click', () => {
                    list.appendChild(makeRuleRow());
                    bindRemove();
                });

                bindRemove();
            });
        })();
    </script>
@endsection
