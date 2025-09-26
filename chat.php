<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$auth->requireLogin();
$user = $auth->getCurrentUser();

$course_id = (int)($_GET['course_id'] ?? 0);

// Verify user has access to this course
if ($user['role'] === 'teacher') {
    $course = $db->fetchOne("SELECT * FROM courses WHERE id = ? AND teacher_id = ?", [$course_id, $user['id']]);
} else {
    $course = $db->fetchOne("SELECT c.* FROM courses c JOIN enrollments e ON c.id = e.course_id WHERE c.id = ? AND e.student_id = ?", [$course_id, $user['id']]);
}

if (!$course) redirect('/dashboard.php');

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    
    if ($action === 'send_message') {
        $message = sanitizeInput($_POST['message'] ?? '');
        if (!empty($message)) {
            try {
                $db->executeQuery("INSERT INTO chat_messages (course_id, user_id, message) VALUES (?, ?, ?)", [$course_id, $user['id'], $message]);
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
                "SELECT cm.*, u.full_name, u.role FROM chat_messages cm JOIN users u ON cm.user_id = u.id WHERE cm.course_id = ? AND cm.id > ? ORDER BY cm.created_at ASC LIMIT 50",
                [$course_id, $last_id]
            );
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
                "SELECT DISTINCT u.full_name, u.role FROM user_sessions us JOIN users u ON us.user_id = u.id WHERE us.course_id = ? AND us.is_online = 1 AND us.last_activity > DATE_SUB(NOW(), INTERVAL 2 MINUTE) ORDER BY u.role DESC, u.full_name",
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
    $db->executeQuery("DELETE FROM user_sessions WHERE last_activity < DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
    
    $existing = $db->fetchOne("SELECT id FROM user_sessions WHERE user_id = ? AND course_id = ? AND session_id = ?", [$user_id, $course_id, $session_id]);
    
    if ($existing) {
        $db->executeQuery("UPDATE user_sessions SET last_activity = NOW(), is_online = 1 WHERE id = ?", [$existing['id']]);
    } else {
        $db->executeQuery("INSERT INTO user_sessions (user_id, course_id, session_id, is_online) VALUES (?, ?, ?, 1) ON DUPLICATE KEY UPDATE last_activity = NOW(), is_online = 1", [$user_id, $course_id, $session_id]);
    }
}

// Get initial messages
$messages = $db->fetchAll(
    "SELECT cm.*, u.full_name, u.role FROM chat_messages cm JOIN users u ON cm.user_id = u.id WHERE cm.course_id = ? ORDER BY cm.created_at DESC LIMIT 50",
    [$course_id]
);
$messages = array_reverse($messages);
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
        tailwind.config = {theme: {extend: {colors: {primary: '#2563eb', secondary: '#10b981', accent: '#f59e0b'}}}}
    </script>
</head>
<body class="bg-gray-50">
    <!-- Header styled like Railway Management System -->
    <nav class="bg-white shadow-md border-b">
        <div class="max-w-6xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                        <span class="text-white font-bold text-xl">💬</span>
                    </div>
                    <h1 class="text-xl font-bold text-gray-800">E-Learning System</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="text-blue-600 hover:text-blue-800 font-medium">Dashboard</a>
                    <span class="text-gray-700"><?php echo ucfirst($user['role']); ?>: <?php echo htmlspecialchars($user['full_name']); ?></span>
                    <a href="logout.php" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-6xl mx-auto px-4 py-8">
        <!-- Course Info -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white rounded-xl shadow-lg p-6 mb-6">
            <h1 class="text-2xl font-bold mb-2"><?php echo htmlspecialchars($course['title']); ?></h1>
            <p class="text-blue-100"><?php echo htmlspecialchars($course['course_code']); ?> • Real-time class discussion</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Chat Messages -->
            <div class="lg:col-span-3">
                <div class="bg-white rounded-xl shadow-lg border border-gray-200">
                    <!-- Messages Area -->
                    <div id="messagesContainer" class="h-96 overflow-y-auto p-4 space-y-4 bg-gray-50">
                        <?php foreach($messages as $message): 
                            $roleColor = $message['role'] === 'teacher' ? 'purple' : 'blue';
                            $initials = strtoupper(substr($message['full_name'], 0, 1));
                        ?>
                            <div class="message" data-id="<?php echo $message['id']; ?>">
                                <div class="flex items-start space-x-3">
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 bg-<?php echo $roleColor; ?>-500 rounded-full flex items-center justify-center">
                                            <span class="text-white font-bold"><?php echo $initials; ?></span>
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm text-gray-600 mb-1">
                                            <span class="font-bold text-gray-800"><?php echo htmlspecialchars($message['full_name']); ?></span>
                                            <span class="text-<?php echo $roleColor; ?>-600 ml-1">(<?php echo ucfirst($message['role']); ?>)</span>
                                            <span class="text-gray-400 ml-2"><?php echo formatDateTime($message['created_at']); ?></span>
                                        </div>
                                        <div class="bg-white rounded-lg p-3 border border-gray-200">
                                            <p class="text-gray-800"><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Message Input -->
                    <div class="border-t p-4 bg-white">
                        <form id="messageForm" class="flex space-x-3">
                            <input type="text" id="messageInput" 
                                   class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                   placeholder="Type your message..." maxlength="500">
                            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 font-medium">
                                Send
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Online Users Sidebar -->
            <div class="lg:col-span-1 space-y-6">
                <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">👥 Online Now</h3>
                    <div id="onlineUsers" class="space-y-3">
                        <div class="text-gray-500 text-sm">Loading...</div>
                    </div>
                </div>

                <!-- Chat Guidelines -->
                <div class="bg-yellow-50 border border-yellow-300 rounded-xl p-4">
                    <h4 class="font-bold text-yellow-800 mb-2">💡 Chat Guidelines</h4>
                    <ul class="text-sm text-yellow-700 space-y-1">
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
                this.messageInput.focus();
            }
            
            setupEventListeners() {
                this.messageForm.addEventListener('submit', (e) => {
                    e.preventDefault();
                    this.sendMessage();
                });
                
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
                
                this.messageInput.disabled = true;
                
                fetch(`chat.php?course_id=${this.courseId}`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `action=send_message&message=${encodeURIComponent(message)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.messageInput.value = '';
                        this.getNewMessages();
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
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `action=get_messages&last_id=${this.lastMessageId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.messages.length > 0) {
                        this.addNewMessages(data.messages);
                    }
                })
                .catch(error => console.error('Error fetching messages:', error));
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
                            <div class="w-10 h-10 bg-${roleColor}-500 rounded-full flex items-center justify-center">
                                <span class="text-white font-bold">${initials}</span>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm text-gray-600 mb-1">
                                <span class="font-bold text-gray-800">${this.escapeHtml(message.full_name)}</span>
                                <span class="text-${roleColor}-600 ml-1">(${message.role.charAt(0).toUpperCase() + message.role.slice(1)})</span>
                                <span class="text-gray-400 ml-2">${this.formatDateTime(message.created_at)}</span>
                            </div>
                            <div class="bg-white rounded-lg p-3 border border-gray-200">
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
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=get_online_users'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) this.updateOnlineUsers(data.users);
                })
                .catch(error => console.error('Error fetching online users:', error));
            }
            
            updateOnlineUsers(users) {
                if (users.length === 0) {
                    this.onlineUsersDiv.innerHTML = '<div class="text-gray-500 text-sm">No one online</div>';
                    return;
                }
                
                this.onlineUsersDiv.innerHTML = users.map(user => {
                    const roleIcon = user.role === 'teacher' ? '👨‍🏫' : (user.role === 'admin' ? '👑' : '👨‍🎓');
                    return `
                        <div class="flex items-center space-x-3">
                            <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                            <span class="text-gray-700">${roleIcon} ${this.escapeHtml(user.full_name)}</span>
                        </div>
                    `;
                }).join('');
            }
            
            scrollToBottom() {
                this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
            }
            
            startPolling() {
                setInterval(() => this.getNewMessages(), 3000);
                setInterval(() => this.getOnlineUsers(), 10000);
                this.getOnlineUsers();
            }
            
            escapeHtml(text) {
                const map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
                return text.replace(/[&<>"']/g, m => map[m]);
            }
            
            formatDateTime(dateString) {
                return new Date(dateString).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            }
        }
        
        document.addEventListener('DOMContentLoaded', () => new ChatSystem());
    </script>
</body>
</html>