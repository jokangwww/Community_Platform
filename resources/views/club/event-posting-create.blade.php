@extends('layouts.club')

@section('title', 'New Posting')

@section('content')
    <style>
        .posting-page {
            --bg-soft: #f4f8ff;
            --text-main: #172236;
            --text-muted: #596579;
            --border: #c9d3e2;
            --primary: #1f5ae0;
            --primary-dark: #1343b6;
            margin-top: 18px;
            color: var(--text-main);
        }
        .page-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 18px;
        }
        .page-title {
            margin: 0;
            font-size: 30px;
            line-height: 1.2;
        }
        .page-subtitle {
            margin: 6px 0 0;
            color: var(--text-muted);
            font-size: 15px;
        }
        .page-back {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 9px 14px;
            border: 1px solid var(--border);
            border-radius: 10px;
            text-decoration: none;
            color: var(--text-main);
            background: #fff;
            font-weight: 600;
            white-space: nowrap;
        }
        .page-back:hover {
            background: #f8fbff;
        }
        .posting-layout {
            max-width: 920px;
        }
        .posting-form {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 14px;
            box-shadow: 0 10px 24px rgba(18, 41, 87, 0.08);
            overflow: hidden;
        }
        .form-section {
            padding: 18px 20px;
            border-bottom: 1px solid #e6edf7;
        }
        .form-section:last-child {
            border-bottom: 0;
        }
        .section-title {
            margin: 0 0 14px;
            font-size: 18px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }
        .form-row {
            margin: 0;
        }
        .form-row.full {
            grid-column: 1 / -1;
        }
        .form-row label {
            display: block;
            font-weight: 700;
            margin-bottom: 7px;
            font-size: 14px;
        }
        .required-mark {
            color: #b22036;
            font-weight: 700;
        }
        .form-row input[type="datetime-local"],
        .form-row select,
        .form-row textarea,
        .form-row input[type="file"] {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #b8c5d7;
            border-radius: 10px;
            font-size: 15px;
            color: var(--text-main);
            background: #fff;
        }
        .form-row input[type="file"] {
            padding: 9px 10px;
            background: #f9fbff;
            border-style: dashed;
        }
        .form-row input:focus,
        .form-row select:focus,
        .form-row textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(31, 90, 224, 0.16);
        }
        .form-row textarea {
            min-height: 220px;
            resize: vertical;
            line-height: 1.45;
        }
        .form-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 16px;
            border-radius: 10px;
            border: 1px solid #94a6c2;
            background: #fff;
            text-decoration: none;
            color: var(--text-main) !important;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-primary {
            border-color: var(--primary);
            background: var(--primary);
            color: #fff;
        }
        .btn-primary:hover,
        .btn-primary:focus-visible {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
            color: #fff;
        }
        .btn:not(.btn-primary):hover {
            background: #f8fbff;
            color: var(--text-main) !important;
        }
        .btn:not(.btn-primary):focus-visible {
            background: #f8fbff;
            color: var(--text-main) !important;
            outline: 2px solid #9ab7e6;
            outline-offset: 2px;
        }
        .help-text {
            color: var(--text-muted);
            font-size: 13px;
            margin-top: 6px;
        }
        .error {
            color: #b00020;
            font-size: 13px;
            margin-top: 6px;
        }
        .poster-previews {
            margin-top: 12px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(96px, 1fr));
            gap: 10px;
        }
        .poster-preview {
            width: 100%;
            aspect-ratio: 1 / 1;
            border: 1px solid #cfdbeb;
            border-radius: 10px;
            background: #f7faff;
            overflow: hidden;
        }
        .poster-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        @media (max-width: 980px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            .page-title {
                font-size: 26px;
            }
        }
        @media (max-width: 640px) {
            .page-header {
                flex-direction: column;
            }
            .page-back {
                width: 100%;
            }
            .form-actions {
                justify-content: stretch;
                flex-direction: column-reverse;
            }
            .form-actions .btn {
                width: 100%;
            }
        }
    </style>

    <div class="posting-page">
        <div class="page-header">
            <div>
                <h2 class="page-title">Create New Posting</h2>
                <p class="page-subtitle">Publish a clear update with posters, status, and timing details.</p>
            </div>
            <a class="page-back" href="{{ route('club.event-posting.mine') }}">Back to My Postings</a>
        </div>

        <div class="posting-layout">
            <form class="posting-form" method="POST" action="{{ route('club.event-posting.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="form-section">
                    <h3 class="section-title">Posting Details</h3>
                    <div class="form-grid">
                        <div class="form-row">
                            <label for="event_id">Event <span class="required-mark">*</span></label>
                            <select id="event_id" name="event_id" required>
                                <option value="">Select an event</option>
                                @foreach ($events as $event)
                                    <option value="{{ $event->id }}" @selected(old('event_id') == $event->id)>
                                        {{ $event->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('event_id')
                                <div class="error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-row">
                            <label for="status">Registration Status <span class="required-mark">*</span></label>
                            <select id="status" name="status" required>
                                <option value="open" @selected(old('status', 'open') === 'open')>Open</option>
                                <option value="closed" @selected(old('status') === 'closed')>Closed</option>
                                <option value="none" @selected(old('status') === 'none')>None</option>
                            </select>
                            @error('status')
                                <div class="error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-row">
                            <label for="outdated_at">Outdated At</label>
                            <input id="outdated_at" name="outdated_at" type="datetime-local" value="{{ old('outdated_at') }}">
                            <div class="help-text">Optional. The posting is considered outdated after this date/time.</div>
                            @error('outdated_at')
                                <div class="error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-row full">
                            <label for="posters">Poster Images</label>
                            <input id="posters" name="posters[]" type="file" accept="image/*" multiple>
                            <div class="help-text">Upload one or more poster images (JPG/PNG).</div>
                            @error('posters')
                                <div class="error">{{ $message }}</div>
                            @enderror
                            @error('posters.*')
                                <div class="error">{{ $message }}</div>
                            @enderror
                            <div class="poster-previews" id="posterPreviews" aria-live="polite"></div>
                        </div>

                        <div class="form-row full">
                            <label for="description">Description <span class="required-mark">*</span></label>
                            <textarea id="description" name="description" required>{{ old('description') }}</textarea>
                            @error('description')
                                <div class="error">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-actions">
                        <a class="btn btn-primary" href="{{ route('club.event-posting.mine') }}">Cancel</a>
                        <button class="btn btn-primary" type="submit">Create Posting</button>
                    </div>
                </div>
            </form>

        </div>
    </div>

    <script>
        (function () {
            const input = document.getElementById('posters');
            const previews = document.getElementById('posterPreviews');

            if (!input || !previews) {
                return;
            }

            input.addEventListener('change', function () {
                previews.innerHTML = '';
                const files = Array.from(input.files || []);

                files.forEach(function (file) {
                    if (!file.type.startsWith('image/')) {
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = function (event) {
                        const wrapper = document.createElement('div');
                        wrapper.className = 'poster-preview';

                        const img = document.createElement('img');
                        img.src = event.target.result;
                        img.alt = file.name;

                        wrapper.appendChild(img);
                        previews.appendChild(wrapper);
                    };
                    reader.readAsDataURL(file);
                });
            });
        })();
    </script>
@endsection
