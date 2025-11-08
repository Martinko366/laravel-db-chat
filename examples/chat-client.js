/**
 * Laravel DB Chat Client
 * 
 * A simple JavaScript client for the Laravel DB Chat package.
 * This demonstrates how to implement long-polling on the frontend.
 */

class LaravelDbChatClient {
  constructor(config = {}) {
    this.baseUrl = config.baseUrl || '/api/dbchat';
    this.token = config.token || '';
    this.lastMessageId = 0;
    this.isPolling = false;
    this.onMessage = config.onMessage || (() => {});
    this.onError = config.onError || console.error;
    this.onStatusChange = config.onStatusChange || (() => {});
  }

  /**
   * Start long polling for new messages
   */
  async startPolling() {
    if (this.isPolling) {
      console.warn('Already polling');
      return;
    }

    this.isPolling = true;
    this.onStatusChange('polling');

    while (this.isPolling) {
      try {
        const response = await this.fetch(
          `${this.baseUrl}/poll?after_message_id=${this.lastMessageId}`
        );

        if (response.status === 200) {
          const data = await response.json();
          this.lastMessageId = data.last_message_id;
          
          if (data.messages && data.messages.length > 0) {
            data.messages.forEach(msg => this.onMessage(msg));
          }
        } else if (response.status === 204) {
          // No new messages, continue polling
        } else if (response.status === 401) {
          this.onError(new Error('Unauthorized'));
          this.stopPolling();
          break;
        }
      } catch (error) {
        this.onError(error);
        // Wait before retrying on error
        await this.sleep(5000);
      }
    }

    this.onStatusChange('stopped');
  }

  /**
   * Stop polling
   */
  stopPolling() {
    this.isPolling = false;
  }

  /**
   * Get all conversations
   */
  async getConversations() {
    const response = await this.fetch(`${this.baseUrl}/conversations`);
    return response.json();
  }

  /**
   * Create a new conversation
   */
  async createConversation(type, participants, title = null) {
    const response = await this.fetch(`${this.baseUrl}/conversations`, {
      method: 'POST',
      body: JSON.stringify({ type, participants, title })
    });
    return response.json();
  }

  /**
   * Get a specific conversation
   */
  async getConversation(conversationId) {
    const response = await this.fetch(`${this.baseUrl}/conversations/${conversationId}`);
    return response.json();
  }

  /**
   * Get messages for a conversation
   */
  async getMessages(conversationId, beforeMessageId = null, limit = 50) {
    let url = `${this.baseUrl}/conversations/${conversationId}/messages?limit=${limit}`;
    if (beforeMessageId) {
      url += `&before_message_id=${beforeMessageId}`;
    }
    
    const response = await this.fetch(url);
    return response.json();
  }

  /**
   * Send a message
   */
  async sendMessage(conversationId, body, attachments = null) {
    const response = await this.fetch(
      `${this.baseUrl}/conversations/${conversationId}/messages`,
      {
        method: 'POST',
        body: JSON.stringify({ body, attachments })
      }
    );
    return response.json();
  }

  /**
   * Mark a message as read
   */
  async markAsRead(messageId) {
    const response = await this.fetch(
      `${this.baseUrl}/messages/${messageId}/read`,
      { method: 'POST' }
    );
    return response.status === 204;
  }

  /**
   * Add a participant to a group conversation
   */
  async addParticipant(conversationId, userId) {
    const response = await this.fetch(
      `${this.baseUrl}/conversations/${conversationId}/participants`,
      {
        method: 'POST',
        body: JSON.stringify({ user_id: userId })
      }
    );
    return response.json();
  }

  /**
   * Remove a participant from a group conversation
   */
  async removeParticipant(conversationId, userId) {
    const response = await this.fetch(
      `${this.baseUrl}/conversations/${conversationId}/participants/${userId}`,
      { method: 'DELETE' }
    );
    return response.status === 204;
  }

  /**
   * Internal fetch wrapper with authentication
   */
  async fetch(url, options = {}) {
    const defaultOptions = {
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${this.token}`
      }
    };

    return fetch(url, { ...defaultOptions, ...options });
  }

  /**
   * Sleep utility
   */
  sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
  }
}

// Export for use in modules
if (typeof module !== 'undefined' && module.exports) {
  module.exports = LaravelDbChatClient;
}

// Example usage:
/*
const chat = new LaravelDbChatClient({
  baseUrl: 'https://yourapp.com/api/dbchat',
  token: 'your-sanctum-token',
  onMessage: (message) => {
    console.log('New message:', message);
    // Update your UI here
  },
  onError: (error) => {
    console.error('Chat error:', error);
  },
  onStatusChange: (status) => {
    console.log('Polling status:', status);
  }
});

// Start listening for new messages
chat.startPolling();

// Send a message
await chat.sendMessage(1, 'Hello, world!');

// Get conversation history
const { messages } = await chat.getMessages(1);

// Stop polling when done
chat.stopPolling();
*/
