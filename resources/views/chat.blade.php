@extends('layouts.app')

@section('content')
  @livewire('chat-widget', ['conversationId' => null])
@endsection
