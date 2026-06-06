@extends('layouts.app')
@section('title', 'Create Customer')
@section('content')
<div class="panel"><div class="panel-header"><h2>Create Customer</h2></div><form method="POST" action="{{ route('customers.store') }}">@csrf @include('customers._form')<div class="mt-3"><button class="btn btn-primary">Create Customer</button></div></form></div>
@endsection
