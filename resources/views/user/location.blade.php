@extends('layouts.user_layout')

@section('title', 'Event Location Map')

@section('content')
    <style>
        .location-header {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 2px solid #1f1f1f;
            flex-wrap: wrap;
        }
        .location-header h2 {
            margin: 0;
            font-size: 26px;
        }
        .location-filter {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .location-filter input,
        .location-filter button {
            border: 1px solid #cfcfcf;
            border-radius: 6px;
            padding: 8px 10px;
            background: #fff;
            color: #1f1f1f;
            font-size: 14px;
        }
        .maps-wrap {
            margin-top: 16px;
            display: grid;
            gap: 16px;
        }
        .map-card {
            border: 1px solid #d8d8d8;
            border-radius: 10px;
            background: #fff;
            padding: 14px;
        }
        .map-card h3 {
            margin: 0 0 10px;
            font-size: 20px;
        }
        .map-canvas {
            position: relative;
            border: 1px solid #d0d0d0;
            border-radius: 8px;
            overflow: hidden;
            background: #f7f7f7;
        }
        .map-canvas img {
            display: block;
            width: 100%;
            height: auto;
        }
        .marker {
            position: absolute;
            width: 18px;
            height: 18px;
            transform: translate(-50%, -100%);
        }
        .marker::before {
            content: "";
            position: absolute;
            inset: 0;
            border-radius: 50% 50% 50% 0;
            transform: rotate(-45deg);
            background: #64748b;
        }
        .marker.has-event::before {
            background: #dc2626;
        }
        .marker-label {
            position: absolute;
            top: -26px;
            left: 50%;
            transform: translateX(-50%);
            white-space: nowrap;
            background: rgba(15, 23, 42, 0.86);
            color: #fff;
            border-radius: 6px;
            padding: 2px 6px;
            font-size: 12px;
        }
        .map-empty {
            margin-top: 10px;
            font-size: 14px;
            color: #666;
        }
        .point-events {
            margin-top: 10px;
            border-top: 1px dashed #d0d0d0;
            padding-top: 8px;
            display: grid;
            gap: 8px;
        }
        .point-events h4 {
            margin: 0;
            font-size: 14px;
        }
        .point-events ul {
            margin: 0;
            padding-left: 18px;
            color: #444;
            font-size: 13px;
        }
        .point-events li {
            margin-bottom: 10px;
            line-height: 1.45;
        }
    </style>

    <div class="location-header">
        <h2>Event Location Map</h2>
        <form class="location-filter" method="GET" action="{{ route('user.location') }}">
            <label for="date">From date:</label>
            <input id="date" type="date" name="date" value="{{ $selectedDate }}">
            <button type="submit">Apply</button>
        </form>
    </div>

    <div class="maps-wrap">
        @forelse ($maps as $map)
            @php
                $mapHasEvents = $map->points->contains(fn ($point) => !empty($pointEvents[$point->id] ?? []));
            @endphp
            <article class="map-card">
                <h3>{{ $map->name }}</h3>
                <div class="map-canvas">
                    <img src="{{ asset('storage/' . $map->image_path) }}" alt="{{ $map->name }} map">
                    @foreach ($map->points as $point)
                        @php
                            $events = $pointEvents[$point->id] ?? [];
                        @endphp
                        <div class="marker {{ count($events) > 0 ? 'has-event' : '' }}" style="left: {{ $point->x_percent }}%; top: {{ $point->y_percent }}%;" title="{{ $point->name }}">
                            <span class="marker-label">{{ $point->name }}</span>
                        </div>
                    @endforeach
                </div>

                @if (! $mapHasEvents)
                    <div class="map-empty">No event sub-events on {{ $selectedDate }} for this map.</div>
                @endif

                @if ($mapHasEvents)
                    <div class="point-events">
                        @foreach ($map->points as $point)
                            @php
                                $events = $pointEvents[$point->id] ?? [];
                            @endphp
                            @if (count($events) > 0)
                                <div>
                                    <h4>{{ $point->name }}</h4>
                                    <ul>
                                        @foreach ($events as $item)
                                            <li>
                                                Event: {{ $item['event_name'] }} - {{ $item['sub_event_title'] }}<br>
                                                Venue: {{ $point->name }}<br>
                                                Date: {{ $item['event_date'] }}<br>
                                                Time: {{ $item['start_time'] }} - {{ $item['end_time'] }}
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
            </article>
        @empty
            <article class="map-card">
                <div class="map-empty">No campus maps available yet.</div>
            </article>
        @endforelse
    </div>
@endsection

