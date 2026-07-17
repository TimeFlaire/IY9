<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WebFM - File Manager & Editor</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.36.2/ace.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap');
        body { font-family: 'Inter', system-ui, sans-serif; }
        .file-list tr:hover { background-color: #f8fafc; }
        .ace_editor { height: 100%; width: 100%; }
        .modal { animation: modalPop 0.2s ease; }
        @keyframes modalPop { from { transform: scale(0.95); opacity: 0; } to { transform: scale(1); opacity: 1; } }
    </style>
</head>
<body class="bg-gray-50 text-gray-900">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="w-72 bg-white border-r border-gray-200 flex flex-col">
            <div class="p-4 border-b border-gray-200 bg-gray-50">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-folder-tree text-2xl text-blue-600"></i>
                    <div>
                        <h1 class="text-xl font-semibold">WebFM</h1>
                        <p class="text-xs text-gray-500">File Manager + Editor</p>
                    </div>
                </div>
            </div>

            <!-- Connection -->
            <div class="p-4 border-b">
                <label class="text-xs font-medium text-gray-500 block mb-1">WEB SHELL URL</label>
                <div class="flex gap-2">
                    <input id="shellUrl" type="text"
                           class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                           placeholder="https://example.com/shell.php" value="">
                    <button onclick="connectShell()"
                            class="px-4 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition">
                        Connect
                    </button>
                </div>
                <div id="connectionStatus" class="mt-2 text-xs flex items-center gap-1"></div>
            </div>

            <!-- Path -->
            <div class="p-4 border-b">
                <div class="flex items-center gap-2 text-sm">
                    <button onclick="goHome()" class="text-blue-600 hover:text-blue-700">
                        <i class="fa-solid fa-house"></i>
                    </button>
                    <div id="currentPath" class="flex-1 font-mono text-xs bg-gray-100 px-3 py-1 rounded overflow-x-auto whitespace-nowrap"></div>
                </div>
            </div>

            <!-- Actions -->
            <div class="p-4 flex flex-wrap gap-2 border-b">
                <button onclick="uploadFile()"
                        class="flex-1 px-4 py-2 bg-white border border-gray-300 hover:border-gray-400 rounded-lg text-sm flex items-center justify-center gap-2">
                    <i class="fa-solid fa-upload"></i> Upload
                </button>
                <button onclick="createNewFile()"
                        class="flex-1 px-4 py-2 bg-white border border-gray-300 hover:border-gray-400 rounded-lg text-sm flex items-center justify-center gap-2">
                    <i class="fa-solid fa-file-circle-plus"></i> New File
                </button>
                <button onclick="createNewDir()"
                        class="flex-1 px-4 py-2 bg-white border border-gray-300 hover:border-gray-400 rounded-lg text-sm flex items-center justify-center gap-2">
                    <i class="fa-solid fa-folder-plus"></i> New Dir
                </button>
            </div>

            <!-- Tree placeholder -->
            <div class="flex-1 overflow-auto p-3 text-sm" id="dirTree">
                <!-- Populated by JS -->
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col">
            <!-- Toolbar -->
            <div class="h-14 border-b bg-white px-6 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <button onclick="refreshFiles()"
                            class="flex items-center gap-2 px-4 py-1.5 text-sm hover:bg-gray-100 rounded-lg">
                        <i class="fa-solid fa-arrows-rotate"></i> Refresh
                    </button>
                    <button onclick="goUp()"
                            class="flex items-center gap-2 px-4 py-1.5 text-sm hover:bg-gray-100 rounded-lg">
                        <i class="fa-solid fa-level-up-alt"></i> Up
                    </button>
                </div>

                <div class="flex items-center gap-3 text-sm">
                    <div id="status" class="text-gray-500"></div>
                </div>
            </div>

            <!-- File List -->
            <div class="flex-1 overflow-auto p-6">
                <table class="w-full file-list">
                    <thead>
                        <tr class="text-left text-xs uppercase text-gray-500 border-b">
                            <th class="pb-3 font-medium w-8"></th>
                            <th class="pb-3 font-medium">Name</th>
                            <th class="pb-3 font-medium">Size</th>
                            <th class="pb-3 font-medium">Modified</th>
                            <th class="pb-3 font-medium w-40">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="fileListBody" class="text-sm"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Editor Modal -->
    <div id="editorModal" class="hidden fixed inset-0 bg-black/70 flex items-center justify-center z-50">
        <div class="bg-white w-[95%] h-[95%] rounded-2xl shadow-2xl flex flex-col modal">
            <div class="flex items-center justify-between border-b px-6 py-4">
                <div class="flex items-center gap-3">
                    <i id="fileIcon" class="fa-solid fa-file text-blue-600"></i>
                    <div>
                        <div id="editingFile" class="font-semibold"></div>
                        <div id="editingPath" class="text-xs text-gray-500 font-mono"></div>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <button onclick="saveFile()"
                            class="px-6 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl flex items-center gap-2 text-sm font-medium">
                        <i class="fa-solid fa-floppy-disk"></i> Save
                    </button>
                    <button onclick="closeEditor()"
                            class="px-6 py-2 bg-gray-100 hover:bg-gray-200 rounded-xl flex items-center gap-2 text-sm font-medium">
                        <i class="fa-solid fa-xmark"></i> Close
                    </button>
                </div>
            </div>
            <div id="aceEditor" class="flex-1"></div>
        </div>
    </div>

    <!-- Upload Modal -->
    <div id="uploadModal" class="hidden fixed inset-0 bg-black/70 flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-8 max-w-md w-full modal">
            <h3 class="text-lg font-semibold mb-6">Upload File</h3>
            <input type="file" id="uploadInput" class="w-full mb-6">
            <div class="flex gap-3">
                <button onclick="closeUploadModal()"
                        class="flex-1 py-3 border border-gray-300 rounded-xl">Cancel</button>
                <button onclick="performUpload()"
                        class="flex-1 py-3 bg-blue-600 text-white rounded-xl">Upload</button>
            </div>
        </div>
    </div>

    <script>
        let shellUrl = '';
        let currentPath = '/';
        let editor = null;
        let currentEditingFile = null;

        // Tailwind script already loaded via CDN
        function initTailwind() {
            // Already initialized by CDN
        }

        function setStatus(message, type = 'info') {
            const statusEl = document.getElementById('status');
            let color = 'text-gray-500';
            if (type === 'success') color = 'text-emerald-600';
            if (type === 'error') color = 'text-red-600';
            statusEl.innerHTML = `<span class="${color}">${message}</span>`;
            setTimeout(() => {
                if (statusEl.textContent.includes(message)) statusEl.textContent = '';
            }, 4000);
        }

        async function executeCommand(cmd, showOutput = true) {
            if (!shellUrl) {
                setStatus('Please connect to a web shell first', 'error');
                return null;
            }

            try {
                const formData = new FormData();
                formData.append('cmd', cmd);

                const response = await fetch(shellUrl, {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) throw new Error('Shell request failed');

                const text = await response.text();
                if (showOutput && text.trim()) {
                    console.log('Command output:', text);
                }
                return text;
            } catch (e) {
                console.error(e);
                setStatus('Command failed: ' + e.message, 'error');
                return null;
            }
        }

        async function connectShell() {
            shellUrl = document.getElementById('shellUrl').value.trim();
            if (!shellUrl) {
                setStatus('Enter a valid shell URL', 'error');
                return;
            }

            const statusEl = document.getElementById('connectionStatus');
            statusEl.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Connecting...`;

            // Test connection
            const test = await executeCommand('pwd', false);
            if (test !== null) {
                statusEl.innerHTML = `<span class="text-emerald-600"><i class="fa-solid fa-circle-check"></i> Connected</span>`;
                currentPath = test.trim() || '/';
                document.getElementById('currentPath').textContent = currentPath;
                await listFiles();
            } else {
                statusEl.innerHTML = `<span class="text-red-600"><i class="fa-solid fa-circle-xmark"></i> Failed</span>`;
            }
        }

        async function listFiles() {
            const body = document.getElementById('fileListBody');
            body.innerHTML = `<tr><td colspan="5" class="py-12 text-center"><i class="fa-solid fa-spinner fa-spin text-3xl text-gray-300"></i></td></tr>`;

            const output = await executeCommand(`ls -la --time-style=long-iso "${currentPath}"`);
            if (!output) {
                body.innerHTML = `<tr><td colspan="5" class="py-12 text-center text-red-500">Failed to list directory</td></tr>`;
                return;
            }

            const lines = output.trim().split('\n');
            let html = '';

            for (let line of lines) {
                if (!line.trim() || line.startsWith('total')) continue;

                const parts = line.trim().split(/\s+/);
                if (parts.length < 8) continue;

                const perms = parts[0];
                const name = parts.slice(8).join(' ');
                const isDir = perms.startsWith('d');
                const size = parts[4];
                const date = parts[5] + ' ' + parts[6];

                if (name === '.' || name === '..') continue;

                const icon = isDir ?
                    '<i class="fa-solid fa-folder text-blue-500"></i>' :
                    '<i class="fa-solid fa-file text-gray-500"></i>';

                html += `
                    <tr class="border-b last:border-0">
                        <td class="py-3 px-2">${icon}</td>
                        <td onclick="${isDir ? `navigateTo('${name}')` : `editFile('${name}')`}"
                            class="py-3 font-medium cursor-pointer hover:text-blue-600">${name}</td>
                        <td class="py-3 text-gray-500 text-sm">${isDir ? '-' : formatBytes(size)}</td>
                        <td class="py-3 text-gray-500 text-sm">${date}</td>
                        <td class="py-3">
                            <div class="flex gap-4 text-gray-400">
                                ${isDir ? '' : `<button onclick="event.stopImmediatePropagation(); editFile('${name}')" class="hover:text-blue-600"><i class="fa-solid fa-pen"></i></button>`}
                                <button onclick="event.stopImmediatePropagation(); downloadFile('${name}')" class="hover:text-blue-600"><i class="fa-solid fa-download"></i></button>
                                <button onclick="event.stopImmediatePropagation(); deleteItem('${name}', ${isDir})" class="hover:text-red-600"><i class="fa-solid fa-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                `;
            }

            body.innerHTML = html || `<tr><td colspan="5" class="py-12 text-center text-gray-400">Directory is empty</td></tr>`;
            updateTree();
        }

        function formatBytes(bytes) {
            if (!bytes) return '0 B';
            const sizes = ['B', 'KB', 'MB', 'GB'];
            let i = 0;
            bytes = parseInt(bytes);
            while (bytes > 1024 && i < sizes.length - 1) {
                bytes /= 1024;
                i++;
            }
            return Math.round(bytes) + ' ' + sizes[i];
        }

        function navigateTo(dir) {
            if (currentPath.endsWith('/')) {
                currentPath += dir;
            } else {
                currentPath += '/' + dir;
            }
            document.getElementById('currentPath').textContent = currentPath;
            listFiles();
        }

        function goUp() {
            if (currentPath === '/' || currentPath === '') return;
            const parts = currentPath.split('/').filter(Boolean);
            parts.pop();
            currentPath = '/' + parts.join('/');
            if (currentPath === '') currentPath = '/';
            document.getElementById('currentPath').textContent = currentPath;
            listFiles();
        }

        function goHome() {
            currentPath = '/';
            document.getElementById('currentPath').textContent = currentPath;
            listFiles();
        }

        async function downloadFile(filename) {
            const fullPath = currentPath.endsWith('/') ? currentPath + filename : currentPath + '/' + filename;
            setStatus('Downloading...');

            const content = await executeCommand(`cat "${fullPath}"`, false);
            if (!content) return;

            const blob = new Blob([content], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            setStatus('Downloaded ' + filename, 'success');
        }

        async function deleteItem(name, isDir) {
            if (!confirm(`Delete ${isDir ? 'directory' : 'file'} "${name}"?`)) return;

            const fullPath = currentPath.endsWith('/') ? currentPath + name : currentPath + '/' + name;
            const cmd = isDir ? `rm -rf "${fullPath}"` : `rm "${fullPath}"`;

            const result = await executeCommand(cmd);
            if (result !== null) {
                setStatus(`Deleted ${name}`, 'success');
                listFiles();
            }
        }

        let aceInstance = null;

        function initEditor() {
            if (aceInstance) return;
            aceInstance = ace.edit("aceEditor");
            aceInstance.setTheme("ace/theme/twilight");
            aceInstance.setFontSize(14);
            aceInstance.getSession().setUseWrapMode(true);
        }

        async function editFile(filename) {
            const fullPath = currentPath.endsWith('/') ? currentPath + filename : currentPath + '/' + filename;
            currentEditingFile = fullPath;

            const content = await executeCommand(`cat "${fullPath}"`, false);
            if (content === null) return;

            document.getElementById('editingFile').textContent = filename;
            document.getElementById('editingPath').textContent = fullPath;
            document.getElementById('editorModal').classList.remove('hidden');

            initEditor();
            aceInstance.setValue(content);
            aceInstance.clearSelection();

            // Simple extension based mode
            const ext = filename.split('.').pop().toLowerCase();
            const modeMap = {
                'js': 'javascript', 'py': 'python', 'php': 'php', 'html': 'html',
                'css': 'css', 'json': 'json', 'md': 'markdown'
            };
            aceInstance.getSession().setMode(`ace/mode/${modeMap[ext] || 'text'}`);
        }

        async function saveFile() {
            if (!currentEditingFile || !aceInstance) return;

            const content = aceInstance.getValue();
            const tempFile = '/tmp/webFM_temp_' + Date.now();

            // Write content
            await executeCommand(`cat > "${tempFile}" << 'EOF'
${content}
EOF`, false);

            // Move to destination
            await executeCommand(`mv "${tempFile}" "${currentEditingFile}"`, false);

            setStatus('File saved successfully', 'success');
            closeEditor();
            listFiles();
        }

        function closeEditor() {
            document.getElementById('editorModal').classList.add('hidden');
        }

        function uploadFile() {
            document.getElementById('uploadModal').classList.remove('hidden');
        }

        function closeUploadModal() {
            document.getElementById('uploadModal').classList.add('hidden');
        }

        async function performUpload() {
            const input = document.getElementById('uploadInput');
            if (!input.files.length) {
                alert('Select a file');
                return;
            }

            const file = input.files[0];
            const reader = new FileReader();

            reader.onload = async function(e) {
                const content = e.target.result;
                const fullPath = currentPath.endsWith('/') ? currentPath + file.name : currentPath + '/' + file.name;

                await executeCommand(`cat > "${fullPath}" << 'EOF'
${content}
EOF`, false);

                closeUploadModal();
                setStatus('File uploaded: ' + file.name, 'success');
                listFiles();
            };

            reader.readAsText(file);
        }

        async function createNewFile() {
            const name = prompt('New file name:');
            if (!name) return;

            const fullPath = currentPath.endsWith('/') ? currentPath + name : currentPath + '/' + name;
            await executeCommand(`touch "${fullPath}"`);
            listFiles();
            editFile(name);
        }

        async function createNewDir() {
            const name = prompt('New directory name:');
            if (!name) return;

            const fullPath = currentPath.endsWith('/') ? currentPath + name : currentPath + '/' + name;
            await executeCommand(`mkdir -p "${fullPath}"`);
            listFiles();
        }

        function updateTree() {
            // Simple breadcrumb style tree for now
            const treeEl = document.getElementById('dirTree');
            treeEl.innerHTML = `
                <div class="text-xs text-gray-400 mb-2 px-2">CURRENT PATH</div>
                <div class="font-mono text-xs bg-gray-50 p-3 rounded border text-gray-600 break-all">${currentPath}</div>
            `;
        }

        async function refreshFiles() {
            listFiles();
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const editorModal = document.getElementById('editorModal');
                if (!editorModal.classList.contains('hidden')) closeEditor();
            }
            if (e.ctrlKey && e.key === 's' && !document.getElementById('editorModal').classList.contains('hidden')) {
                e.preventDefault();
                saveFile();
            }
        });

        // Initialize
        window.onload = function() {
            document.getElementById('currentPath').textContent = currentPath;
            // Auto-focus URL input
            setTimeout(() => {
                document.getElementById('shellUrl').focus();
            }, 300);

            // Demo URL hint
            console.log('%cWebFM ready. Paste your web shell URL and click Connect.', 'color: #3b82f6; font-weight: 500');
        };
    </script>
</body>
</html>
