@props(['label', 'id', 'type' => 'text', 'required' => false])

<div>
  <label for="{{ $id }}" class="block text-sm font-medium text-gray-700">
    {{ $label }}
  </label>

  <input
    id="{{ $id }}"
    name="{{ $id }}"
    type="{{ $type }}"
    {{ $required ? 'required' : '' }}
    {{ $attributes->merge(['class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500']) }}
  >
</div>
