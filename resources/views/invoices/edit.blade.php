@extends('layouts.app')
@section('title', 'Edit Invoice')
@section('content')
<form method="POST" action="{{ route('invoices.update', $invoice) }}">@csrf @method('PUT') @include('invoices._form')</form>
@endsection
