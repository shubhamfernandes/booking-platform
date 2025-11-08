@props(['label', 'id', 'rows' => 3])

<div>
  <label for="{{ $id }}" class="block text-sm font-medium text-gray-700">
    {{ $label }}
  </label>

  <textarea
    id="{{ $id }}"
    name="{{ $id }}"
    rows="{{ $rows }}"
    {{ $attributes->merge(['class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500']) }}
  ></textarea>
</div>
