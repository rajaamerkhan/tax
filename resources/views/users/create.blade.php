@extends('layouts.app')
@section('title', 'New User')
@section('content')
<div class="panel">
    <div class="panel-header"><h2>New User</h2></div>
    <form method="POST" action="{{ route('users.store') }}">
        @include('users._form')
    </form>
</div>
@endsection
