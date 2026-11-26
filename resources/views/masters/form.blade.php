@extends('layouts.app')

@section('title', $title.' | GudangIn')

@section('content')
    <section class="page-heading">
        <div>
            <p class="eyebrow">Data operasional</p>
            <h1>{{ $title }}</h1>
            <p class="lead">Lengkapi informasi berikut, lalu simpan perubahan.</p>
        </div>
    </section>
    <section class="form-panel master-form-panel">
        @if ($errors->any())
            <div class="notice notice-error">{{ $errors->first() }}</div>
        @endif
        <form method="POST" action="{{ $formAction }}" class="stack-form">
            @csrf
            @if ($method)
                @method($method)
            @endif
            @foreach ($fields as $field)
                <label for="{{ $field['name'] }}">{{ $field['label'] }}</label>
                @isset($field['help'])
                    <p class="field-help">{{ $field['help'] }}</p>
                @endisset
                @if ($field['type'] === 'textarea')
                    <textarea
                        id="{{ $field['name'] }}"
                        name="{{ $field['name'] }}"
                        maxlength="{{ $field['maxlength'] ?? 255 }}"
                        rows="4"
                        @required($field['required'])
                    >{{ old($field['name'], $values[$field['name']] ?? '') }}</textarea>
                @elseif ($field['type'] === 'select')
                    @php
                        $currentValue = old($field['name'], $values[$field['name']] ?? '');
                        $customValue = $field['customValue'] ?? '__custom__';
                        $customSelected = ($field['customOption'] ?? false)
                            && ($currentValue === $customValue || ! array_key_exists($currentValue, $field['options']));
                        $selectedValue = $customSelected ? $customValue : $currentValue;
                    @endphp
                    <select
                        id="{{ $field['name'] }}"
                        name="{{ $field['name'] }}"
                        @if ($field['customOption'] ?? false)
                            data-custom-select
                            data-custom-value="{{ $customValue }}"
                            data-custom-target="{{ $field['name'] }}_custom"
                        @endif
                        @required($field['required'])
                    >
                        @foreach ($field['options'] as $value => $label)
                            <option value="{{ $value }}" @selected((string) $selectedValue === (string) $value)>{{ $label }}</option>
                        @endforeach
                        @if ($field['customOption'] ?? false)
                            <option value="{{ $customValue }}" @selected($customSelected)>Lainnya</option>
                        @endif
                    </select>
                    @if ($field['customOption'] ?? false)
                        <div data-custom-field @if (! $customSelected) hidden @endif>
                            <label for="{{ $field['name'] }}_custom">Satuan lainnya</label>
                            <input
                                id="{{ $field['name'] }}_custom"
                                name="{{ $field['name'] }}_custom"
                                type="text"
                                maxlength="20"
                                value="{{ old($field['name'].'_custom', $currentValue === $customValue ? '' : ($customSelected ? $currentValue : '')) }}"
                                @required($customSelected)
                            >
                        </div>
                    @endif
                @else
                    <input
                        id="{{ $field['name'] }}"
                        name="{{ $field['name'] }}"
                        type="{{ $field['type'] }}"
                        value="{{ old($field['name'], $values[$field['name']] ?? '') }}"
                        @isset($field['maxlength']) maxlength="{{ $field['maxlength'] }}" @endisset
                        @isset($field['min']) min="{{ $field['min'] }}" @endisset
                        @isset($field['max']) max="{{ $field['max'] }}" @endisset
                        @isset($field['step']) step="{{ $field['step'] }}" @endisset
                        @isset($field['placeholder']) placeholder="{{ $field['placeholder'] }}" @endisset
                        @required($field['required'])
                    >
                @endif
            @endforeach
            <div class="form-actions">
                <a class="button button-secondary" href="{{ $cancelRoute }}">Batal</a>
                <button class="button button-primary" type="submit">{{ $submitLabel }}</button>
            </div>
        </form>
    </section>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-custom-select]').forEach((select) => {
            const customField = document.getElementById(select.dataset.customTarget);
            const customContainer = customField.closest('[data-custom-field]');
            const updateCustomField = () => {
                const isCustom = select.value === select.dataset.customValue;
                customContainer.hidden = !isCustom;
                customField.required = isCustom;
            };

            select.addEventListener('change', updateCustomField);
            updateCustomField();
        });
    </script>
@endpush
