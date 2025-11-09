# Laravel DB Chat

A database-driven chat package for Laravel with long-polling support. No Redis, Pusher, or external services required - everything runs on your existing database.

## Features

- ✅ **Pure Database Solution** - No external services needed
- ✅ **Direct & Group Chats** - Support for 1:1 and group conversations
- ✅ **Long Polling** - Realtime-ish updates without WebSockets
- ✅ **Read Receipts** - Track who has seen which messages
- ✅ **Cursor-based Polling** - Efficient message fetching using monotonic IDs
- ✅ **Rate Limiting** - Built-in protection against abuse
- ✅ **Laravel 10, 11 & 12** - Compatible with modern Laravel versions

## Installation

Install the package via Composer:

```bash
composer require martinko366/laravel-db-chat
```

Publish the configuration and migrations:

```bash
php artisan vendor:publish --tag=dbchat-config
php artisan vendor:publish --tag=dbchat-migrations
```

Run the migrations:

```bash
php artisan migrate
```

## Configuration

The package configuration file is located at `config/dbchat.php`. You can customize:

- Database table names
- User model
- Route prefix and middleware
- Polling timeout and intervals
- Rate limits
- Message length limits

## Usage

### Authentication

The package uses Laravel Sanctum by default. Ensure you have authentication configured:

```php
// config/dbchat.php
'route' => [
    'prefix' => 'api/dbchat',
    'middleware' => ['api', 'auth:sanctum'],
],
```

### API Endpoints

All endpoints are prefixed with `/api/dbchat` by default.

#### Create a Conversation

**POST** `/api/dbchat/conversations`

```json
{
  "type": "direct",
  "participants": [1, 2]
}
```

For group chats:

```json
{
  "type": "group",
  "title": "Team Chat",
  "participants": [1, 2, 3, 4]
}
```

**Response:**
```json
{
  "conversation": {
    "id": 1,
    "type": "direct",
    "title": null,
    "created_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

#### List Conversations

**GET** `/api/dbchat/conversations`

Returns all conversations for the authenticated user.

#### Get Conversation Details

**GET** `/api/dbchat/conversations/{id}`

#### Send a Message

**POST** `/api/dbchat/conversations/{id}/messages`

```json
{
  "body": "Hello, world!",
  "attachments": [
    {
      "type": "image",
      "url": "https://example.com/image.jpg"
    }
  ]
}
```

**Response:**
```json
{
  "message": {
    "id": 523,
    "conversation_id": 1,
    "sender_id": 1,
    "body": "Hello, world!",
    "attachments": null,
    "created_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

#### Get Messages

**GET** `/api/dbchat/conversations/{id}/messages?before_message_id=100&limit=50`

Fetch historical messages with pagination.

#### Long Polling for New Messages

**GET** `/api/dbchat/poll?after_message_id=523`

This endpoint will wait up to 25 seconds (configurable) for new messages. Returns:

- `200` with new messages if available
- `204 No Content` if no new messages before timeout

**Response (200):**
```json
{
  "last_message_id": 526,
  "messages": [
    {
      "id": 524,
      "conversation_id": 1,
      "sender_id": 2,
      "body": "Hi there!",
      "created_at": "2024-01-01T00:00:01.000000Z"
    },
    {
      "id": 525,
      "conversation_id": 1,
      "sender_id": 2,
      "body": "How are you?",
      "created_at": "2024-01-01T00:00:02.000000Z"
    }
  ]
}
```

#### Mark Message as Read

**POST** `/api/dbchat/messages/{id}/read`

Returns `204 No Content` on success.

### Client Implementation Example

Here's a simple JavaScript client for long polling:

```javascript
class ChatClient {
  constructor(baseUrl, token) {
    this.baseUrl = baseUrl;
    this.token = token;
    this.lastMessageId = 0;
    this.isPolling = false;
  }

  async startPolling() {
    this.isPolling = true;
    
    while (this.isPolling) {
      try {
        const response = await fetch(
          `${this.baseUrl}/poll?after_message_id=${this.lastMessageId}`,
          {
            headers: {
              'Authorization': `Bearer ${this.token}`,
              'Accept': 'application/json'
            }
          }
        );

        if (response.status === 200) {
          const data = await response.json();
          this.lastMessageId = data.last_message_id;
          this.handleNewMessages(data.messages);
        }
        // Status 204 means no new messages, just continue polling
      } catch (error) {
        console.error('Polling error:', error);
        await this.sleep(5000); // Wait before retrying on error
      }
    }
  }

  stopPolling() {
    this.isPolling = false;
  }

  handleNewMessages(messages) {
    messages.forEach(message => {
      console.log('New message:', message);
      // Update your UI here
    });
  }

  async sendMessage(conversationId, body) {
    const response = await fetch(
      `${this.baseUrl}/conversations/${conversationId}/messages`,
      {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${this.token}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({ body })
      }
    );

    return response.json();
  }

  sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
  }
}

// Usage
const client = new ChatClient('https://yourapp.com/api/dbchat', 'your-token');
client.startPolling();
```

### Vue/React Component Example

Here's a basic Vue 3 composition example:

```vue
<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const messages = ref([]);
const lastMessageId = ref(0);
let isPolling = true;

async function poll() {
  while (isPolling) {
    try {
      const response = await fetch(
        `/api/dbchat/poll?after_message_id=${lastMessageId.value}`,
        { credentials: 'include' }
      );

      if (response.status === 200) {
        const data = await response.json();
        lastMessageId.value = data.last_message_id;
        messages.value.push(...data.messages);
      }
    } catch (error) {
      console.error(error);
      await new Promise(resolve => setTimeout(resolve, 5000));
    }
  }
}

onMounted(() => {
  poll();
});

onUnmounted(() => {
  isPolling = false;
});
</script>
```

## How It Works

### The Polling Mechanism

1. Client keeps track of `lastMessageId` (starts at 0)
2. Client calls `/poll?after_message_id=X`
3. Server queries for messages with `id > X`
4. If messages exist, return immediately
5. If no messages, wait up to 25 seconds, checking every 500ms
6. Return `204 No Content` if timeout reached
7. Client updates `lastMessageId` and polls again

### Database Schema

**conversations**
- id, type (direct/group), title, timestamps

**participants**
- id, conversation_id, user_id, joined_at, timestamps
- Unique index on (conversation_id, user_id)

**messages**
- id (auto-increment), conversation_id, sender_id, body, attachments, timestamps
- Index on (conversation_id, id) for efficient polling

**message_reads**
- id, message_id, user_id, read_at
- Unique index on (message_id, user_id)

### Performance Tips

1. The package uses indexed queries with monotonic IDs for efficient polling
2. Direct conversations are deduplicated automatically
3. Use the `before_message_id` parameter for pagination
4. Configure rate limits based on your needs
5. Adjust polling timeout and check interval for your use case

## Environment Variables

```env
DBCHAT_USER_MODEL=App\Models\User
DBCHAT_POLL_TIMEOUT=25
DBCHAT_POLL_CHECK_INTERVAL=500
DBCHAT_POLL_RATE_LIMIT=120
DBCHAT_MESSAGE_MAX_LENGTH=5000
DBCHAT_MESSAGE_PAGINATION_LIMIT=50
DBCHAT_MESSAGE_RATE_LIMIT=60
```

## Advanced: SSE (Server-Sent Events)

If you prefer SSE over long polling, you can create a custom controller:

```php
use Symfony\Component\HttpFoundation\StreamedResponse;

class SSEController extends Controller
{
    public function __invoke(Request $request, MessageService $messageService)
    {
        return new StreamedResponse(function () use ($request, $messageService) {
            $lastMessageId = $request->query('after_message_id', 0);
            $conversationIds = // ... get user's conversation IDs

            while (true) {
                $messages = $messageService->getNewMessages($conversationIds, $lastMessageId);
                
                if ($messages->isNotEmpty()) {
                    echo "data: " . json_encode([
                        'last_message_id' => $messages->last()->id,
                        'messages' => $messages,
                    ]) . "\n\n";
                    
                    ob_flush();
                    flush();
                    
                    $lastMessageId = $messages->last()->id;
                }
                
                sleep(1);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
```

## Testing

```bash
composer test
```

## Security

- All routes are protected by authentication middleware
- Users can only access conversations they're participants in
- Rate limiting prevents abuse
- Input validation on all endpoints

## License

MIT License

## Credits

Created by Martinko366

## Support

For issues and questions, please use the GitHub issue tracker.
