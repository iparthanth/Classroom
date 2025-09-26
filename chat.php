<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$auth->requireLogin();
$user = $auth->getCurrentUser();

$course_id = (int)($_GET['course_id'] ?? 0);

// Verify user has access to this course
if ($user['role'] === 'teacher') {
    $course = $db->fetchOne(
        "SELECT * FROM courses WHERE id = ? AND teacher_id = ?",
        [$course_id, $user['id']]
    );
} else {
    $course = $db->fetchOne(
        "SELECT c.* FROM courses c 
         JOIN enrollments e ON c.id = e.course_id 
         WHERE c.id = ? AND e.student_id = ?",
        [$course_id, $user['id']]
    );
}

if (!$course) {
    redirect('/dashboard.php');
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'send_message') {
        $message = sanitizeInput($_POST['message'] ?? '');
        
        if (!empty($message)) {
            try {
                $db->executeQuery(
                    "INSERT INTO chat_messages (course_id, user_id, message) VALUES (?, ?, ?)",
                    [$course_id, $user['id'], $message]
                );
                
                // Update user presence
                updateUserPresence($db, $user['id'], $course_id);
                
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Message cannot be empty']);
        }
        exit;
    }
    
    if ($action === 'get_messages') {
        $last_id = (int)($_POST['last_id'] ?? 0);
        
        try {
            $messages = $db->fetchAll(
                "SELECT cm.*, u.full_name, u.role 
                 FROM chat_messages cm 
                 JOIN users u ON cm.user_id = u.id 
                 WHERE cm.course_id = ? AND cm.id > ? 
                 ORDER BY cm.created_at ASC 
                 LIMIT 50",
                [$course_id, $last_id]
            );
            
            // Update user presence
            updateUserPresence($db, $user['id'], $course_id);
            
            echo json_encode(['success' => true, 'messages' => $messages]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
    
    if ($action === 'get_online_users') {
        try {
            $online_users = $db->fetchAll(
                "SELECT DISTINCT u.full_name, u.role 
                 FROM user_sessions us 
                 JOIN users u ON us.user_id = u.id 
                 WHERE us.course_id = ? AND us.is_online = 1 
                 AND us.last_activity > DATE_SUB(NOW(), INTERVAL 2 MINUTE)
                 ORDER BY u.role DESC, u.full_name",
                [$course_id]
            );
            
            echo json_encode(['success' => true, 'users' => $online_users]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
}

// Function to update user presence
function updateUserPresence($db, $user_id, $course_id) {
    $session_id = session_id();
    
    // Clean old sessions first
    $db->executeQuery(
        "DELETE FROM user_sessions WHERE last_activity < DATE_SUB(NOW(), INTERVAL 5 MINUTE)"
    );
    
    // Update or insert current session
    $existing = $db->fetchOne(
        "SELECT id FROM user_sessions WHERE user_id = ? AND course_id = ? AND session_id = ?",
        [$user_id, $course_id, $session_id]
    );
    
    if ($existing) {
        $db->executeQuery(
            "UPDATE user_sessions SET last_activity = NOW(), is_online = 1 WHERE id = ?",
            [$existing['id']]
        );
    } else {
        $db->executeQuery(
            "INSERT INTO user_sessions (user_id, course_id, session_id, is_online) VALUES (?, ?, ?, 1) 
             ON DUPLICATE KEY UPDATE last_activity = NOW(), is_online = 1",
            [$user_id, $course_id, $session_id]
        );
    }
}

// Get initial messages
$messages = $db->fetchAll(
    "SELECT cm.*, u.full_name, u.role 
     FROM chat_messages cm 
     JOIN users u ON cm.user_id = u.id 
     WHERE cm.course_id = ? 
     ORDER BY cm.created_at DESC 
     LIMIT 50",
    [$course_id]
);

$messages = array_reverse($messages);

// Update presence
updateUserPresence($db, $user['id'], $course_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - <?php echo htmlspecialchars($course['title']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#2563eb',
                        secondary: '#10b981',
                        accent: '#f59e0b'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-6xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 bg-blue-600 rounded flex items-center justify-center">
                        <span class="text-white font-bold text-xl">💬</span>
                    </div>
                    <h1 class="text-xl font-bold text-gray-800">Chat - <?php echo htmlspecialchars($course['course_code']); ?></h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-600"><?php echo ucfirst($user['role']); ?>: <?php echo htmlspecialchars($user['full_name']); ?></span>
                    <a href="dashboard.php" class="text-gray-600 hover:text-gray-800">Back</a>
                    <a href="logout.php" class="text-red-600 hover:text-red-800">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-6xl mx-auto px-4 py-6">
        <!-- Course Info -->
        <div class="bg-white rounded-lg shadow p-4 mb-4">
            <h2 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($course['title']); ?></h2>
            <p class="text-sm text-gray-600">Real-time class discussion</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Chat Messages -->
            <div class="lg:col-span-3">
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <!-- Messages Area -->
                    <div id="messagesContainer" class="h-96 overflow-y-auto p-4 space-y-3 bg-gray-50">
                        <?php foreach($messages as $message): ?>
                            <div class="message" data-id="<?php echo $message['id']; ?>">
                                <div class="flex items-start space-x-3">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 bg-<?php echo $message['role'] === 'teacher' ? 'purple' : 'blue'; ?>-500 rounded-full flex items-center justify-center">
                                            <span class="text-white text-xs font-bold">
                                                <?php echo strtoupper(substr($message['full_name'], 0, 1)); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-xs text-gray-500 mb-1">
                                            <span class="font-medium text-gray-900"><?php echo htmlspecialchars($message['full_name']); ?></span>
                                            <span class="text-<?php echo $message['role'] === 'teacher' ? 'purple' : 'blue'; ?>-600">
                                                (<?php echo ucfirst($message['role']); ?>)
                                            </span>
                                            • <span><?php echo formatDateTime($message['created_at']); ?></span>
                                        </div>
                                        <div class="bg-white rounded-lg p-3 shadow-sm">
                                            <p class="text-gray-800"><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Message Input -->
                    <div class="border-t p-4 bg-white">
                        <form id="messageForm" class="flex space-x-2">
                            <input 
                                type="text" 
                                id="messageInput" 
                                class="flex-1 border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                placeholder="Type your message..."
                                maxlength="500"
                            >
                            <button 
                                type="submit" 
                                class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                                Send
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Online Users Sidebar -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow p-4">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Online Now</h3>
                    <div id="onlineUsers" class="space-y-2">
                        <div class="text-sm text-gray-500">Loading...</div>
                    </div>
                </div>

                <!-- Chat Guidelines -->
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mt-4">
                    <h4 class="text-sm font-semibold text-yellow-800 mb-2">💡 Chat Guidelines</h4>
                    <ul class="text-xs text-yellow-700 space-y-1">
                        <li>• Keep discussions course-related</li>
                        <li>• Be respectful to everyone</li>
                        <li>• Ask questions freely</li>
                        <li>• Help your classmates</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script>
        class ChatSystem {
            constructor() {
                this.courseId = <?php echo $course_id; ?>;
                this.lastMessageId = <?php echo !empty($messages) ? end($messages)['id'] : 0; ?>;
                this.messagesContainer = document.getElementById('messagesContainer');
                this.messageForm = document.getElementById('messageForm');
                this.messageInput = document.getElementById('messageInput');
                this.onlineUsersDiv = document.getElementById('onlineUsers');
                
                this.setupEventListeners();
                this.startPolling();
                this.scrollToBottom();
                
                // Focus on input
                this.messageInput.focus();
            }
            
            setupEventListeners() {
                this.messageForm.addEventListener('submit', (e) => {
                    e.preventDefault();
                    this.sendMessage();
                });
                
                // Auto-resize and send on Enter
                this.messageInput.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        this.sendMessage();
                    }
                });
            }
            
            sendMessage() {
                const message = this.messageInput.value.trim();
                if (!message) return;
                
                // Disable input while sending
                this.messageInput.disabled = true;
                
                fetch(`chat.php?course_id=${this.courseId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=send_message&message=${encodeURIComponent(message)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.messageInput.value = '';
                        this.getNewMessages(); // Immediately check for new messages
                    } else {
                        alert('Failed to send message: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error sending message:', error);
                    alert('Failed to send message');
                })
                .finally(() => {
                    this.messageInput.disabled = false;
                    this.messageInput.focus();
                });
            }
            
            getNewMessages() {
                fetch(`chat.php?course_id=${this.courseId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=get_messages&last_id=${this.lastMessageId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.messages.length > 0) {
                        this.addNewMessages(data.messages);
                    }
                })
                .catch(error => {
                    console.error('Error fetching messages:', error);
                });
            }
            
            addNewMessages(messages) {
                messages.forEach(message => {
                    const messageDiv = this.createMessageElement(message);
                    this.messagesContainer.appendChild(messageDiv);
                    this.lastMessageId = Math.max(this.lastMessageId, message.id);
                });
                
                this.scrollToBottom();
            }
            
            createMessageElement(message) {
                const div = document.createElement('div');
                div.className = 'message';
                div.setAttribute('data-id', message.id);
                
                const roleColor = message.role === 'teacher' ? 'purple' : 'blue';
                const initials = message.full_name.charAt(0).toUpperCase();
                
                div.innerHTML = `
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-${roleColor}-500 rounded-full flex items-center justify-center">
                                <span class="text-white text-xs font-bold">${initials}</span>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-xs text-gray-500 mb-1">
                                <span class="font-medium text-gray-900">${this.escapeHtml(message.full_name)}</span>
                                <span class="text-${roleColor}-600">(${message.role.charAt(0).toUpperCase() + message.role.slice(1)})</span>
                                • <span>${this.formatDateTime(message.created_at)}</span>
                            </div>
                            <div class="bg-white rounded-lg p-3 shadow-sm">
                                <p class="text-gray-800">${this.escapeHtml(message.message).replace(/\n/g, '<br>')}</p>
                            </div>
                        </div>
                    </div>
                `;
                
                return div;
            }
            
            getOnlineUsers() {
                fetch(`chat.php?course_id=${this.courseId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=get_online_users'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.updateOnlineUsers(data.users);
                    }
                })
                .catch(error => {
                    console.error('Error fetching online users:', error);
                });
            }
            
            updateOnlineUsers(users) {
                if (users.length === 0) {
                    this.onlineUsersDiv.innerHTML = '<div class="text-sm text-gray-500">No one online</div>';
                    return;
                }
                
                this.onlineUsersDiv.innerHTML = users.map(user => {
                    const roleColor = user.role === 'teacher' ? 'purple' : (user.role === 'admin' ? 'red' : 'blue');
                    const roleIcon = user.role === 'teacher' ? '👨‍🏫' : (user.role === 'admin' ? '👑' : '👨‍🎓');
                    
                    return `
                        <div class="flex items-center space-x-2">
                            <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                            <span class="text-sm text-gray-700">${roleIcon} ${this.escapeHtml(user.full_name)}</span>
                        </div>
                    `;
                }).join('');
            }
            
            scrollToBottom() {
                this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
            }
            
            startPolling() {
                // Poll for new messages every 3 seconds
                setInterval(() => {
                    this.getNewMessages();
                }, 3000);
                
                // Update online users every 10 seconds
                setInterval(() => {
                    this.getOnlineUsers();
                }, 10000);
                
                // Initial online users load
                this.getOnlineUsers();
            }
            
            escapeHtml(text) {
                const map = {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                };
                return text.replace(/[&<>"']/g, (m) => map[m]);
            }
            
            formatDateTime(dateString) {
                const date = new Date(dateString);
                return date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            }
        }
        
        // Initialize chat when page loads
        document.addEventListener('DOMContentLoaded', () => {
            new ChatSystem();
        });
        
        // Handle page visibility change to pause/resume polling when tab is not active
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                // Could pause polling here if needed
            } else {
                // Resume polling or force refresh
            }
        });
    </script>
</body>
</html>
