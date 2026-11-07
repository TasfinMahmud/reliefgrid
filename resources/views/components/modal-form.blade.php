@props(['id', 'title', 'action', 'method' => 'POST', 'submit' => 'Save', 'submitClass' => 'btn-rg', 'enctype' => null])
<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-labelledby="{{ $id }}Label" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ $action }}" class="modal-content" @if($enctype) enctype="{{ $enctype }}" @endif>
            @csrf
            @if(strtoupper($method) !== 'POST')
                @method($method)
            @endif
            <div class="modal-header">
                <h5 class="modal-title" id="{{ $id }}Label">{{ $title }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                {{ $slot }}
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn {{ $submitClass }}">{{ $submit }}</button>
            </div>
        </form>
    </div>
</div>
