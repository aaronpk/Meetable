@extends('errors::minimal')

@section('title', __('errors.database_error'))
@section('code', 'DB')
@section('message', __('errors.database_error_message'))
