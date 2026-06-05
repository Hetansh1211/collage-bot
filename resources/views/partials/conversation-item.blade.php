@php
    $isActive = optional($activeConversation)->id === $conversation->id;
    $preview = optional($conversation->latestChat)->user_message ?: 'No messages yet';
@endphp

<div
    class="conversation-item rounded-2xl border p-3 text-sm transition {{ $isActive ? 'border-blue-300 bg-blue-50 shadow-sm' : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50' }}"
    data-conversation-id="{{ $conversation->id }}"
    data-title="{{ strtolower($conversation->title) }}"
    data-preview="{{ strtolower($preview) }}"
>
    <div class="flex items-start justify-between gap-3">
        <a href="{{ route('chat.show', $conversation->id) }}" class="block min-w-0 flex-1">
            <div class="flex items-center gap-2">
                @if($conversation->is_pinned)
                    <span class="h-2 w-2 rounded-full bg-blue-600"></span>
                @endif
                <p class="conversation-title truncate font-bold text-slate-800">
                    {{ Str::limit($conversation->title, 45) }}
                </p>
            </div>
            <p class="mt-1 truncate text-xs leading-5 text-slate-500">
                {{ Str::limit($preview, 58) }}
            </p>
            <p class="mt-2 text-[11px] font-medium text-slate-400">
                {{ optional($conversation->latestChat)->created_at ? $conversation->latestChat->created_at->diffForHumans() : $conversation->created_at->diffForHumans() }}
            </p>
        </a>

        <div class="flex shrink-0 items-center gap-1">
            <button onclick="startInlineRename({{ $conversation->id }}, @js($conversation->title))" class="rounded-lg border border-slate-200 bg-white px-2 py-1 text-[11px] font-semibold text-slate-600 hover:bg-slate-50">
                Edit
            </button>
            <button onclick="togglePinConversation({{ $conversation->id }})" class="rounded-lg border border-amber-200 bg-amber-50 px-2 py-1 text-[11px] font-semibold text-amber-700 hover:bg-amber-100">
                {{ $conversation->is_pinned ? 'Unpin' : 'Pin' }}
            </button>
            <button onclick="openDeleteModal({{ $conversation->id }}, @js($conversation->title))" class="rounded-lg border border-red-200 bg-red-50 px-2 py-1 text-[11px] font-semibold text-red-700 hover:bg-red-100">
                Del
            </button>
        </div>
    </div>

    <div id="renameRow-{{ $conversation->id }}" class="hidden mt-3 flex gap-2">
        <input id="renameInput-{{ $conversation->id }}" type="text" class="min-w-0 flex-1 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:outline-none focus:ring-4 focus:ring-blue-100">
        <button onclick="submitInlineRename({{ $conversation->id }})" class="rounded-xl bg-slate-950 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-800">Save</button>
        <button onclick="cancelInlineRename({{ $conversation->id }})" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
    </div>
</div>
