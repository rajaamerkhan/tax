@extends('layouts.app')
@section('title', 'New Client')
@section('content')
<div class="panel">
    <div class="panel-header"><h2>New Client</h2></div>
    <form method="POST" action="{{ route('owner.clients.store') }}">
        @include('owner.clients._form')
    </form>
</div>
@endsection
