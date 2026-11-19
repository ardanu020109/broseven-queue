@if (session('success'))
    <x-alert type="success">{{ session('success') }}</x-alert>
@endif

@if (session('status'))
    <x-alert type="info">{{ session('status') }}</x-alert>
@endif

@if (session('error'))
    <x-alert type="danger">{{ session('error') }}</x-alert>
@endif

@if (session('warning'))
    <x-alert type="warning">
        @foreach ((array) session('warning') as $message)
            <div>{{ $message }}</div>
        @endforeach
    </x-alert>
@endif
