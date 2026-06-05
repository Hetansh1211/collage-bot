@extends('layouts.app')

@section('content')

<div class="min-h-[calc(100vh-4rem)] bg-slate-100 text-slate-900 flex flex-col">
    
    <!-- Header with Dashboard and Credits -->
    <div class="border-b border-slate-200 bg-white px-4 py-4 shadow-sm sm:px-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="eyebrow">Assistant workspace</p>
                <h1 class="mt-1 text-xl font-extrabold tracking-tight text-slate-950">College AI Chat</h1>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <button onclick="toggleSidebar()" 
                    class="btn-secondary px-4 py-2"
                    id="sidebarToggleBtn"
                    title="Show or hide chats">
                    ☰ Chats
                </button>
                <button onclick="stopSpeaking()" 
                    class="btn-secondary px-4 py-2" 
                    id="muteBtn"
                    title="Stop speaking now">
                    ⏹ Stop Voice
                </button>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-semibold text-slate-600">Credits</span>
                        <span class="text-base font-extrabold text-blue-700" id="creditDisplay">{{ Auth::user()->credit_points }}</span>
                    </div>
                    <p class="text-[11px] font-medium text-slate-500" id="nextRefreshLabel">
                        Refresh: {{ Auth::user()->nextCreditRefreshLabel() }}
                    </p>
                </div>
                <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-slate-600 transition hover:text-slate-950">Dashboard</a>
            </div>
        </div>
        
        <!-- Credits Depleted Warning -->
        @if(Auth::user()->credit_points <= 0)
        <div class="mt-4 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            <p class="font-semibold mb-1">⚠️ Credits Depleted</p>
            <p class="mt-1">Your credits will refresh to <strong>300 points</strong> at <strong>{{ Auth::user()->nextCreditRefreshLabel() }}</strong> (<strong id="refreshTimer"></strong> remaining).</p>
        </div>
        @endif
    </div>

    <!-- Main content with sidebar -->
    <div class="flex flex-1 overflow-hidden relative">
        <!-- Sidebar - Chat History -->
        <div id="chatSidebar" class="w-80 bg-white border-r border-slate-200 flex flex-col overflow-hidden absolute md:relative z-30 inset-y-0 left-0 transform -translate-x-full transition-transform duration-300 ease-in-out shadow-2xl md:shadow-none">
            <!-- New Chat Button -->
            <div class="border-b border-slate-200 bg-slate-50 p-4">
                <div class="flex justify-end mb-2 md:hidden">
                    <button onclick="toggleSidebar(false)" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">
                        Close
                    </button>
                </div>
                <a href="{{ route('chat') }}" class="btn-primary w-full py-2.5">
                    New Chat
                </a>
                <div class="mt-3 relative">
                    <input
                        id="conversationSearch"
                        type="text"
                        placeholder="Search chats..."
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:outline-none focus:ring-4 focus:ring-blue-100 placeholder:text-slate-400"
                    >
                    <span id="searchIndicator" class="hidden absolute right-3 top-2.5 text-xs text-slate-400">Searching...</span>
                </div>
            </div>

            <!-- Chat History List -->
            <div id="conversationList" class="flex-1 overflow-y-auto p-3">
                @php
                    $pinnedConversations = $conversations->where('is_pinned', true);
                    $recentConversations = $conversations->where('is_pinned', false);
                @endphp

                @if($conversations->count() > 0)
                    @if($pinnedConversations->count() > 0)
                        <div class="mb-4">
                            <p class="px-2 mb-2 text-xs font-bold uppercase tracking-wide text-slate-400">Pinned</p>
                            <div class="space-y-2">
                                @foreach($pinnedConversations as $conversation)
                                    @include('partials.conversation-item', ['conversation' => $conversation, 'activeConversation' => $activeConversation])
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if($recentConversations->count() > 0)
                        <div>
                            <p class="px-2 mb-2 text-xs font-bold uppercase tracking-wide text-slate-400">Recent</p>
                            <div class="space-y-2">
                                @foreach($recentConversations as $conversation)
                                    @include('partials.conversation-item', ['conversation' => $conversation, 'activeConversation' => $activeConversation])
                                @endforeach
                            </div>
                        </div>
                    @endif
                @else
                    <div class="h-full flex items-center justify-center text-center px-4">
                        <div>
                            <p class="text-4xl mb-2 font-extrabold text-slate-300">AI</p>
                            <p class="text-sm font-semibold text-slate-700">No chats yet</p>
                            <p class="text-xs text-slate-500 mt-1">Start a new conversation to see it here.</p>
                        </div>
                    </div>
                @endif
            </div>
            <div id="noSearchResults" class="hidden px-4 py-8 text-center text-sm text-slate-500">
                <p>No conversations found.</p>
                <p class="text-xs text-slate-400 mt-1">Try a different keyword.</p>
            </div>
        </div>
        <div id="sidebarBackdrop" onclick="toggleSidebar(false)" class="hidden absolute inset-0 bg-slate-950/40 z-20 md:hidden"></div>

        <!-- Main Chat Area -->
        <div class="flex-1 flex flex-col bg-slate-100">
            <!-- Chat Messages Area (Scrollable) -->
            <div id="chatBox" class="flex-1 overflow-y-auto px-4 py-8 space-y-5 w-full sm:px-6">
        @if($chats->count() > 0)
            @foreach($chats as $chat)
                <!-- User Message -->
                <div class="flex justify-end animate-fade-in">
                    <div class="group max-w-xl rounded-2xl bg-slate-950 p-4 text-white shadow-sm">
                        <p class="text-sm leading-6">{{ $chat->user_message }}</p>
                        @if($chat->user_attachment)
                            <p class="mt-2 flex items-center gap-1 text-xs text-slate-300">
                                File: {{ basename($chat->user_attachment) }}
                            </p>
                        @endif
                        <div class="flex justify-between items-center mt-2">
                            <p class="text-xs text-slate-400">{{ $chat->created_at->format('h:i A') }}</p>
                            <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition">
                                <button onclick="editMessage(this, {{ Illuminate\Support\Js::from($chat->user_message) }})" class="rounded-lg bg-white/10 px-2 py-1 text-xs font-semibold text-white transition hover:bg-white/20">
                                    Edit
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Bot Reply -->
                <div class="flex justify-start animate-fade-in">
                    <div class="max-w-3xl rounded-2xl border border-slate-200 bg-white p-5 text-slate-800 shadow-sm">
                        <div class="text-sm leading-relaxed reply-content" data-reply="{{ e($chat->bot_reply) }}"></div>
                        <div class="mt-4 flex items-center justify-between gap-3 border-t border-slate-100 pt-3 text-xs text-slate-500">
                            <div>
                                {{ $chat->created_at->format('h:i A') }}
                            </div>
                            <button onclick="speakText({{ Illuminate\Support\Js::from($chat->bot_reply) }})" class="font-semibold text-blue-700 transition hover:text-blue-900">
                                Read
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        @else
            <div class="flex h-full items-center justify-center px-4 py-12 text-center">
                <div class="section-card max-w-2xl p-8">
                    <div class="mx-auto mb-4 grid h-14 w-14 place-items-center rounded-2xl bg-blue-50 text-sm font-extrabold text-blue-700">AI</div>
                    <p class="mb-2 text-2xl font-extrabold text-slate-950">Start a focused college conversation</p>
                    <p class="mb-6 text-slate-600">Ask about admissions, courses, notices, study plans, or uploaded files.</p>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-left text-sm">
                        <p class="mb-2 font-bold text-slate-950">Useful starting points</p>
                        <ul class="space-y-1 text-slate-600">
                            <li>Compare two courses or career paths</li>
                            <li>Summarize an admission PDF or screenshot</li>
                            <li>Create a weekly study plan</li>
                            <li>Explain eligibility, documents, and next steps</li>
                        </ul>
                    </div>
                </div>
            </div>
        @endif
            </div>

            <!-- Input Area (Bottom) -->
            <div class="border-t border-slate-200 bg-white px-4 py-5 shadow-[0_-12px_30px_rgba(15,23,42,0.05)] sm:px-6">
                <div class="max-w-3xl mx-auto">
            
            <!-- Main Input Container -->
            <div id="composerBox" class="space-y-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-lg shadow-slate-200/70 transition">
                
                <!-- Tools and Controls Row -->
                <div class="flex items-center justify-between">
                    <div class="flex gap-2">
                        <!-- Plus Button (File Upload) -->
                        <button onclick="document.getElementById('fileInput').click()" 
                            class="grid h-9 w-9 place-items-center rounded-xl border border-slate-200 text-lg font-bold text-slate-600 transition hover:bg-slate-50"
                            title="Upload file">
                            +
                        </button>
                        
                        <!-- Tools Button -->
                        <button class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
                            Tools
                        </button>
                    </div>
                    
                    <div class="text-xs text-slate-500">
                        <span id="voiceStatus" class="hidden">Listening...</span>
                    </div>
                </div>

                <!-- Message Input and Controls -->
                <div class="flex gap-3 items-end">
                    <input type="hidden" id="conversationId" value="{{ optional($activeConversation)->id }}">
                    <input type="hidden" id="quickPromptInput" value="{{ request('prompt') }}">
                    <div class="flex-1">
                        <textarea id="message"
                            rows="1"
                            class="w-full resize-none rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-900 transition placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-blue-100"
                            placeholder="Message College AI... (Enter to send, Shift+Enter for new line)"></textarea>
                        <div class="mt-2 flex items-center justify-between px-1">
                            <p id="composerHint" class="text-[11px] text-slate-500">Supports multi-line messages and file uploads</p>
                            <p id="charCount" class="text-[11px] text-slate-500">0 / 2000</p>
                        </div>
                    </div>
                    
                    <!-- Gemini Voice Button -->
                    <button onclick="toggleVoiceInput()"
                        class="h-12 rounded-xl border border-slate-200 px-4 text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
                        id="voiceBtn"
                        title="Voice Input">
                        Voice
                    </button>
                    
                    <!-- Send Button -->
                    <button onclick="sendMessage()"
                        class="h-12 rounded-xl bg-slate-950 px-5 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-40"
                        id="sendBtn"
                        disabled
                        title="Send">
                        Send
                    </button>
                </div>

                <!-- File Preview -->
                <div id="fileName" class="px-1 text-xs text-slate-500"></div>
            </div>

            <!-- Pro Badge -->
            <div class="flex justify-center mt-3">
                <span class="text-xs text-slate-500">Powered by Gemini. College AI v1.0</span>
            </div>
            </div>
        </div>
    </div>

    <!-- Hidden File Input -->
    <input type="file" id="fileInput" class="hidden" accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png,.webp,.gif" />

    <!-- Edit Message Modal -->
    <div id="editModal" class="hidden fixed inset-0 bg-slate-950/50 flex items-center justify-center z-50">
        <div class="section-card mx-4 w-full max-w-md p-6">
            <h3 class="mb-4 text-lg font-bold text-slate-950">Edit Message</h3>
            
            <textarea id="editMessageText" 
                class="form-input resize-none"
                rows="4"
                placeholder="Edit your message..."></textarea>
            
            <div class="flex gap-3 mt-4">
                <button onclick="closeEditModal()" 
                    class="btn-secondary flex-1 py-2">
                    Cancel
                </button>
                <button onclick="submitEditMessage()" 
                    class="btn-primary flex-1 py-2">
                    Send Edited
                </button>
            </div>
        </div>
    </div>

    <!-- Delete Conversation Modal -->
    <div id="deleteConversationModal" class="hidden fixed inset-0 bg-slate-950/50 flex items-center justify-center z-50">
        <div class="section-card mx-4 w-full max-w-md p-6">
            <h3 class="mb-2 text-lg font-bold text-slate-950">Delete conversation</h3>
            <p class="mb-4 text-sm text-slate-600">
                Are you sure you want to delete
                <span id="deleteConversationTitle" class="font-semibold text-slate-950"></span>?
            </p>
            <div class="flex gap-3">
                <button onclick="closeDeleteModal()" class="btn-secondary flex-1 py-2">
                    Cancel
                </button>
                <button onclick="confirmDeleteConversation()" class="btn-danger flex-1 py-2">
                    Delete
                </button>
            </div>
        </div>
    </div>

    <!-- Undo Toast -->
    <div id="undoToast" class="hidden fixed bottom-4 right-4 rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-lg z-50">
        <p class="text-sm font-medium text-slate-700">Conversation deleted.</p>
    </div>

</div>

<!-- Styling -->
<style>
    @keyframes fade-in {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .animate-fade-in {
        animation: fade-in 0.3s ease-in-out;
    }

    .animate-pulse {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }

    @keyframes pulse {
        0%, 100% {
            opacity: 1;
        }
        50% {
            opacity: 0.5;
        }
    }

    /* Custom scrollbar */
    #chatBox::-webkit-scrollbar {
        width: 8px;
    }

    #chatBox::-webkit-scrollbar-track {
        background: #e2e8f0;
    }

    #chatBox::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }

    #chatBox::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    /* Mute button styling */
    #muteBtn {
        transition: all 0.3s ease;
        background-color: transparent;
    }

    #muteBtn:hover {
        background-color: #f8fafc;
    }

    /* Edit button visibility on hover */
    .group .group-hover\:opacity-100 {
        opacity: 0;
        transition: opacity 0.2s ease;
    }

    .group:hover .group-hover\:opacity-100 {
        opacity: 1;
    }

    .opacity-0 {
        opacity: 0;
    }

    .reply-content {
        line-height: 1.65;
        word-break: break-word;
    }

    .reply-content > :first-child {
        margin-top: 0;
    }

    .reply-content > :last-child {
        margin-bottom: 0;
    }

    .reply-content h3,
    .reply-content h4 {
        font-size: 0.95rem;
        font-weight: 700;
        margin-top: 0.75rem;
        margin-bottom: 0.35rem;
        color: #0f172a;
    }

    .reply-content h4 {
        font-size: 0.9rem;
        color: #1d4ed8;
    }

    .reply-content ul,
    .reply-content ol {
        margin: 0.35rem 0 0.35rem 1rem;
    }

    .reply-content ul {
        list-style-type: disc;
    }

    .reply-content ol {
        list-style-type: decimal;
    }

    .reply-content li {
        margin: 0.2rem 0;
    }

    .reply-content p {
        margin: 0.25rem 0;
    }

    .reply-content strong {
        color: #0f172a;
        font-weight: 700;
    }

    .reply-content code {
        background: #f1f5f9;
        border: 1px solid #cbd5e1;
        border-radius: 0.35rem;
        color: #1d4ed8;
        font-size: 0.82rem;
        padding: 0.1rem 0.3rem;
    }

    .reply-content blockquote {
        border-left: 3px solid #60a5fa;
        color: #475569;
        margin: 0.5rem 0;
        padding-left: 0.75rem;
    }
</style>

<script>
let selectedFile = null;
let isLoading = false;
let isRecording = false;
const csrfToken = "{{ csrf_token() }}";
let pendingDeleteConversation = null;
let undoTimer = null;
let searchDebounceTimer = null;
let sidebarOpen = false;

function toggleSidebar(forceState = null) {
    const sidebar = document.getElementById('chatSidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    const toggleBtn = document.getElementById('sidebarToggleBtn');
    if (!sidebar || !backdrop || !toggleBtn) return;

    if (forceState === null) {
        sidebarOpen = !sidebarOpen;
    } else {
        sidebarOpen = Boolean(forceState);
    }

    if (window.innerWidth >= 768) {
        // Desktop: allow collapse/expand
        sidebar.classList.toggle('-translate-x-full', !sidebarOpen);
        backdrop.classList.add('hidden');
    } else {
        // Mobile: slide-over with backdrop
        sidebar.classList.toggle('-translate-x-full', !sidebarOpen);
        backdrop.classList.toggle('hidden', !sidebarOpen);
    }

    toggleBtn.textContent = sidebarOpen ? 'Hide Chats' : 'Show Chats';
    localStorage.setItem('chat_sidebar_open', sidebarOpen ? '1' : '0');
}

function showToast(message) {
    const toast = document.getElementById('undoToast');
    toast.querySelector('p').textContent = message;
    toast.classList.remove('hidden');
}

function hideToast() {
    document.getElementById('undoToast').classList.add('hidden');
}

function startInlineRename(conversationId, currentTitle) {
    const row = document.getElementById(`renameRow-${conversationId}`);
    const input = document.getElementById(`renameInput-${conversationId}`);
    if (!row || !input) return;
    row.classList.remove('hidden');
    input.value = currentTitle;
    input.focus();
    input.select();
}

function cancelInlineRename(conversationId) {
    const row = document.getElementById(`renameRow-${conversationId}`);
    if (row) {
        row.classList.add('hidden');
    }
}

function submitInlineRename(conversationId) {
    const input = document.getElementById(`renameInput-${conversationId}`);
    if (!input) return;
    const updatedTitle = input.value.trim();
    if (!updatedTitle) return;

    fetch(`/chat/${conversationId}/title`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ title: updatedTitle })
    })
        .then(() => {
            showToast('Conversation renamed.');
            window.location.reload();
        });
}

function togglePinConversation(conversationId) {
    fetch(`/chat/${conversationId}/pin`, {
        method: 'PATCH',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        }
    })
        .then(() => {
            showToast('Pin updated.');
            window.location.reload();
        });
}

function openDeleteModal(conversationId, title) {
    pendingDeleteConversation = { id: conversationId, title: title };
    document.getElementById('deleteConversationTitle').textContent = `"${title}"`;
    document.getElementById('deleteConversationModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteConversationModal').classList.add('hidden');
    pendingDeleteConversation = null;
}

function confirmDeleteConversation() {
    if (!pendingDeleteConversation) return;
    const { id, title } = pendingDeleteConversation;
    closeDeleteModal();

    fetch(`/chat/${id}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        }
    }).then(() => {
        showToast(`Deleted "${title}".`);
        const currentConversationId = document.getElementById('conversationId').value;
        if (String(currentConversationId) === String(id)) {
            window.location.href = "{{ route('chat') }}";
            return;
        }

        clearTimeout(undoTimer);
        undoTimer = setTimeout(() => {
            hideToast();
        }, 5000);
        window.location.reload();
    });
}

function applyConversationSearch() {
    const searchInput = document.getElementById('conversationSearch');
    const indicator = document.getElementById('searchIndicator');
    const query = searchInput.value.trim().toLowerCase();
    const conversationItems = Array.from(document.querySelectorAll('.conversation-item'));
    const noResults = document.getElementById('noSearchResults');
    let visibleCount = 0;

    conversationItems.forEach((item) => {
        const title = item.dataset.title || '';
        const preview = item.dataset.preview || '';
        const isMatch = !query || title.includes(query) || preview.includes(query);
        item.classList.toggle('hidden', !isMatch);

        const titleElement = item.querySelector('.conversation-title');
        if (titleElement) {
            titleElement.innerHTML = highlightQuery(titleElement.textContent, query);
        }

        if (isMatch) {
            visibleCount += 1;
        }
    });

    noResults.classList.toggle('hidden', visibleCount !== 0 || !query);
    indicator.classList.add('hidden');
}

function highlightQuery(text, query) {
    if (!query) return text;
    const escaped = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    return text.replace(new RegExp(`(${escaped})`, 'ig'), '<mark class="rounded bg-blue-100 px-1 text-blue-900">$1</mark>');
}

function updateMessageComposerUI() {
    const messageInput = document.getElementById('message');
    const charCount = document.getElementById('charCount');
    if (!messageInput) return;

    messageInput.style.height = 'auto';
    messageInput.style.height = Math.min(messageInput.scrollHeight, 180) + 'px';
    messageInput.style.overflowY = messageInput.scrollHeight > 180 ? 'auto' : 'hidden';

    if (charCount) {
        charCount.textContent = `${messageInput.value.length} / 2000`;
        charCount.className = messageInput.value.length > 1800
            ? 'text-[11px] text-orange-600'
            : 'text-[11px] text-slate-500';
    }

    updateSendButtonState();
}

function updateSendButtonState() {
    const messageInput = document.getElementById('message');
    const sendBtn = document.getElementById('sendBtn');
    if (!messageInput || !sendBtn) return;

    const hasMessage = messageInput.value.trim().length > 0;
    const hasFile = Boolean(selectedFile);
    sendBtn.disabled = isLoading || (!hasMessage && !hasFile);
}

function clearSelectedFile() {
    selectedFile = null;
    const fileInput = document.getElementById('fileInput');
    const fileName = document.getElementById('fileName');
    if (fileInput) fileInput.value = '';
    if (fileName) fileName.textContent = '';
    updateSendButtonState();
}

function renderSelectedFile() {
    const fileName = document.getElementById('fileName');
    if (!fileName) return;

    if (!selectedFile) {
        fileName.textContent = '';
        return;
    }

    const fileInfo = `${escapeHtml(selectedFile.name)} (${(selectedFile.size / 1024).toFixed(2)} KB)`;
    const preview = `
        <div class="mt-2 flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
            <p class="mr-3 truncate text-xs text-slate-600">File: ${fileInfo}</p>
            <button type="button" onclick="clearSelectedFile()" class="rounded-lg border border-slate-200 bg-white px-2 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-50">
                Remove
            </button>
        </div>
    `;

    if (selectedFile.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function (event) {
            fileName.innerHTML = `
                <div class="mt-2">
                    <img src="${event.target.result}" class="max-w-xs rounded-xl border border-slate-200 shadow-sm" alt="preview">
                    ${preview}
                </div>
            `;
        };
        reader.readAsDataURL(selectedFile);
        return;
    }

    fileName.innerHTML = preview;
}

function escapeHtml(text) {
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function escapeForInlineHandler(value) {
    return escapeHtml(JSON.stringify(String(value ?? '')));
}

function formatUserMessage(text) {
    return escapeHtml(text).replace(/\r\n/g, '\n').replace(/\n/g, '<br>');
}

function formatReplyToHtml(text) {
    const safeText = escapeHtml(text ?? '').replace(/\r\n/g, '\n');
    const lines = safeText.split('\n');
    let html = '';
    let listType = null;

    const inlineMarkdown = (value) => value
        .replace(/`([^`]+?)`/g, '<code>$1</code>')
        .replace(/\*\*\*(.+?)\*\*\*/g, '<strong><em>$1</em></strong>')
        .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
        .replace(/\*(.+?)\*/g, '<em>$1</em>');

    const closeList = () => {
        if (!listType) return;
        html += listType === 'ol' ? '</ol>' : '</ul>';
        listType = null;
    };

    const openList = (type) => {
        if (listType === type) return;
        closeList();
        html += type === 'ol' ? '<ol>' : '<ul>';
        listType = type;
    };

    lines.forEach((line) => {
        const trimmed = line.trim();

        if (!trimmed) {
            closeList();
            return;
        }

        const heading = trimmed.match(/^(#{2,4})\s+(.+)$/);
        if (heading) {
            closeList();
            const tag = heading[1].length >= 4 ? 'h4' : 'h3';
            html += `<${tag}>${inlineMarkdown(heading[2])}</${tag}>`;
            return;
        }

        const orderedItem = trimmed.match(/^\d+\.\s+(.+)$/);
        if (orderedItem) {
            openList('ol');
            html += `<li>${inlineMarkdown(orderedItem[1])}</li>`;
            return;
        }

        const unorderedItem = trimmed.match(/^[-*]\s+(.+)$/);
        if (unorderedItem) {
            openList('ul');
            html += `<li>${inlineMarkdown(unorderedItem[1])}</li>`;
            return;
        }

        if (trimmed.startsWith('&gt; ')) {
            closeList();
            html += `<blockquote>${inlineMarkdown(trimmed.replace(/^&gt;\s+/, ''))}</blockquote>`;
            return;
        }

        closeList();
        html += `<p>${inlineMarkdown(trimmed)}</p>`;
    });

    closeList();

    return html || '<p></p>';
}

function renderStoredReplies() {
    document.querySelectorAll('[data-reply]').forEach((element) => {
        element.innerHTML = formatReplyToHtml(element.dataset.reply || '');
        element.removeAttribute('data-reply');
    });
}

renderStoredReplies();

document.getElementById('conversationSearch')?.addEventListener('input', function () {
    const indicator = document.getElementById('searchIndicator');
    indicator.classList.remove('hidden');
    clearTimeout(searchDebounceTimer);
    searchDebounceTimer = setTimeout(applyConversationSearch, 220);
});

window.addEventListener('resize', function () {
    const savedSidebarState = localStorage.getItem('chat_sidebar_open') === '1';
    sidebarOpen = savedSidebarState;
    toggleSidebar(sidebarOpen);
});

applyConversationSearch();

document.getElementById('message')?.addEventListener('input', updateMessageComposerUI);
document.getElementById('message')?.addEventListener('keydown', function (event) {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        sendMessage();
    }
});
updateMessageComposerUI();
updateSendButtonState();

// Backward compatibility with old onclick references
function renameConversation(conversationId, currentTitle) {
    startInlineRename(conversationId, currentTitle);
}

function stopSpeaking() {
    window.speechSynthesis.cancel();
    showToast('Voice stopped.');
}

function syncVoiceButtonUI() {
    const muteBtn = document.getElementById('muteBtn');
    if (!muteBtn) return;
    muteBtn.textContent = '⏹ Stop Voice';
    muteBtn.className = 'btn-secondary px-4 py-2';
}

// Edit message functions
let editingMessageContent = '';

function editMessage(button, originalMessage) {
    editingMessageContent = originalMessage;
    document.getElementById('editMessageText').value = originalMessage;
    document.getElementById('editModal').classList.remove('hidden');
    document.getElementById('editMessageText').focus();
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
    document.getElementById('editMessageText').value = '';
    editingMessageContent = '';
}

function submitEditMessage() {
    let editedMessage = document.getElementById('editMessageText').value.trim();
    
    if (!editedMessage) {
        alert('Please enter a message');
        return;
    }
    
    closeEditModal();
    
    // Set the edited message to the input field and send
    document.getElementById('message').value = editedMessage;
    sendMessage();
}

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    const modal = document.getElementById('editModal');
    if (event.target === modal) {
        closeEditModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeEditModal();
    }
});

// Initialize Web Speech API
const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
const speechRecognition = new SpeechRecognition();
speechRecognition.continuous = false;
speechRecognition.interimResults = true;
speechRecognition.lang = 'en-US';

// Voice Recognition Event Handlers
speechRecognition.onstart = function() {
    isRecording = true;
    document.getElementById('voiceBtn').classList.add('animate-pulse', 'text-blue-700');
    document.getElementById('voiceStatus').classList.remove('hidden');
};

speechRecognition.onresult = function(event) {
    let interimTranscript = '';
    
    for (let i = event.resultIndex; i < event.results.length; i++) {
        const transcript = event.results[i][0].transcript;
        
        if (event.results[i].isFinal) {
            document.getElementById('message').value += transcript + ' ';
        } else {
            interimTranscript += transcript;
        }
    }
    
    if (interimTranscript) {
        document.getElementById('voiceStatus').textContent = 'Heard: "' + interimTranscript + '"';
    }

    updateMessageComposerUI();
};

speechRecognition.onerror = function(event) {
    document.getElementById('voiceStatus').textContent = '❌ Error: ' + event.error;
};

speechRecognition.onend = function() {
    isRecording = false;
    document.getElementById('voiceBtn').classList.remove('animate-pulse', 'text-blue-700');
    setTimeout(() => {
        document.getElementById('voiceStatus').classList.add('hidden');
    }, 1500);
};

// Toggle Voice Input
function toggleVoiceInput() {
    if (isRecording) {
        speechRecognition.stop();
    } else {
        document.getElementById('message').focus();
        speechRecognition.start();
    }
}

// Text-to-Speech Function with visual feedback
function speakText(text) {
    if ('speechSynthesis' in window) {
        window.speechSynthesis.cancel();
        
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.rate = 1.0;
        utterance.pitch = 1.0;
        utterance.volume = 1.0;
        
        // Show speaking indicator
        utterance.onstart = function() {
            console.log('Voice assistant started reading');
        };
        
        utterance.onend = function() {
            console.log('Voice assistant finished reading');
        };
        
        utterance.onerror = function(event) {
            console.error('Voice error:', event.error);
        };
        
        window.speechSynthesis.speak(utterance);
    }
}

// Update refresh timer
function updateRefreshTimer() {
    const timerElement = document.getElementById('refreshTimer');
    const labelElement = document.getElementById('nextRefreshLabel');
    const serverRefreshTime = "{{ Auth::user()->getNextRefreshTime()->toIso8601String() }}";
    const nextRefreshTime = localStorage.getItem('nextRefreshTime') || serverRefreshTime;

    if (!nextRefreshTime) return;

    const now = new Date();
    const refreshTime = new Date(nextRefreshTime);
    const diff = refreshTime - now;

    if (labelElement) {
        labelElement.textContent = `Refresh: ${refreshTime.toLocaleString('en-IN', {
            month: 'short',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
        })}`;
    }

    if (timerElement) {
        if (diff > 0) {
            const hours = Math.floor(diff / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            timerElement.textContent = `${hours}h ${minutes}m`;
        } else {
            location.reload();
        }
    }
}

// Update timer every minute
setInterval(updateRefreshTimer, 60000);
updateRefreshTimer();

// Handle file selection
document.getElementById('fileInput').addEventListener('change', function(e) {
    selectedFile = e.target.files[0];
    renderSelectedFile();
    updateSendButtonState();
});

const composerBox = document.getElementById('composerBox');
composerBox?.addEventListener('dragover', function (event) {
    event.preventDefault();
    composerBox.classList.add('border-blue-400', 'bg-blue-50');
    const composerHint = document.getElementById('composerHint');
    if (composerHint) composerHint.textContent = 'Drop file to attach';
});

composerBox?.addEventListener('dragleave', function () {
    composerBox.classList.remove('border-blue-400', 'bg-blue-50');
    const composerHint = document.getElementById('composerHint');
    if (composerHint) composerHint.textContent = 'Supports multi-line messages • Drag files here to upload';
});

composerBox?.addEventListener('drop', function (event) {
    event.preventDefault();
    composerBox.classList.remove('border-blue-400', 'bg-blue-50');
    const composerHint = document.getElementById('composerHint');
    if (composerHint) composerHint.textContent = 'Supports multi-line messages • Drag files here to upload';

    const files = event.dataTransfer?.files;
    if (!files || files.length === 0) return;
    selectedFile = files[0];
    renderSelectedFile();
    updateSendButtonState();
});

function sendMessage() {
    let msg = document.getElementById('message').value.trim();
    let conversationId = document.getElementById('conversationId').value;
    
    if (!msg && !selectedFile) {
        alert('Please type a message or select a file');
        return;
    }

    if (msg.length > 2000) {
        alert('Message is too long. Please keep it under 2000 characters.');
        return;
    }

    if (isLoading) return;

    isLoading = true;
    updateSendButtonState();

    let chatBox = document.getElementById('chatBox');
    const safeMsgHtml = formatUserMessage(msg);
    const msgForHandler = escapeForInlineHandler(msg);
    const safeFileName = selectedFile ? escapeHtml(selectedFile.name) : '';

    // Display user message
    if (msg) {
        chatBox.innerHTML += `
            <div class="flex justify-end animate-fade-in">
                <div class="group max-w-xl rounded-2xl bg-slate-950 p-4 text-white shadow-sm">
                    <p class="text-sm leading-6">${safeMsgHtml}</p>
                    <div class="flex justify-between items-center mt-2">
                        <p class="text-xs text-slate-400">${new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })}</p>
                        <button onclick="editMessage(this, ${msgForHandler})" class="rounded-lg bg-white/10 px-2 py-1 text-xs font-semibold text-white transition opacity-0 group-hover:opacity-100 hover:bg-white/20">
                            Edit
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    if (selectedFile) {
        chatBox.innerHTML += `
            <div class="flex justify-end animate-fade-in">
                <div class="max-w-lg rounded-2xl bg-slate-950 p-4 text-white shadow-sm">
                    ${selectedFile.type.startsWith('image/') ? `<img src="${URL.createObjectURL(selectedFile)}" class="mb-2 max-w-xs rounded-xl" alt="image">` : ''}
                    <p class="text-xs text-slate-300">File: ${safeFileName}</p>
                </div>
            </div>
        `;
    }

    // Display loading animation
    const loadingId = 'loading-' + Date.now();
    chatBox.innerHTML += `
        <div id="${loadingId}" class="flex justify-start animate-fade-in">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 text-slate-500 shadow-sm">
                <div class="flex gap-1">
                    <div class="h-2 w-2 animate-bounce rounded-full bg-blue-500" style="animation-delay: 0s"></div>
                    <div class="h-2 w-2 animate-bounce rounded-full bg-blue-500" style="animation-delay: 0.2s"></div>
                    <div class="h-2 w-2 animate-bounce rounded-full bg-blue-500" style="animation-delay: 0.4s"></div>
                </div>
                <p class="mt-2 text-xs text-slate-500">Thinking...</p>
            </div>
        </div>
    `;

    // Prepare form data
    let formData = new FormData();
    formData.append('message', msg);
    if (conversationId) {
        formData.append('conversation_id', conversationId);
    }
    if (selectedFile) {
        formData.append('attachment', selectedFile);
    }

    // Auto-scroll to bottom
    chatBox.scrollTop = chatBox.scrollHeight;

    // Send request
    fetch("{{ route('chat.send') }}", {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": "{{ csrf_token() }}"
        },
        body: formData
    })
    .then(res => res.json().then(data => ({ status: res.status, data })))
    .then(({ status, data }) => {
        const loadingElement = document.getElementById(loadingId);
        if (loadingElement) {
            loadingElement.remove();
        }

        if (data.next_refresh) {
            localStorage.setItem('nextRefreshTime', data.next_refresh);
            updateRefreshTimer();
        }

        if (!conversationId && data.conversation_id) {
            window.location.href = `/chat/${data.conversation_id}`;
            return;
        }

        if (status === 402 && data.error) {
            const formattedReply = formatReplyToHtml(data.reply || '');
            
            if (data.credits_depleted) {
                chatBox.innerHTML += `
                    <div class="flex justify-start animate-fade-in">
                        <div class="max-w-2xl rounded-2xl border border-red-200 bg-red-50 p-4 text-red-800 shadow-sm">
                            <div class="text-sm leading-relaxed reply-content">${formattedReply}</div>
                            <p class="mt-3 text-xs font-semibold text-red-700">Check back later for credit refresh</p>
                        </div>
                    </div>
                `;
                // Update header color and timer
                document.getElementById('creditDisplay').className = 'text-base font-extrabold text-red-700';
                updateRefreshTimer();
                
            } else {
                chatBox.innerHTML += `
                    <div class="flex justify-start animate-fade-in">
                        <div class="max-w-2xl rounded-2xl border border-amber-200 bg-amber-50 p-4 text-amber-900 shadow-sm">
                            <div class="text-sm leading-relaxed reply-content">${formattedReply}</div>
                            <p class="mt-2 text-xs text-amber-700">Current credits: ${data.credits_remaining}</p>
                        </div>
                    </div>
                `;
                
            }
        } else {
            if (data.conversation_id) {
                document.getElementById('conversationId').value = data.conversation_id;
            }
            const replyText = data.reply || '';
            const replyForHandler = escapeForInlineHandler(replyText);
            chatBox.innerHTML += `
                <div class="flex justify-start animate-fade-in">
                    <div class="max-w-3xl rounded-2xl border border-slate-200 bg-white p-5 text-slate-800 shadow-sm">
                        <div class="text-sm leading-relaxed reply-content">${formatReplyToHtml(replyText)}</div>
                        <div class="mt-4 flex items-center justify-between gap-3 border-t border-slate-100 pt-3 text-xs text-slate-500">
                            <div class="flex items-center gap-2">
                                <span id="voice-status-${Date.now()}" class="hidden text-blue-700">Reading...</span>
                                <span>${new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })}</span>
                            </div>
                            <div class="flex gap-2">
                                <button onclick="speakText(${replyForHandler})" class="font-semibold text-blue-700 transition hover:text-blue-900">
                                    Read
                                </button>
                                ${data.credits_used ? `<span class="text-slate-400">-${data.credits_used} | ${data.credits_remaining} left</span>` : ''}
                                ${data.credits_depleted && data.next_refresh_label ? `<span class="font-semibold text-red-700">Refresh: ${escapeHtml(data.next_refresh_label)}</span>` : ''}
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            if (data.credits_remaining !== undefined) {
                document.getElementById('creditDisplay').textContent = data.credits_remaining;
            }
        }
        
        chatBox.scrollTop = chatBox.scrollHeight;
        
        isLoading = false;
        updateSendButtonState();
    })
    .catch(error => {
        console.error('Error:', error);
        
        const loadingElement = document.getElementById(loadingId);
        if (loadingElement) {
            loadingElement.remove();
        }

        const errorMsg = 'Error sending message. Please try again.';
        chatBox.innerHTML += `
            <div class="flex justify-start animate-fade-in">
                <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-red-800">
                    <p class="text-sm">${errorMsg}</p>
                </div>
            </div>
        `;
        
        isLoading = false;
        updateSendButtonState();
    });

    document.getElementById('message').value = "";
    updateMessageComposerUI();
    clearSelectedFile();
}

syncVoiceButtonUI();
const savedSidebarState = localStorage.getItem('chat_sidebar_open') === '1';
sidebarOpen = savedSidebarState;
toggleSidebar(sidebarOpen);

const quickPromptInput = document.getElementById('quickPromptInput');
if (quickPromptInput && quickPromptInput.value.trim()) {
    document.getElementById('message').value = quickPromptInput.value.trim();
    updateMessageComposerUI();
    setTimeout(() => sendMessage(), 200);
}
</script>

@endsection
