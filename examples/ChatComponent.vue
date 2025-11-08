<template>
  <div class="chat-container">
    <!-- Conversation List -->
    <div class="conversations-sidebar">
      <h3>Conversations</h3>
      <div 
        v-for="conv in conversations" 
        :key="conv.id"
        @click="selectConversation(conv.id)"
        :class="{ active: currentConversationId === conv.id }"
        class="conversation-item"
      >
        <div class="conversation-title">
          {{ conv.title || `Conversation #${conv.id}` }}
        </div>
        <div v-if="conv.latest_message" class="conversation-preview">
          {{ conv.latest_message.body }}
        </div>
      </div>
      
      <button @click="showCreateConversation = true" class="btn-new">
        New Conversation
      </button>
    </div>

    <!-- Messages Area -->
    <div class="messages-area" v-if="currentConversationId">
      <div class="messages-header">
        <h3>{{ currentConversation?.title || 'Chat' }}</h3>
        <div class="status-indicator" :class="pollingStatus">
          {{ pollingStatus }}
        </div>
      </div>

      <div class="messages-list" ref="messagesList">
        <div 
          v-for="message in messages" 
          :key="message.id"
          :class="{ 'message-own': message.sender_id === currentUserId }"
          class="message"
        >
          <div class="message-sender">
            {{ message.sender?.name || `User ${message.sender_id}` }}
          </div>
          <div class="message-body">{{ message.body }}</div>
          <div class="message-time">
            {{ formatTime(message.created_at) }}
          </div>
        </div>
      </div>

      <div class="message-input">
        <textarea 
          v-model="newMessage"
          @keydown.ctrl.enter="sendMessage"
          placeholder="Type a message... (Ctrl+Enter to send)"
          rows="3"
        ></textarea>
        <button @click="sendMessage" :disabled="!newMessage.trim()">
          Send
        </button>
      </div>
    </div>

    <!-- Create Conversation Modal -->
    <div v-if="showCreateConversation" class="modal">
      <div class="modal-content">
        <h3>New Conversation</h3>
        <select v-model="newConvType">
          <option value="direct">Direct Message</option>
          <option value="group">Group Chat</option>
        </select>
        
        <input 
          v-if="newConvType === 'group'"
          v-model="newConvTitle"
          placeholder="Group name"
        />
        
        <input 
          v-model="newConvParticipants"
          placeholder="User IDs (comma separated)"
        />
        
        <div class="modal-actions">
          <button @click="createConversation">Create</button>
          <button @click="showCreateConversation = false">Cancel</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed, nextTick } from 'vue';
import axios from 'axios';

// Props
const props = defineProps({
  baseUrl: {
    type: String,
    default: '/api/dbchat'
  },
  currentUserId: {
    type: Number,
    required: true
  }
});

// State
const conversations = ref([]);
const currentConversationId = ref(null);
const messages = ref([]);
const newMessage = ref('');
const lastMessageId = ref(0);
const pollingStatus = ref('stopped');
const showCreateConversation = ref(false);
const newConvType = ref('direct');
const newConvTitle = ref('');
const newConvParticipants = ref('');

// Refs
const messagesList = ref(null);

// Computed
const currentConversation = computed(() => {
  return conversations.value.find(c => c.id === currentConversationId.value);
});

// Polling control
let isPolling = false;

// Methods
const loadConversations = async () => {
  try {
    const response = await axios.get(`${props.baseUrl}/conversations`);
    conversations.value = response.data.conversations;
  } catch (error) {
    console.error('Failed to load conversations:', error);
  }
};

const selectConversation = async (conversationId) => {
  currentConversationId.value = conversationId;
  await loadMessages(conversationId);
};

const loadMessages = async (conversationId, beforeMessageId = null) => {
  try {
    let url = `${props.baseUrl}/conversations/${conversationId}/messages?limit=50`;
    if (beforeMessageId) {
      url += `&before_message_id=${beforeMessageId}`;
    }

    const response = await axios.get(url);
    messages.value = response.data.messages;
    
    // Set lastMessageId to the highest ID
    if (messages.value.length > 0) {
      lastMessageId.value = Math.max(...messages.value.map(m => m.id));
    }
    
    await nextTick();
    scrollToBottom();
  } catch (error) {
    console.error('Failed to load messages:', error);
  }
};

const sendMessage = async () => {
  if (!newMessage.value.trim() || !currentConversationId.value) return;

  try {
    const response = await axios.post(
      `${props.baseUrl}/conversations/${currentConversationId.value}/messages`,
      { body: newMessage.value }
    );

    // Add message to local state
    messages.value.push(response.data.message);
    lastMessageId.value = response.data.message.id;
    
    newMessage.value = '';
    
    await nextTick();
    scrollToBottom();
  } catch (error) {
    console.error('Failed to send message:', error);
  }
};

const createConversation = async () => {
  try {
    const participants = newConvParticipants.value
      .split(',')
      .map(id => parseInt(id.trim()))
      .filter(id => !isNaN(id));

    const data = {
      type: newConvType.value,
      participants: participants
    };

    if (newConvType.value === 'group') {
      data.title = newConvTitle.value;
    }

    const response = await axios.post(`${props.baseUrl}/conversations`, data);
    conversations.value.push(response.data.conversation);
    
    // Reset form
    showCreateConversation.value = false;
    newConvType.value = 'direct';
    newConvTitle.value = '';
    newConvParticipants.value = '';
    
    // Select the new conversation
    selectConversation(response.data.conversation.id);
  } catch (error) {
    console.error('Failed to create conversation:', error);
  }
};

const poll = async () => {
  isPolling = true;
  pollingStatus.value = 'polling';

  while (isPolling) {
    try {
      const response = await axios.get(
        `${props.baseUrl}/poll?after_message_id=${lastMessageId.value}`
      );

      if (response.status === 200) {
        const data = response.data;
        lastMessageId.value = data.last_message_id;

        // Add new messages to current conversation
        if (data.messages && data.messages.length > 0) {
          data.messages.forEach(msg => {
            if (msg.conversation_id === currentConversationId.value) {
              messages.value.push(msg);
            }
          });

          await nextTick();
          scrollToBottom();
        }
      }
    } catch (error) {
      if (error.response?.status === 401) {
        console.error('Unauthorized');
        stopPolling();
        break;
      }
      
      console.error('Polling error:', error);
      await sleep(5000);
    }
  }

  pollingStatus.value = 'stopped';
};

const stopPolling = () => {
  isPolling = false;
};

const scrollToBottom = () => {
  if (messagesList.value) {
    messagesList.value.scrollTop = messagesList.value.scrollHeight;
  }
};

const formatTime = (timestamp) => {
  return new Date(timestamp).toLocaleTimeString();
};

const sleep = (ms) => {
  return new Promise(resolve => setTimeout(resolve, ms));
};

// Lifecycle
onMounted(async () => {
  await loadConversations();
  poll();
});

onUnmounted(() => {
  stopPolling();
});
</script>

<style scoped>
.chat-container {
  display: flex;
  height: 600px;
  border: 1px solid #ddd;
  border-radius: 8px;
  overflow: hidden;
}

.conversations-sidebar {
  width: 300px;
  border-right: 1px solid #ddd;
  display: flex;
  flex-direction: column;
  background: #f5f5f5;
}

.conversations-sidebar h3 {
  padding: 1rem;
  margin: 0;
  border-bottom: 1px solid #ddd;
  background: white;
}

.conversation-item {
  padding: 1rem;
  border-bottom: 1px solid #ddd;
  cursor: pointer;
  transition: background 0.2s;
}

.conversation-item:hover {
  background: #e9e9e9;
}

.conversation-item.active {
  background: #007bff;
  color: white;
}

.conversation-title {
  font-weight: bold;
  margin-bottom: 0.25rem;
}

.conversation-preview {
  font-size: 0.875rem;
  color: #666;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.conversation-item.active .conversation-preview {
  color: rgba(255, 255, 255, 0.8);
}

.btn-new {
  margin: 1rem;
  padding: 0.5rem 1rem;
  background: #007bff;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.messages-area {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.messages-header {
  padding: 1rem;
  border-bottom: 1px solid #ddd;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: white;
}

.messages-header h3 {
  margin: 0;
}

.status-indicator {
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.75rem;
  text-transform: uppercase;
}

.status-indicator.polling {
  background: #28a745;
  color: white;
}

.status-indicator.stopped {
  background: #dc3545;
  color: white;
}

.messages-list {
  flex: 1;
  overflow-y: auto;
  padding: 1rem;
  background: #fafafa;
}

.message {
  margin-bottom: 1rem;
  padding: 0.75rem;
  background: white;
  border-radius: 8px;
  max-width: 70%;
}

.message-own {
  margin-left: auto;
  background: #007bff;
  color: white;
}

.message-sender {
  font-weight: bold;
  font-size: 0.875rem;
  margin-bottom: 0.25rem;
}

.message-body {
  margin-bottom: 0.25rem;
}

.message-time {
  font-size: 0.75rem;
  opacity: 0.7;
}

.message-input {
  padding: 1rem;
  border-top: 1px solid #ddd;
  display: flex;
  gap: 0.5rem;
  background: white;
}

.message-input textarea {
  flex: 1;
  padding: 0.5rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  resize: vertical;
}

.message-input button {
  padding: 0.5rem 1.5rem;
  background: #007bff;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.message-input button:disabled {
  background: #ccc;
  cursor: not-allowed;
}

.modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
}

.modal-content {
  background: white;
  padding: 2rem;
  border-radius: 8px;
  width: 400px;
  max-width: 90%;
}

.modal-content h3 {
  margin-top: 0;
}

.modal-content select,
.modal-content input {
  width: 100%;
  padding: 0.5rem;
  margin-bottom: 1rem;
  border: 1px solid #ddd;
  border-radius: 4px;
}

.modal-actions {
  display: flex;
  gap: 0.5rem;
  justify-content: flex-end;
}

.modal-actions button {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.modal-actions button:first-child {
  background: #007bff;
  color: white;
}

.modal-actions button:last-child {
  background: #6c757d;
  color: white;
}
</style>
