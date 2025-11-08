@props(['label', 'id', 'options' => [], 'required' => false])

<div>
  <label for="{{ $id }}" class="block text-sm font-medium text-gray-700">
    {{ $label }}
  </label>

  <select
    id="{{ $id }}"
    name="{{ $id }}"
    {{ $required ? 'required' : '' }}
    {{ $attributes->merge(['class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500']) }}
  >
    <option value="">Select {{ strtolower($label) }}</option>
    @foreach ($options as $option)
      <option value="{{ $option['id'] ?? $option->id }}">{{ $option['name'] ?? $option->name }}</option>
    @endforeach
  </select>
</div>
