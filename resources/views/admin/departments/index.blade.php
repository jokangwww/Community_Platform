@extends('layouts.admin_layout')

@section('title', 'Departments')

@section('content')
    <style>
        .department-header {
            padding: 12px 0;
            border-bottom: 2px solid #1f1f1f;
        }
        .department-header h2 {
            margin: 0;
            font-size: 24px;
        }
        .department-status {
            margin-top: 12px;
            padding: 10px 12px;
            border: 1px solid #c2c2c2;
            border-radius: 8px;
            background: #f7f7f7;
            max-width: 900px;
        }
        .department-form {
            margin-top: 14px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            max-width: 900px;
        }
        .department-form input,
        .department-form button {
            border: 1px solid #c2c2c2;
            border-radius: 6px;
            padding: 8px 10px;
            font-size: 14px;
            background: #fff;
            color: #1f1f1f;
        }
        .department-form input {
            min-width: 260px;
            flex: 1 1 320px;
        }
        .department-form button {
            border-color: #1f1f1f;
            cursor: pointer;
        }
        .department-list {
            margin-top: 14px;
            max-width: 900px;
            display: grid;
            gap: 10px;
        }
        .department-item {
            border: 1px solid #d6d6d6;
            border-radius: 8px;
            background: #fff;
            padding: 10px 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }
        .department-item strong {
            font-size: 15px;
        }
        .department-item form {
            margin: 0;
        }
        .department-item button {
            border: 1px solid #8f1717;
            background: #fff;
            color: #8f1717;
            border-radius: 6px;
            padding: 6px 10px;
            cursor: pointer;
            font-size: 13px;
        }
        .department-empty {
            margin-top: 14px;
            max-width: 900px;
            border: 1px dashed #c2c2c2;
            border-radius: 8px;
            padding: 16px;
            color: #4a4a4a;
        }
    </style>

    <div class="department-header">
        <h2>Department Management</h2>
    </div>

    @if (session('status'))
        <div class="department-status">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="department-status" style="border-color:#f5c2c2;background:#ffecec;color:#7f1d1d;">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.departments.store') }}" class="department-form">
        @csrf
        <input
            type="text"
            name="name"
            value="{{ old('name') }}"
            placeholder="e.g. Finance Department"
            maxlength="255"
            required
        >
        <button type="submit">Add Department</button>
    </form>

    @if ($departments->isEmpty())
        <div class="department-empty">No departments yet.</div>
    @else
        <div class="department-list">
            @foreach ($departments as $department)
                <div class="department-item">
                    <strong>{{ $department->name }}</strong>
                    <form method="POST" action="{{ route('admin.departments.destroy', $department) }}" onsubmit="return confirm('Delete this department?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit">Delete</button>
                    </form>
                </div>
            @endforeach
        </div>
    @endif
@endsection

