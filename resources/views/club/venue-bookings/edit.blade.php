@extends('layouts.club')

@section('title', 'Edit Venue Booking')

@section('content')
    <div style="padding:12px 0;border-bottom:2px solid #1f1f1f;">
        <h2 style="margin:0;font-size:24px;">Edit Venue Booking</h2>
        <div style="margin-top:6px;font-size:14px;color:#555;">
            Editing this booking may reset it to pending approval if it was already approved.
        </div>
    </div>

    @include('club.venue-bookings._form', ['venues' => $venues, 'booking' => $booking])
@endsection

