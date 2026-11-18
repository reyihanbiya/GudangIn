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
                @if ($field['type'] === 'textarea')
                    <textarea
                        id="{{ $field['name'] }}"
                        name="{{ $field['name'] }}"
                        maxlength="{{ $field['maxlength'] ?? 255 }}"
                        rows="4"
                        @required($field['required'])
                    >{{ old($field['name'], $values[$field['name']] ?? '') }}</textarea>
                @else
                    <input
                        id="{{ $field['name'] }}"
                        name="{{ $field['name'] }}"
                        type="{{ $field['type'] }}"
                        value="{{ old($field['name'], $values[$field['name']] ?? '') }}"
                        @isset($field['maxlength']) maxlength="{{ $field['maxlength'] }}" @endisset
                        @isset($field['min']) min="{{ $field['min'] }}" @endisset
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
