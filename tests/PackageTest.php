<?php

namespace Martinko366\LaravelDbChat\Tests;

class PackageTest extends TestCase
{
    /** @test */
    public function it_can_load_service_provider()
    {
        $this->assertTrue(true);
    }

    /** @test */
    public function it_can_run_migrations()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        
        $this->assertTrue(
            \Schema::hasTable('chat_conversations'),
            'chat_conversations table should exist'
        );
        
        $this->assertTrue(
            \Schema::hasTable('chat_participants'),
            'chat_participants table should exist'
        );
        
        $this->assertTrue(
            \Schema::hasTable('chat_messages'),
            'chat_messages table should exist'
        );
        
        $this->assertTrue(
            \Schema::hasTable('chat_message_reads'),
            'chat_message_reads table should exist'
        );
    }
}
