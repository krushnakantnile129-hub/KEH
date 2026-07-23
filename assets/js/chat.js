/* Knowledge Exchange Hub - Real-Time Chat Polling JS */
document.addEventListener('DOMContentLoaded', function () {
    const chatForm = document.getElementById('chatForm');
    const messageInput = document.getElementById('messageInput');
    const chatMessages = document.getElementById('chatMessages');
    
    if (!chatForm || !chatMessages) return;

    const requestId = chatForm.dataset.requestId;
    const receiverId = chatForm.dataset.receiverId;
    let lastMessageId = 0;

    function fetchMessages() {
        fetch(`/KEH/api/get-messages.php?request_id=${requestId}&last_id=${lastMessageId}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.messages.length > 0) {
                    data.messages.forEach(msg => {
                        appendMessageBubble(msg);
                        lastMessageId = Math.max(lastMessageId, msg.id);
                    });
                    scrollToBottom();
                }
            })
            .catch(err => console.error("Error fetching messages:", err));
    }

    function appendMessageBubble(msg) {
        const bubbleWrap = document.createElement('div');
        bubbleWrap.className = 'd-flex mb-3 ' + (msg.is_me ? 'justify-content-end' : 'justify-content-start');
        
        const bubble = document.createElement('div');
        bubble.className = 'chat-bubble ' + (msg.is_me ? 'chat-bubble-sent' : 'chat-bubble-received');
        
        bubble.innerHTML = `
            <div>${escapeHtml(msg.message)}</div>
            <span class="chat-time">${msg.time}</span>
        `;
        
        bubbleWrap.appendChild(bubble);
        chatMessages.appendChild(bubbleWrap);
    }

    function scrollToBottom() {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.innerText = str;
        return div.innerHTML;
    }

    chatForm.addEventListener('submit', function (e) {
        e.preventDefault();
        const text = messageInput.value.trim();
        if (!text) return;

        const formData = new FormData();
        formData.append('request_id', requestId);
        formData.append('receiver_id', receiverId);
        formData.append('message', text);

        messageInput.value = '';

        fetch('/KEH/api/send-message.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                fetchMessages();
            } else {
                alert("Could not send message: " + (data.error || 'Unknown error'));
            }
        })
        .catch(err => console.error("Send message error:", err));
    });

    // Initial fetch and start 2s polling
    fetchMessages();
    setInterval(fetchMessages, 2000);
});
