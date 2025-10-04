<?php
// Set the content type to JSON, so the browser knows how to read the data.
header('Content-Type: application/json');

// --- CONFIGURATION ---
// The directory to scan for files. '.' means the current directory where this script is located.
// IMPORTANT: Change this if your files are in a different folder, e.g., '/var/www/my-files'
$directory = '.'; 

// --- FUNCTION to get file type for icons ---
function getFileType($extension) {
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg'];
    $videoExtensions = ['mp4', 'mov', 'avi', 'mkv', 'webm'];
    $audioExtensions = ['mp3', 'wav', 'ogg', 'm4a'];
    $documentExtensions = ['doc', 'docx', 'pdf', 'txt', 'xls', 'xlsx', 'ppt', 'pptx'];
    $archiveExtensions = ['zip', 'rar', '7z', 'tar', 'gz'];

    if (in_array($extension, $imageExtensions)) return 'image';
    if (in_array($extension, $videoExtensions)) return 'video';
    if (in_array($extension, $audioExtensions)) return 'audio';
    if (in_array($extension, $documentExtensions)) return 'document';
    if (in_array($extension, $archiveExtensions)) return 'archive';
    return 'default'; // Default icon for other file types
}

// --- FUNCTION to format file size ---
function formatBytes($bytes, $precision = 2) { 
    $units = ['B', 'KB', 'MB', 'GB', 'TB']; 
    $bytes = max($bytes, 0); 
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
    $pow = min($pow, count($units) - 1); 
    $bytes /= (1 << (10 * $pow)); 
    return round($bytes, $precision) . ' ' . $units[$pow]; 
} 

// --- MAIN LOGIC ---

// 1. Get File and Folder List
$files = [];
$items = scandir($directory); // Scan the specified directory

foreach ($items as $item) {
    // Ignore current directory ('.') and parent directory ('..'), and this API file itself.
    if ($item === '.' || $item === '..' || $item === basename(__FILE__) || $item === 'index.html') {
        continue;
    }

    $path = $directory . DIRECTORY_SEPARATOR . $item;
    $fileInfo = [];

    if (is_dir($path)) {
        $fileInfo['type'] = 'folder';
        $fileInfo['name'] = $item;
        $fileInfo['modified'] = date("Y-m-d", filemtime($path));
        // Note: Calculating folder size can be slow, so we'll omit it for performance.
        $fileInfo['size'] = '-'; 
    } else {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $fileInfo['type'] = getFileType($extension);
        $fileInfo['name'] = $item;
        $fileInfo['modified'] = date("Y-m-d", filemtime($path));
        $fileInfo['size'] = formatBytes(filesize($path));
    }
    $files[] = $fileInfo;
}

// 2. Get Server Storage Capacity
// disk_total_space('/') gets total space for the entire partition.
$totalSpace = disk_total_space('/'); 
$freeSpace = disk_free_space('/');
$usedSpace = $totalSpace - $freeSpace;

$storage = [
    'used' => $usedSpace,
    'total' => $totalSpace,
];

// 3. Combine and Output as JSON
$response = [
    'files' => $files,
    'storage' => $storage,
];

// Convert the PHP array into a JSON string and send it to the browser.
echo json_encode($response);

?>
