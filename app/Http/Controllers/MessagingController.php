<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class MessagingController extends Controller
{
    public function sendMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sender_id' => 'required|exists:users,id',
            'receiver_id' => 'required|exists:users,id',
            'content' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $message = Message::create([
            'sender_id' => $request->sender_id,
            'receiver_id' => $request->receiver_id,
            'content' => $request->content
        ]);

        return response()->json([
            'message' => 'Message sent successfully',
            'data' => $message
        ], 201);
    }

    public function getConversations($userId)
    {
        // Get all unique conversation partners
        $sentMessages = Message::where('sender_id', $userId)
            ->select('receiver_id as partner_id')
            ->distinct()
            ->get();

        $receivedMessages = Message::where('receiver_id', $userId)
            ->select('sender_id as partner_id')
            ->distinct()
            ->get();

        // Combine and get unique partner IDs
        $partnerIds = $sentMessages->pluck('partner_id')
            ->merge($receivedMessages->pluck('partner_id'))
            ->unique();

        $conversations = [];

        foreach ($partnerIds as $partnerId) {
            // Get the latest message between these users
            $latestMessage = Message::where(function ($query) use ($userId, $partnerId) {
                $query->where('sender_id', $userId)->where('receiver_id', $partnerId);
            })->orWhere(function ($query) use ($userId, $partnerId) {
                $query->where('sender_id', $partnerId)->where('receiver_id', $userId);
            })->orderBy('created_at', 'desc')->first();

            // Get unread count
            $unreadCount = Message::where('sender_id', $partnerId)
                ->where('receiver_id', $userId)
                ->where('read', false)
                ->count();

            // Get partner info
            $partner = User::find($partnerId);

            if ($latestMessage && $partner) {
                $conversations[] = [
                    'partner_id' => $partnerId,
                    'partner_email' => $partner->email,
                    'latest_message' => $latestMessage->content,
                    'timestamp' => $latestMessage->created_at->toISOString(),
                    'unread_count' => $unreadCount
                ];
            }
        }

        // Sort by latest message timestamp
        usort($conversations, function ($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });

        return response()->json($conversations, 200);
    }

    public function getMessages($user1Id, $user2Id)
    {
        // Get all messages between two users
        $messages = Message::where(function ($query) use ($user1Id, $user2Id) {
            $query->where('sender_id', $user1Id)->where('receiver_id', $user2Id);
        })->orWhere(function ($query) use ($user1Id, $user2Id) {
            $query->where('sender_id', $user2Id)->where('receiver_id', $user1Id);
        })->orderBy('created_at', 'asc')->get();

        // Mark messages as read
        Message::where('sender_id', $user2Id)
            ->where('receiver_id', $user1Id)
            ->where('read', false)
            ->update(['read' => true]);

        return response()->json($messages, 200);
    }
}

