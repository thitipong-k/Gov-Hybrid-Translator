$ErrorActionPreference = "Stop"
Write-Host "Starting installation..."
$dir = "inc\Libraries"
if (!(Test-Path -Path $dir)) {
    New-Item -ItemType Directory -Force -Path $dir
    Write-Host "Created $dir"
}
$url = "https://github.com/YahnisElsts/plugin-update-checker/releases/latest/download/plugin-update-checker.zip"
$zip = "$dir\puc.zip"

[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
Write-Host "Downloading from $url..."
try {
    Invoke-WebRequest -Uri $url -OutFile $zip
}
catch {
    Write-Error "Download failed: $_"
    exit 1
}

Write-Host "Extracting..."
Expand-Archive -Path $zip -DestinationPath $dir -Force
Write-Host "Cleaning up..."
Remove-Item $zip
Write-Host "Success!"
exit 0
