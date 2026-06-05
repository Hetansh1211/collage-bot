<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Gemini\Laravel\Facades\Gemini;
use Gemini\Data\Blob;
use Gemini\Enums\MimeType;
use App\Models\Chat;
use App\Models\Conversation;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ChatController extends Controller
{
    private const CREDITS_PER_MESSAGE = 30;
    private const REFRESH_CREDITS = 300;

    public function index()
    {
        $chats = collect();
        $activeConversation = null;
        $conversations = $this->getConversationsForUser(request('q'));

        return view('chat', compact('chats', 'conversations', 'activeConversation'));
    }

    public function show($conversation)
    {
        $activeConversation = Conversation::where('user_id', Auth::id())
            ->findOrFail($conversation);

        $chats = Chat::where('user_id', Auth::id())
            ->where('conversation_id', $activeConversation->id)
            ->latest()
            ->get();

        $conversations = $this->getConversationsForUser(request('q'));

        return view('chat', compact('chats', 'conversations', 'activeConversation'));
    }

    public function send(Request $request)
    {
        $request->validate([
            'message' => 'required_if:attachment,null|string|min:1|max:2000',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,txt,jpg,jpeg,png,gif,webp|max:5120',
            'conversation_id' => 'nullable|integer'
        ]);

        // Check if user has enough credits
        $user = Auth::user();
        $user->refreshCreditsIfDue();
        $user = $user->fresh();
        $creditsPerSearch = self::CREDITS_PER_MESSAGE;

        // Check if credits are totally depleted
        if ($user->credit_points <= 0) {
            $nextRefresh = $user->getNextRefreshTime();
            $refreshMessage = $nextRefresh->format('M d, Y h:i A');
            
            return response()->json([
                'reply' => "### Credits Unavailable\n\n" .
                    "- **Current balance:** 0 credits\n" .
                    "- **Refresh amount:** " . self::REFRESH_CREDITS . " credits\n" .
                    "- **Next refresh:** {$refreshMessage}\n\n" .
                    "Please try again after your credits refresh, or contact support if this looks incorrect.",
                'error' => true,
                'credits_depleted' => true,
                'credits_remaining' => 0,
                'next_refresh' => $nextRefresh->toIso8601String()
            ], 402); // 402 Payment Required
        }

        // Check if user has enough credits for one search
        if ($user->credit_points < $creditsPerSearch) {
            $nextRefresh = $user->getNextRefreshTime();
            $refreshMessage = $nextRefresh->format('M d, Y h:i A');

            return response()->json([
                'reply' => "### Not Enough Credits\n\n" .
                    "- **Required:** {$creditsPerSearch} credits\n" .
                    "- **Available:** {$user->credit_points} credits\n" .
                    "- **Next refresh:** {$refreshMessage}\n\n" .
                    "You can continue after your credits refresh.",
                'error' => true,
                'credits_remaining' => $user->credit_points,
                'next_refresh' => $nextRefresh->toIso8601String(),
                'next_refresh_label' => $refreshMessage,
            ], 402); // 402 Payment Required
        }

        $userMessage = $request->message ?? '';
        $conversationId = $request->conversation_id;
        $attachmentPath = null;
        $attachmentBase64 = null;
        $attachmentMimeType = null;
        $attachmentGeminiMimeType = null;

        if ($conversationId) {
            $conversation = Conversation::where('user_id', Auth::id())->findOrFail($conversationId);
        } else {
            $conversation = Conversation::create([
                'user_id' => Auth::id(),
                'title' => $this->generateConversationTitle($userMessage),
            ]);
        }

        // Handle file upload
        if ($request->hasFile('attachment')) {
            try {
                $file = $request->file('attachment');
                $filename = time() . '_' . $file->getClientOriginalName();
                $attachmentPath = $file->storeAs('attachments', $filename, 'public');
                
                $attachmentMimeType = $file->getMimeType();
                $attachmentGeminiMimeType = $this->resolveGeminiMimeType($attachmentMimeType);

                if ($attachmentGeminiMimeType) {
                    $attachmentBase64 = base64_encode((string) file_get_contents($file->getRealPath()));
                }
            } catch (\Exception $e) {
                \Log::error('File upload error', ['error' => $e->getMessage()]);
            }
        }

        // Local time response (no credits deducted).
        if ($this->isLocalTimeQuestion($userMessage)) {
            $now = Carbon::now('Asia/Kolkata');

            $reply = "### Current Time\n\n" .
                "- **Time:** " . $now->format('h:i A') . " IST\n" .
                "- **Date:** " . $now->format('l, d M Y');

            Chat::create([
                'conversation_id' => $conversation->id,
                'user_id' => Auth::id(),
                'user_message' => $userMessage,
                'bot_reply' => $reply,
                'user_attachment' => $attachmentPath
            ]);

            return response()->json([
                'reply' => $reply,
                'credits_remaining' => $user->credit_points,
                'next_refresh' => $user->getNextRefreshTime()->toIso8601String(),
                'next_refresh_label' => $user->nextCreditRefreshLabel(),
                'conversation_id' => $conversation->id,
            ]);
        }

        try {
            $prompt = $this->buildProfessionalPrompt(
                $this->buildAttachmentAwareInput(
                    $userMessage,
                    $attachmentMimeType,
                    (bool) ($attachmentBase64 && $attachmentGeminiMimeType)
                )
            );

            $content = [$prompt];
            if ($attachmentBase64 && $attachmentGeminiMimeType) {
                $content[] = new Blob(
                    mimeType: $attachmentGeminiMimeType,
                    data: $attachmentBase64
                );
            }
            
            // Use the model from config
            $result = Gemini::generativeModel(config('gemini.model'))
                ->generateContent($content);

            $reply = $this->normalizeReply($result->text());

            Chat::create([
                'conversation_id' => $conversation->id,
                'user_id' => Auth::id(),
                'user_message' => $userMessage,
                'bot_reply' => $reply,
                'user_attachment' => $attachmentPath
            ]);

            // Deduct credits after successful response
            $user->decrement('credit_points', $creditsPerSearch);
            $freshUser = $user->fresh();
            $nextRefresh = $freshUser->getNextRefreshTime();

            return response()->json([
                'reply' => $reply,
                'credits_remaining' => $freshUser->credit_points,
                'credits_used' => $creditsPerSearch,
                'credits_depleted' => $freshUser->credit_points <= 0,
                'next_refresh' => $nextRefresh->toIso8601String(),
                'next_refresh_label' => $nextRefresh->format('M d, Y h:i A'),
                'conversation_id' => $conversation->id,
            ]);

        } catch (\Throwable $e) {

            \Log::error('Gemini Error', [
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);

            return response()->json([
                'reply' => "### AI Service Unavailable\n\nThe assistant could not generate a response right now. Please try again in a moment.",
                'credits_remaining' => $user->credit_points,
                'error' => true
            ], 500);
        }
    }

    public function renameConversation(Request $request, $conversation)
    {
        $request->validate([
            'title' => 'required|string|min:1|max:120',
        ]);

        $targetConversation = Conversation::where('user_id', Auth::id())
            ->findOrFail($conversation);

        $targetConversation->update([
            'title' => trim($request->title),
        ]);

        return response()->json([
            'message' => 'Conversation renamed successfully.',
            'title' => $targetConversation->title,
        ]);
    }

    public function togglePinConversation($conversation)
    {
        $targetConversation = Conversation::where('user_id', Auth::id())
            ->findOrFail($conversation);

        $targetConversation->update([
            'is_pinned' => !$targetConversation->is_pinned,
        ]);

        return response()->json([
            'message' => 'Pin status updated successfully.',
            'is_pinned' => $targetConversation->is_pinned,
        ]);
    }

    public function deleteConversation($conversation)
    {
        $targetConversation = Conversation::where('user_id', Auth::id())
            ->findOrFail($conversation);

        Chat::where('user_id', Auth::id())
            ->where('conversation_id', $targetConversation->id)
            ->delete();

        $targetConversation->delete();

        return response()->json([
            'message' => 'Conversation deleted successfully.',
        ]);
    }

    private function getConversationsForUser(?string $search = null)
    {
        return Conversation::where('user_id', Auth::id())
            ->when($search, function ($query, $searchText) {
                $searchText = trim($searchText);
                $query->where(function ($nestedQuery) use ($searchText) {
                    $nestedQuery->where('title', 'like', '%' . $searchText . '%')
                        ->orWhereHas('chats', function ($chatQuery) use ($searchText) {
                            $chatQuery->where('user_message', 'like', '%' . $searchText . '%');
                        });
                });
            })
            ->with('latestChat')
            ->orderByDesc('is_pinned')
            ->latest()
            ->limit(50)
            ->get();
    }

    private function generateConversationTitle(string $message): string
    {
        $title = trim($message);

        if ($title === '') {
            return 'New Chat';
        }

        return str($title)->limit(60)->toString();
    }

    private function buildProfessionalPrompt(string $userInput): string
    {
        $userInput = trim($userInput);

        return <<<PROMPT
You are College AI, a professional college chatbot for students, parents, and staff.

Answer style:
- Be clear, polished, respectful, and student-friendly.
- Start with the direct answer. Do not start with filler like "Sure" or "Of course".
- Use Markdown with short sections, `###` headings, bullets, and bold labels.
- Keep paragraphs short. Prefer practical steps over long explanations.
- Use simple language, but keep the tone professional.
- End with `### What to Do Next` when the user needs an action plan.

Accuracy rules:
- Do not invent official fees, cutoffs, deadlines, policies, admission rules, or exam dates.
- If details depend on a specific college, course, year, location, or official notice, state that clearly and suggest verifying with the college office or official website.
- If the question is unclear, answer the most likely intent and ask one short follow-up question.
- If an attachment cannot be inspected, say so plainly and ask for a supported file or pasted text.

User question:
{$userInput}
PROMPT;
    }

    private function buildAttachmentAwareInput(string $userMessage, ?string $attachmentMimeType, bool $canAnalyzeAttachment): string
    {
        $message = trim($userMessage);

        if ($attachmentMimeType === null) {
            return $message;
        }

        if ($canAnalyzeAttachment) {
            $instruction = 'Use the attached file as context. Summarize key details, extract visible or readable text when relevant, and answer the student professionally.';

            return trim($message . "\n\n" . $instruction);
        }

        $instruction = "The user uploaded a file with MIME type `{$attachmentMimeType}`, but this application cannot send that file type to the AI model. Do not pretend to read it. Explain that PDF, TXT, PNG, JPG/JPEG, and WEBP files can be analyzed, or ask the user to paste the text.";

        return trim($message . "\n\n" . $instruction);
    }

    private function resolveGeminiMimeType(?string $mimeType): ?MimeType
    {
        return match ($mimeType) {
            'application/pdf' => MimeType::APPLICATION_PDF,
            'text/plain' => MimeType::TEXT_PLAIN,
            'image/jpeg', 'image/jpg' => MimeType::IMAGE_JPEG,
            'image/png' => MimeType::IMAGE_PNG,
            'image/webp' => MimeType::IMAGE_WEBP,
            default => null,
        };
    }

    private function isLocalTimeQuestion(string $message): bool
    {
        $message = trim($message);

        if ($message === '') {
            return false;
        }

        return (bool) preg_match('/^(time|current time|what time is it|what is the time|whats the time|show me the time|tell me the time|time now)$/i', $message);
    }

    private function normalizeReply(?string $reply): string
    {
        $reply = trim((string) $reply);

        if ($reply !== '') {
            return $reply;
        }

        return "### I Could Not Generate a Complete Answer\n\nPlease rephrase your question and try again.";
    }
}
