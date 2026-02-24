@extends('layouts.admin_layout')

@section('title', 'Venue Management')

@section('content')
    <style>
        .v-head { padding: 12px 0; border-bottom: 2px solid #1f1f1f; }
        .v-head h2 { margin: 0; font-size: 24px; }
        .v-msg { margin-top: 12px; padding: 10px 12px; border: 1px solid #cfcfcf; border-radius: 8px; background: #f7f7f7; }
        .v-grid { margin-top: 16px; display: grid; grid-template-columns: 360px 1fr; gap: 16px; align-items: start; }
        .v-panel { border: 1px solid #d7d7d7; border-radius: 10px; background: #fff; padding: 14px; }
        .v-panel h3 { margin: 0 0 10px; font-size: 18px; }
        .v-form, .v-filters { display: grid; gap: 8px; }
        .v-form input, .v-form textarea, .v-form select, .v-form button,
        .v-filters input, .v-filters select, .v-filters button, .v-filters a {
            width: 100%; border: 1px solid #c7c7c7; border-radius: 6px; padding: 8px 10px; font-size: 14px; background: #fff;
            text-decoration: none; color: #1f1f1f;
        }
        .v-form textarea { min-height: 70px; resize: vertical; }
        .v-checkbox { display: flex; align-items: center; gap: 8px; font-size: 14px; }
        .v-checkbox input { width: auto; }
        .v-form button, .v-filters button { cursor: pointer; border-color: #1f1f1f; }
        .v-filter-row { display: grid; grid-template-columns: 1fr 170px auto auto; gap: 8px; align-items: center; }
        .v-list { margin-top: 12px; display: grid; gap: 12px; }
        .v-card { border: 1px solid #d8d8d8; border-radius: 10px; padding: 12px; background: #fcfcfc; }
        .v-card h4 { margin: 0 0 8px; font-size: 17px; }
        .v-meta { display: grid; gap: 4px; font-size: 14px; color: #333; }
        .v-actions { margin-top: 10px; }
        .v-actions form { display: grid; gap: 8px; }
        .v-actions .row { display: grid; grid-template-columns: 1fr 1fr auto; gap: 8px; }
        .v-actions input, .v-actions textarea, .v-actions select, .v-actions button {
            border: 1px solid #c7c7c7; border-radius: 6px; padding: 8px 10px; font-size: 14px; background: #fff;
        }
        .v-actions textarea { grid-column: 1 / -1; min-height: 60px; resize: vertical; }
        .v-actions button { cursor: pointer; }
        .v-actions .save { border-color: #1f7a3f; color: #1f7a3f; }
        .v-actions .delete { border-color: #8f1717; color: #8f1717; }
        .v-empty { margin-top: 12px; border: 1px dashed #c7c7c7; border-radius: 8px; padding: 14px; color: #555; }
        @media (max-width: 1000px) {
            .v-grid { grid-template-columns: 1fr; }
            .v-filter-row { grid-template-columns: 1fr; }
            .v-actions .row { grid-template-columns: 1fr; }
        }
    </style>

    <div class="v-head"><h2>Venue Management</h2></div>

    @if (session('status'))
        <div class="v-msg">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="v-msg">{{ $errors->first() }}</div>
    @endif

    <div class="v-grid">
        <section class="v-panel">
            <h3>Create Venue</h3>
            <form method="POST" action="{{ route('admin.venues.store') }}" class="v-form">
                @csrf
                <input type="text" name="name" placeholder="Venue name" value="{{ old('name') }}" required>
                <input type="text" name="location" placeholder="Location (building / floor / block)" value="{{ old('location') }}" required>
                <textarea name="notes" placeholder="Notes (optional)">{{ old('notes') }}</textarea>
                <label class="v-checkbox">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', '1') == '1')>
                    Venue is active / available for booking
                </label>
                <button type="submit">Add Venue</button>
            </form>
        </section>

        <section class="v-panel">
            <form method="GET" action="{{ route('admin.venues.index') }}" class="v-filters">
                <div class="v-filter-row">
                    <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search name or location">
                    <select name="operational">
                        <option value="" @selected(($filters['operational'] ?? '') === '')>All operational</option>
                        <option value="active" @selected(($filters['operational'] ?? '') === 'active')>Active</option>
                        <option value="inactive" @selected(($filters['operational'] ?? '') === 'inactive')>Inactive</option>
                    </select>
                    <button type="submit">Apply</button>
                    <a href="{{ route('admin.venues.index') }}">Reset</a>
                </div>
            </form>

            @if ($venues->isEmpty())
                <div class="v-empty">No venue records found.</div>
            @else
                <div class="v-list">
                    @foreach ($venues as $venue)
                        @php
                            $bookedNow = (int) ($currentApprovedCounts[$venue->id] ?? 0) > 0;
                            $linkedCount = (int) ($activeUpcomingCounts[$venue->id] ?? 0);
                        @endphp
                        <article class="v-card">
                            <h4>{{ $venue->name }}</h4>
                            <div class="v-meta">
                                <div><strong>Location:</strong> {{ $venue->location }}</div>
                                <div><strong>Operational Status:</strong> {{ $venue->is_active ? 'Active' : 'Inactive' }}</div>
                                <div><strong>Availability Now:</strong> {{ $venue->is_active ? ($bookedNow ? 'Occupied' : 'Available') : 'Inactive' }}</div>
                                <div><strong>Active/Upcoming Bookings:</strong> {{ $linkedCount }}</div>
                                <div><strong>Notes:</strong> {{ $venue->notes ?: 'None' }}</div>
                            </div>

                            <div class="v-actions">
                                <form method="POST" action="{{ route('admin.venues.update', $venue) }}">
                                    @csrf
                                    @method('PUT')
                                    <div class="row">
                                        <input type="text" name="name" value="{{ old('name', $venue->name) }}" required>
                                        <input type="text" name="location" value="{{ old('location', $venue->location) }}" required>
                                        <button type="submit" class="save">Update</button>
                                    </div>
                                    <textarea name="notes" placeholder="Notes (optional)">{{ old('notes', $venue->notes) }}</textarea>
                                    <label class="v-checkbox">
                                        <input type="checkbox" name="is_active" value="1" @checked($venue->is_active)>
                                        Venue is active / available for booking
                                    </label>
                                </form>
                                <form method="POST" action="{{ route('admin.venues.destroy', $venue) }}" onsubmit="return confirm('Delete this venue?');" style="margin-top:8px;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="delete">Delete Venue</button>
                                </form>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
@endsection

