@extends('layouts.app')
@section('title', 'Create Invoice')
@section('content')
<form method="POST" action="{{ route('invoices.store') }}">@csrf @include('invoices._form')</form>
@endsection
