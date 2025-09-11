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

// Handle saving whiteboard data (teachers only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user['role'] === 'teacher') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_whiteboard') {
        $title = sanitizeInput($_POST['title'] ?? 'Whiteboard Session');
        $session_data = $_POST['session_data'] ?? '';
        
        try {
            // Check if session exists
            $existing = $db->fetchOne(
                "SELECT id FROM whiteboard_sessions WHERE course_id = ? AND teacher_id = ? ORDER BY created_at DESC LIMIT 1",
                [$course_id, $user['id']]
            );
            
            if ($existing) {
                // Update existing session
                $db->query(
                    "UPDATE whiteboard_sessions SET session_data = ?, is_active = 1 WHERE id = ?",
                    [$session_data, $existing['id']]
                );
            } else {
                // Create new session
                $db->query(
                    "INSERT INTO whiteboard_sessions (course_id, teacher_id, title, session_data) VALUES (?, ?, ?, ?)",
                    [$course_id, $user['id'], $title, $session_data]
                );
            }
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
    
    if ($action === 'load_whiteboard') {
        $session = $db->fetchOne(
            "SELECT * FROM whiteboard_sessions WHERE course_id = ? ORDER BY created_at DESC LIMIT 1",
            [$course_id]
        );
        
        echo json_encode([
            'success' => true, 
            'data' => $session ? $session['session_data'] : ''
        ]);
        exit;
    }
}

// Load existing whiteboard data
$whiteboard_data = '';
$latest_session = $db->fetchOne(
    "SELECT * FROM whiteboard_sessions WHERE course_id = ? ORDER BY created_at DESC LIMIT 1",
    [$course_id]
);

if ($latest_session) {
    $whiteboard_data = $latest_session['session_data'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interactive Whiteboard - <?php echo htmlspecialchars($course['title']); ?></title>
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
        <div class="max-w-full px-4">
            <div class="flex justify-between items-center py-3">
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 bg-green-600 rounded flex items-center justify-center">
                        <span class="text-white font-bold text-xl">🎨</span>
                    </div>
                    <h1 class="text-lg font-bold text-gray-800">Whiteboard - <?php echo htmlspecialchars($course['course_code']); ?></h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-600"><?php echo ucfirst($user['role']); ?>: <?php echo htmlspecialchars($user['full_name']); ?></span>
                    <a href="dashboard.php" class="text-gray-600 hover:text-gray-800 text-sm">Back</a>
                    <a href="logout.php" class="text-red-600 hover:text-red-800 text-sm">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Whiteboard Tools -->
    <div class="bg-white border-b px-4 py-2">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <?php if ($user['role'] === 'teacher'): ?>
                    <!-- Drawing Tools (Teachers Only) -->
                    <div class="flex items-center space-x-2">
                        <button id="penTool" class="bg-blue-500 text-white px-3 py-1 rounded text-sm">✏️ Pen</button>
                        <button id="eraserTool" class="bg-gray-500 text-white px-3 py-1 rounded text-sm">🗑️ Eraser</button>
                        
                        <select id="colorPicker" class="border rounded px-2 py-1 text-sm">
                            <option value="#000000">Black</option>
                            <option value="#ff0000">Red</option>
                            <option value="#00ff00">Green</option>
                            <option value="#0000ff">Blue</option>
                            <option value="#ffff00">Yellow</option>
                            <option value="#ff00ff">Magenta</option>
                        </select>
                        
                        <select id="brushSize" class="border rounded px-2 py-1 text-sm">
                            <option value="2">Fine (2px)</option>
                            <option value="4" selected>Normal (4px)</option>
                            <option value="8">Thick (8px)</option>
                            <option value="12">Very Thick (12px)</option>
                        </select>
                    </div>
                    
                    <div class="flex items-center space-x-2">
                        <button id="clearBoard" class="bg-red-500 text-white px-3 py-1 rounded text-sm">🗑️ Clear All</button>
                        <button id="saveBoard" class="bg-green-500 text-white px-3 py-1 rounded text-sm">💾 Save</button>
                    </div>
                <?php else: ?>
                    <div class="text-sm text-gray-600">
                        👀 View-only mode - Teacher can draw on the board
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="text-sm text-gray-500">
                Course: <?php echo htmlspecialchars($course['title']); ?>
            </div>
        </div>
    </div>

    <!-- Whiteboard Canvas -->
    <div class="p-4">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <canvas 
                id="whiteboard" 
                width="1200" 
                height="800" 
                class="border cursor-crosshair block w-full"
                style="max-width: 100%; height: auto;"
            ></canvas>
        </div>
    </div>

    <script>
        class Whiteboard {
            constructor() {
                this.canvas = document.getElementById('whiteboard');
                this.ctx = this.canvas.getContext('2d');
                this.isDrawing = false;
                this.currentTool = 'pen';
                this.currentColor = '#000000';
                this.currentSize = 4;
                this.strokes = [];
                this.isTeacher = <?php echo json_encode($user['role'] === 'teacher'); ?>;
                
                this.initializeCanvas();
                this.setupEventListeners();
                this.loadWhiteboard();
                
                // Auto-save for teachers every 30 seconds
                if (this.isTeacher) {
                    setInterval(() => this.saveWhiteboard(), 30000);
                }
            }
            
            initializeCanvas() {
                this.ctx.lineCap = 'round';
                this.ctx.lineJoin = 'round';
                this.ctx.imageSmoothingEnabled = true;
                
                // Set canvas background to white
                this.ctx.fillStyle = '#FFFFFF';
                this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);
            }
            
            setupEventListeners() {
                if (!this.isTeacher) return; // Students can't draw
                
                // Mouse events
                this.canvas.addEventListener('mousedown', (e) => this.startDrawing(e));
                this.canvas.addEventListener('mousemove', (e) => this.draw(e));
                this.canvas.addEventListener('mouseup', () => this.stopDrawing());
                this.canvas.addEventListener('mouseout', () => this.stopDrawing());
                
                // Touch events for mobile
                this.canvas.addEventListener('touchstart', (e) => {
                    e.preventDefault();
                    this.startDrawing(e.touches[0]);
                });
                this.canvas.addEventListener('touchmove', (e) => {
                    e.preventDefault();
                    this.draw(e.touches[0]);
                });
                this.canvas.addEventListener('touchend', (e) => {
                    e.preventDefault();
                    this.stopDrawing();
                });
                
                // Tool buttons
                document.getElementById('penTool')?.addEventListener('click', () => {
                    this.currentTool = 'pen';
                    this.updateToolButtons();
                });
                
                document.getElementById('eraserTool')?.addEventListener('click', () => {
                    this.currentTool = 'eraser';
                    this.updateToolButtons();
                });
                
                document.getElementById('colorPicker')?.addEventListener('change', (e) => {
                    this.currentColor = e.target.value;
                });
                
                document.getElementById('brushSize')?.addEventListener('change', (e) => {
                    this.currentSize = parseInt(e.target.value);
                });
                
                document.getElementById('clearBoard')?.addEventListener('click', () => {
                    if (confirm('Are you sure you want to clear the entire whiteboard?')) {
                        this.clearBoard();
                    }
                });
                
                document.getElementById('saveBoard')?.addEventListener('click', () => {
                    this.saveWhiteboard();
                });
            }
            
            getMousePos(e) {
                const rect = this.canvas.getBoundingClientRect();
                const scaleX = this.canvas.width / rect.width;
                const scaleY = this.canvas.height / rect.height;
                
                return {
                    x: (e.clientX - rect.left) * scaleX,
                    y: (e.clientY - rect.top) * scaleY
                };
            }
            
            startDrawing(e) {
                if (!this.isTeacher) return;
                
                this.isDrawing = true;
                const pos = this.getMousePos(e);
                
                this.ctx.beginPath();
                this.ctx.moveTo(pos.x, pos.y);
                
                // Set drawing properties
                if (this.currentTool === 'eraser') {
                    this.ctx.globalCompositeOperation = 'destination-out';
                    this.ctx.lineWidth = this.currentSize * 2;
                } else {
                    this.ctx.globalCompositeOperation = 'source-over';
                    this.ctx.strokeStyle = this.currentColor;
                    this.ctx.lineWidth = this.currentSize;
                }
            }
            
            draw(e) {
                if (!this.isDrawing || !this.isTeacher) return;
                
                const pos = this.getMousePos(e);
                this.ctx.lineTo(pos.x, pos.y);
                this.ctx.stroke();
            }
            
            stopDrawing() {
                if (!this.isDrawing) return;
                this.isDrawing = false;
                this.ctx.beginPath();
            }
            
            updateToolButtons() {
                document.getElementById('penTool')?.classList.toggle('bg-blue-500', this.currentTool === 'pen');
                document.getElementById('penTool')?.classList.toggle('bg-gray-300', this.currentTool !== 'pen');
                document.getElementById('eraserTool')?.classList.toggle('bg-blue-500', this.currentTool === 'eraser');
                document.getElementById('eraserTool')?.classList.toggle('bg-gray-300', this.currentTool !== 'eraser');
            }
            
            clearBoard() {
                this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
                this.ctx.fillStyle = '#FFFFFF';
                this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);
                this.strokes = [];
                this.saveWhiteboard();
            }
            
            saveWhiteboard() {
                if (!this.isTeacher) return;
                
                const imageData = this.canvas.toDataURL();
                
                fetch('whiteboard.php?course_id=<?php echo $course_id; ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=save_whiteboard&session_data=${encodeURIComponent(imageData)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log('Whiteboard saved successfully');
                        // Show brief save indicator
                        const saveBtn = document.getElementById('saveBoard');
                        const originalText = saveBtn.textContent;
                        saveBtn.textContent = '✓ Saved';
                        saveBtn.classList.add('bg-green-600');
                        setTimeout(() => {
                            saveBtn.textContent = originalText;
                            saveBtn.classList.remove('bg-green-600');
                        }, 2000);
                    } else {
                        console.error('Failed to save whiteboard');
                    }
                })
                .catch(error => {
                    console.error('Error saving whiteboard:', error);
                });
            }
            
            loadWhiteboard() {
                const savedData = <?php echo json_encode($whiteboard_data); ?>;
                
                if (savedData) {
                    const img = new Image();
                    img.onload = () => {
                        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
                        this.ctx.drawImage(img, 0, 0);
                    };
                    img.src = savedData;
                }
            }
        }
        
        // Initialize whiteboard when page loads
        document.addEventListener('DOMContentLoaded', () => {
            new Whiteboard();
        });
        
        // Auto-refresh for students every 10 seconds to see teacher's updates
        <?php if ($user['role'] !== 'teacher'): ?>
        setInterval(() => {
            location.reload();
        }, 10000);
        <?php endif; ?>
    </script>
</body>
</html>
