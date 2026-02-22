@extends('layouts.admin_layout')

@section('title', 'Location Management')

@section('content')
    <style>
        .location-page {
            padding: 20px 0;
        }
        .location-page h1 {
            margin: 0 0 16px;
            font-size: 28px;
        }
        .status-message {
            margin-bottom: 16px;
            padding: 12px;
            border-radius: 8px;
            background: #e8f5e9;
            color: #1b5e20;
            border: 1px solid #b9e3bd;
        }
        .error-list {
            margin-bottom: 16px;
            padding: 12px 16px;
            border-radius: 8px;
            background: #ffebee;
            color: #b71c1c;
            border: 1px solid #ffcdd2;
        }
        .card {
            background: #fff;
            border: 1px solid #d8d8d8;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 20px;
        }
        .form-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            align-items: end;
        }
        .location-point-form {
            margin-top: 12px;
            grid-template-columns: minmax(180px, 1fr) minmax(220px, 1fr) minmax(220px, 1fr) auto;
            align-items: start;
        }
        .location-point-submit {
            align-self: end;
        }
        @media (max-width: 1100px) {
            .location-point-form {
                grid-template-columns: 1fr;
            }
        }
        .field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .field label {
            font-size: 14px;
            font-weight: 600;
            color: #24487a;
        }
        .field input,
        .field textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
        }
        .field textarea {
            min-height: 80px;
            resize: vertical;
        }
        .auto-grow-textarea {
            min-height: 42px !important;
            height: 42px;
            resize: none !important;
            overflow: hidden;
        }
        .btn {
            border: 0;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 14px;
            cursor: pointer;
        }
        .btn-primary {
            background: #2563eb;
            color: #fff;
        }
        .btn-danger {
            background: #dc2626;
            color: #fff;
        }
        .maps-grid {
            display: grid;
            gap: 20px;
        }
        .map-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }
        .map-header h2 {
            margin: 0;
            font-size: 22px;
        }
        .map-canvas {
            position: relative;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            overflow: hidden;
            cursor: crosshair;
            background: #f8fafc;
        }
        .map-canvas img {
            display: block;
            width: 100%;
            height: auto;
            user-select: none;
            pointer-events: none;
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
            background: #e11d48;
            transform: rotate(-45deg);
        }
        .marker span {
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
            font-weight: 600;
        }
        .marker-preview {
        }
        .marker-preview::before {
            background: #0ea5e9;
        }
        .points-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
            font-size: 14px;
        }
        .points-table th,
        .points-table td {
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
            padding: 8px 6px;
            vertical-align: top;
        }
        .points-table th {
            color: #334155;
            font-weight: 700;
        }
    </style>

    <div class="location-page">
        <h1>Location Management</h1>

        @if (session('status'))
            <div class="status-message">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="error-list">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="card">
            <h2 style="margin-top:0;">Upload New Map</h2>
            <form method="POST" action="{{ route('admin.locations.maps.store') }}" enctype="multipart/form-data" class="form-grid">
                @csrf
                <div class="field">
                    <label for="map_name">Map Name</label>
                    <input id="map_name" type="text" name="name" required placeholder="Example: TAR UMT Campus Ground Floor">
                </div>
                <div class="field">
                    <label for="map_image">Map Image</label>
                    <input id="map_image" type="file" name="map_image" accept="image/*" required>
                </div>
                <div>
                    <button type="submit" class="btn btn-primary">Upload Map</button>
                </div>
            </form>
        </section>

        <section class="maps-grid">
            @forelse ($maps as $map)
                <article class="card">
                    <div class="map-header">
                        <h2>{{ $map->name }}</h2>
                        <form method="POST" action="{{ route('admin.locations.maps.destroy', $map) }}" onsubmit="return confirm('Delete this map and all points?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">Delete Map</button>
                        </form>
                    </div>

                    <div class="map-canvas" data-map-canvas data-map-id="{{ $map->id }}">
                        <img src="{{ asset('storage/' . $map->image_path) }}" alt="{{ $map->name }} map image">

                        @foreach ($map->points as $point)
                            <div class="marker" style="left: {{ $point->x_percent }}%; top: {{ $point->y_percent }}%;">
                                <span>{{ $point->name }}</span>
                            </div>
                        @endforeach

                        <div class="marker marker-preview" data-preview-marker="{{ $map->id }}" style="display:none;">
                            <span>New point</span>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.locations.points.store', $map) }}" class="form-grid location-point-form">
                        @csrf
                        <input type="hidden" name="x_percent" data-x-input="{{ $map->id }}" required>
                        <input type="hidden" name="y_percent" data-y-input="{{ $map->id }}" required>

                        <div class="field">
                            <label>Location Name</label>
                            <input type="text" name="name" required placeholder="Example: Main Hall">
                        </div>
                        <div class="field">
                            <label>Description (Optional)</label>
                            <textarea name="description" class="auto-grow-textarea" rows="1" placeholder="Extra details"></textarea>
                        </div>
                        <div class="field">
                            <label>Selected Coordinates</label>
                            <input type="text" data-coordinate-display="{{ $map->id }}" value="Click on the map to place point" readonly>
                        </div>
                        <div class="location-point-submit">
                            <button type="submit" class="btn btn-primary">Add Location Point</button>
                        </div>
                    </form>

                    @if ($map->points->isNotEmpty())
                        <table class="points-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>X</th>
                                    <th>Y</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($map->points as $point)
                                    <tr>
                                        <td>{{ $point->name }}</td>
                                        <td>{{ $point->description ?: '-' }}</td>
                                        <td>{{ number_format((float) $point->x_percent, 2) }}%</td>
                                        <td>{{ number_format((float) $point->y_percent, 2) }}%</td>
                                        <td>
                                            <form method="POST" action="{{ route('admin.locations.points.destroy', [$map, $point]) }}" onsubmit="return confirm('Delete this location point?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </article>
            @empty
                <div class="card">No maps uploaded yet.</div>
            @endforelse
        </section>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const autoGrow = (textarea) => {
                textarea.style.height = 'auto';
                textarea.style.height = `${textarea.scrollHeight}px`;
            };

            document.querySelectorAll('.auto-grow-textarea').forEach((textarea) => {
                autoGrow(textarea);
                textarea.addEventListener('input', () => autoGrow(textarea));
            });

            document.querySelectorAll('[data-map-canvas]').forEach((canvas) => {
                const mapId = canvas.getAttribute('data-map-id');
                const xInput = document.querySelector(`[data-x-input="${mapId}"]`);
                const yInput = document.querySelector(`[data-y-input="${mapId}"]`);
                const preview = document.querySelector(`[data-preview-marker="${mapId}"]`);
                const display = document.querySelector(`[data-coordinate-display="${mapId}"]`);

                if (!xInput || !yInput || !preview || !display) {
                    return;
                }

                canvas.addEventListener('click', (event) => {
                    const rect = canvas.getBoundingClientRect();
                    const x = ((event.clientX - rect.left) / rect.width) * 100;
                    const y = ((event.clientY - rect.top) / rect.height) * 100;

                    const clampedX = Math.min(100, Math.max(0, x)).toFixed(2);
                    const clampedY = Math.min(100, Math.max(0, y)).toFixed(2);

                    xInput.value = clampedX;
                    yInput.value = clampedY;
                    preview.style.display = 'block';
                    preview.style.left = `${clampedX}%`;
                    preview.style.top = `${clampedY}%`;
                    display.value = `X: ${clampedX}%, Y: ${clampedY}%`;
                });
            });
        });
    </script>
@endsection
