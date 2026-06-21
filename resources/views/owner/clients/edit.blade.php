@extends('layouts.app')
@section('title', 'Edit Client')
@section('content')
<div class="panel">
    <div class="panel-header"><h2>Edit Client</h2></div>
    <form method="POST" action="{{ route('owner.clients.update', $client) }}">
        @include('owner.clients._form')
    </form>
</div>
@endsection
