<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    // Fetch or create a conversation
    public function getOrCreateConversation(Request $request, $userId)
    {
        $currentUser = auth()->user();

        $conversation = Conversation::where(function ($query) use ($currentUser, $userId) {
            $query->where('sender_id', $currentUser->id)
                ->where('receiver_id', $userId);
        })->orWhere(function ($query) use ($currentUser, $userId) {
            $query->where('sender_id', $userId)
                ->where('receiver_id', $currentUser->id);
        })->first();

        if (!$conversation) {
            $conversation = Conversation::create([
                'sender_id' => $currentUser->id,
                'receiver_id' => $userId
            ]);
        }

        return response()->json($conversation);
    }

    public function storeMessage(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'sender_id' => 'required|exists:users,id',
            'message' => 'required|string',
        ]);

        $message = Message::create([
            'conversation_id' => $request->conversation_id,
            'sender_id' => $request->sender_id,
            'message' => $request->message,
        ]);

        return response()->json($message, 201);
    }

    public function getMessages($conversationId)
    {
        $messages = Message::where('conversation_id', $conversationId)->with('sender')->get();
        return response()->json($messages);
    }

    public function getMyConversations(Request $request)
    {
        $userId = auth()->id();

        $conversations = Conversation::where('sender_id', $userId)
            ->orWhere('receiver_id', $userId)
            ->get();

        // Format the result if needed
        $conversations = $conversations->map(function ($conversation) {
            $latestMessage = $conversation->messages()->orderBy('created_at', 'desc')->first();

            return [
                'id' => $conversation->id,
                'messages' => $conversation->messages,
                'latest_message' => $latestMessage ? $latestMessage->message : null,
                'last_message_sender' => $latestMessage ? $latestMessage->sender : null,
                'sender' => $conversation->sender,
                'receiver' => $conversation->receiver,
            ];
        });

        return response()->json($conversations);
    }

    public function storeConversation(Request $request)
    {
        $sender_id = auth()->id();
        $receiver_id = $request->user_id;

        $conversation = Conversation::where(function ($query) use ($sender_id, $receiver_id) {
            $query->where('sender_id', $sender_id)
                ->where('receiver_id', $receiver_id);
        })->orWhere(function ($query) use ($sender_id, $receiver_id) {
            $query->where('sender_id', $receiver_id)
                ->where('receiver_id', $sender_id);
        })->first();

        if (!$conversation) {
            $conversation = Conversation::create([
                'sender_id' => $sender_id,
                'receiver_id' => $receiver_id,
            ]);
        }

        return response()->json($conversation, 201);
    }

    public function send(Request $request)
    {
        $validated = $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'message' => 'required|string',
        ]);

        $message = Message::create([
            'conversation_id' => $validated['conversation_id'],
            'sender_id' => Auth::id(),
            'message' => $validated['message'],
        ]);

        broadcast(new \App\Events\MessageSent($message))->toOthers();

        return response()->json($message, 200);
    }
}
