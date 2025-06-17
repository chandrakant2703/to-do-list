<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config.php';

// Handle task operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_task'])) {
        $task = trim($_POST['task']);
        $user_id = $_SESSION['user_id'];
        if (!empty($task)) {
            $stmt = $conn->prepare("INSERT INTO tasks (user_id, task_name) VALUES (?, ?)");
            $stmt->bind_param("is", $user_id, $task);
            $stmt->execute();
        }
    } elseif (isset($_POST['delete_task'])) {
        $task_id = $_POST['task_id'];
        $user_id = $_SESSION['user_id'];
        $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $task_id, $user_id);
        $stmt->execute();
    } elseif (isset($_POST['toggle_complete'])) {
        $task_id = $_POST['task_id'];
        $user_id = $_SESSION['user_id'];
        $stmt = $conn->prepare("UPDATE tasks SET completed = NOT completed WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $task_id, $user_id);
        $stmt->execute();
    } elseif (isset($_POST['update_task'])) {
        $task_id = $_POST['task_id'];
        $task_name = trim($_POST['task_name']);
        $user_id = $_SESSION['user_id'];
        if (!empty($task_name)) {
            $stmt = $conn->prepare("UPDATE tasks SET task_name = ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("sii", $task_name, $task_id, $user_id);
            $stmt->execute();
        }
    }
    header("Location: index.php");
    exit();
}

// Fetch tasks
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM tasks WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$tasks = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Todo List</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen">
    <nav class="bg-indigo-600 p-4">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-white text-2xl font-bold">Todo List</h1>
            <div class="flex items-center space-x-4">
                <span class="text-white">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="logout.php" class="bg-white text-indigo-600 px-4 py-2 rounded-lg hover:bg-indigo-100 transition">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-md p-6">
            <form action="index.php" method="POST" class="mb-6">
                <div class="flex gap-2">
                    <input type="text" name="task" placeholder="Add a new task..." required
                           class="flex-1 border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:border-indigo-500">
                    <button type="submit" name="add_task" 
                            class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition">
                        Add Task
                    </button>
                </div>
            </form>

            <div class="space-y-4">
                <?php foreach ($tasks as $task): ?>
                    <div class="flex items-center justify-between bg-gray-50 p-4 rounded-lg">
                        <div class="flex items-center gap-3 flex-1">
                            <form action="index.php" method="POST" class="inline">
                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                <button type="submit" name="toggle_complete" class="text-gray-500 hover:text-indigo-600">
                                    <?php if ($task['completed']): ?>
                                        <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    <?php else: ?>
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <circle cx="12" cy="12" r="10" stroke-width="2"></circle>
                                        </svg>
                                    <?php endif; ?>
                                </button>
                            </form>
                            
                            <span class="task-text flex-1 <?php echo $task['completed'] ? 'line-through text-gray-500' : ''; ?>">
                                <?php echo htmlspecialchars($task['task_name']); ?>
                            </span>
                            
                            <div class="flex gap-2">
                                <button onclick="editTask(<?php echo $task['id']; ?>, '<?php echo htmlspecialchars($task['task_name']); ?>')"
                                        class="text-blue-600 hover:text-blue-800">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                
                                <form action="index.php" method="POST" class="inline">
                                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                    <button type="submit" name="delete_task" class="text-red-600 hover:text-red-800">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Edit Task Modal -->
    <div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center">
        <div class="bg-white p-6 rounded-lg shadow-lg w-96">
            <h3 class="text-lg font-semibold mb-4">Edit Task</h3>
            <form action="index.php" method="POST">
                <input type="hidden" id="edit_task_id" name="task_id">
                <input type="text" id="edit_task_name" name="task_name" class="w-full border border-gray-300 rounded-lg px-4 py-2 mb-4">
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeEditModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">Cancel</button>
                    <button type="submit" name="update_task" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Update</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editTask(taskId, taskName) {
            document.getElementById('editModal').classList.remove('hidden');
            document.getElementById('editModal').classList.add('flex');
            document.getElementById('edit_task_id').value = taskId;
            document.getElementById('edit_task_name').value = taskName;
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
            document.getElementById('editModal').classList.remove('flex');
        }
    </script>

    <script>
        (function() {
            const popup = document.createElement("div");
            popup.innerHTML = "Created by <strong>Chandrakant</strong>";
            Object.assign(popup.style, {
                position: "fixed",
                bottom: "20px",
                left: "20px",
                backgroundColor: "rgba(0, 0, 0, 0.7)",
                color: "#fff",
                padding: "8px 12px",
                borderRadius: "8px",
                fontSize: "14px",
                zIndex: "1000",
                boxShadow: "0 4px 8px rgba(0, 0, 0, 0.2)",
                opacity: "0",
                transform: "translateY(10px)",
                transition: "opacity 0.5s ease, transform 0.5s ease"
            });

            document.body.appendChild(popup);

            requestAnimationFrame(() => {
                popup.style.opacity = "1";
                popup.style.transform = "translateY(0)";
            });

            setTimeout(() => {
                popup.style.opacity = "0";
                popup.style.transform = "translateY(10px)";
                setTimeout(() => popup.remove(), 500);
            }, 5000);
        })();
    </script>
</body>
</html>