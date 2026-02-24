@extends('layouts.club')

@section('title', 'Create Venue Booking')

@section('content')
    <div style="padding:12px 0;border-bottom:2px solid #1f1f1f;">
        <h2 style="margin:0;font-size:24px;">Create Venue Booking</h2>
    </div>

    @include('club.venue-bookings._form', ['venues' => $venues])
@endsection

