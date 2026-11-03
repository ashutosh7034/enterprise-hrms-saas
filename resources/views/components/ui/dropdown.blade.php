{{--
  Reusable Enterprise Dropdown Wrapper Component
  Usage:
  <x-ui.dropdown id="myDropdown" label="Options" icon="bi-three-dots">
      <li><a class="dropdown-item" href="#">Item 1</a></li>
  </x-ui.dropdown>
--}}
@props([
    'id' => 'dropdown-' . uniqid(),
    'label' => '',
    'icon' => null,
    'variant' => 'light',
    'size' => 'sm',
    'align' => 'end',
    'class' => '',
    'menuClass' => ''
])

<div class="dropdown {{ $class }}">
    <button class="btn btn-{{ $variant }} btn-{{ $size }} dropdown-toggle d-inline-flex align-items-center gap-1 shadow-xs border"
            type="button"
            id="{{ $id }}"
            data-bs-toggle="dropdown"
            aria-expanded="false"
            style="font-size: 12.5px; font-weight: 500; border-radius: 8px;">
        @if($icon)
            <i class="bi {{ $icon }}"></i>
        @endif
        @if($label)
            <span>{{ $label }}</span>
        @endif
    </button>
    <ul class="dropdown-menu dropdown-menu-{{ $align }} shadow-lg border-0 py-1 {{ $menuClass }}"
        aria-labelledby="{{ $id }}"
        style="min-width: 180px; border-radius: 10px; font-size: 13px;">
        {{ $slot }}
    </ul>
</div>

