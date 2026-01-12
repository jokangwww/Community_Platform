@extends('layouts.user_layout')

@section('title', $section . ' - Events')

@section('content')
    <div class="tabs">
        <div class="tab">{{ $section }}</div>
    </div>
    <div class="main-card">
        {{ $section }} page coming soon.
    </div>
@endsection
