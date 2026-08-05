<?php
$url = 'https://github.com/YahnisElsts/plugin-update-checker/releases/latest/download/plugin-update-checker.zip';
$zipFile = __DIR__ . '/inc/Libraries/puc.zip';
$extractPath = __DIR__ . '/inc/Libraries/';

if (!is_dir($extractPath)) {
    mkdir($extractPath, 0755, true);
}

echo "Downloading from $url...\n";
$fp = fopen($zipFile, 'w+');
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_TIMEOUT, 300);
curl_setopt($ch, CURLOPT_FILE, $fp);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
// Github requires User-Agent
curl_setopt($ch, CURLOPT_USERAGENT, 'GovHybridTranslator-Installer');
curl_exec($ch);
if (curl_errno($ch)) {
    echo 'Curl error: ' . curl_error($ch) . "\n";
}
curl_close($ch);
fclose($fp);

if (!file_exists($zipFile) || filesize($zipFile) < 100) {
    echo "Download failed or file empty.\n";
    exit(1);
}

echo "Extracting...\n";
$zip = new ZipArchive;
if ($zip->open($zipFile) === TRUE) {
    $zip->extractTo($extractPath);
    $zip->close();
    echo "Extracted successfully.\n";
    unlink($zipFile);
} else {
    echo "Failed to open zip.\n";
}
echo "Done.\n";
?>
