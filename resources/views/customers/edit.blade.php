@extends('layouts.app')
@section('title', 'Edit Customer')
@section('content')
<div class="panel"><div class="panel-header"><h2>Edit Customer</h2></div><form method="POST" action="{{ route('customers.update', $customer) }}">@csrf @method('PUT') @include('customers._form')<div class="mt-3"><button class="btn btn-primary">Save Changes</button></div></form></div>
@endsection
