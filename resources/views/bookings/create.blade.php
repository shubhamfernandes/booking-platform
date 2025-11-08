<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Create Booking</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 p-10">

  <div class="max-w-2xl mx-auto bg-white p-6 rounded-2xl shadow">
    <h1 class="text-2xl font-semibold mb-4 text-gray-800">Create a Booking</h1>

    @if(session('success'))
      <div class="mb-4 text-green-600 font-medium">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('bookings.store') }}" id="bookingForm" class="space-y-4">
      @csrf

      <x-input label="Title" id="title" required />
      <x-textarea label="Description" id="description" />

      <div class="grid grid-cols-2 gap-4">
        <x-input label="Start Time" id="start_time" type="datetime-local" required />
        <x-input label="End Time" id="end_time" type="datetime-local" required />
      </div>

      <div class="grid grid-cols-2 gap-4">
        <x-select label="User" id="user_id" required :options="$users" />
        <x-select label="Client" id="client_id" required :options="$clients" />
      </div>

      <x-button id="submitBtn">Create Booking</x-button>
    </form>

    <div id="messageBox" class="mt-4 text-sm font-medium"></div>
  </div>

</body>
</html>
