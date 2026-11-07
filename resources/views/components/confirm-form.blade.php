@props(['action', 'method' => 'POST', 'confirm' => 'Are you sure?', 'class' => 'd-inline'])
<form method="POST" action="{{ $action }}" class="{{ $class }}" onsubmit="return confirm(@js($confirm));">
    @csrf
    @if(strtoupper($method) !== 'POST')
        @method($method)
    @endif
    {{ $slot }}
</form>
