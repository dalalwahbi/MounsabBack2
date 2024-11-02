<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Conversation;
use App\Models\User;

class ConversationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // User IDs
        $user1Id = 3;
        $user2Id = 7;

        // Create a conversation between the two users
        Conversation::create([
            'sender_id' => $user1Id,
            'receiver_id' => $user2Id,
        ]);
    }
}
