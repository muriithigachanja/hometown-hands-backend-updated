<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class MessagingController extends Controller
{
    public function getConversations(Request $request)
    {
        $user = $request->user();
        
        $conversations = Conversation::where('user1_id', $user->id)
            ->orWhere('user2_id', $user->id)
            ->with(['user1', 'user2', 'lastMessage'])
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json([
            'conversations' => $conversations
        ], 200);
    }

    public function getMessages(Request $request, $conversationId)
    {
        $user = $request->user();
        
        // Verify user is part of this conversation
        $conversation = Conversation::where('id', $conversationId)
            ->where(function($query) use ($user) {
                $query->where('user1_id', $user->id)
                      ->orWhere('user2_id', $user->id);
            })
            ->first();

        if (!$conversation) {
            return response()->json(['error' => 'Conversation not found'], 404);
        }

        $messages = Message::where('conversation_id', $conversationId)
            ->with('sender')
            ->orderBy('created_at', 'asc')
            ->get();

        // Mark messages as read
        Message::where('conversation_id', $conversationId)
            ->where('receiver_id', $user->id)
            ->where('read', false)
            ->update(['read' => true]);

        return response()->json([
            'messages' => $messages
        ], 200);
    }

    public function sendMessage(Request $request, $conversationId)
    {
        $user = $request->user();
        
        $validator = Validator::make($request->all(), [
            'message' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        // Verify user is part of this conversation
        $conversation = Conversation::where('id', $conversationId)
            ->where(function($query) use ($user) {
                $query->where('user1_id', $user->id)
                      ->orWhere('user2_id', $user->id);
            })
            ->first();

        if (!$conversation) {
            return response()->json(['error' => 'Conversation not found'], 404);
        }

        // Determine receiver
        $receiverId = $conversation->user1_id === $user->id 
            ? $conversation->user2_id 
            : $conversation->user1_id;

        $message = Message::create([
            'conversation_id' => $conversationId,
            'sender_id' => $user->id,
            'receiver_id' => $receiverId,
            'content' => $request->message,
            'read' => false
        ]);

        // Update conversation timestamp
        $conversation->touch();

        return response()->json([
            'message' => 'Message sent successfully',
            'data' => $message->load('sender')
        ], 201);
    }

    public function createConversation(Request $request)
    {
        $user = $request->user();
        
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $otherUserId = $request->user_id;

        // Check if conversation already exists
        $existingConversation = Conversation::where(function($query) use ($user, $otherUserId) {
            $query->where('user1_id', $user->id)->where('user2_id', $otherUserId);
        })->orWhere(function($query) use ($user, $otherUserId) {
            $query->where('user1_id', $otherUserId)->where('user2_id', $user->id);
        })->first();

        if ($existingConversation) {
            return response()->json([
                'conversation' => $existingConversation->load(['user1', 'user2'])
            ], 200);
        }

        // Create new conversation
        $conversation = Conversation::create([
            'user1_id' => $user->id,
            'user2_id' => $otherUserId
        ]);

        return response()->json([
            'message' => 'Conversation created successfully',
            'conversation' => $conversation->load(['user1', 'user2'])
        ], 201);
    }
}

