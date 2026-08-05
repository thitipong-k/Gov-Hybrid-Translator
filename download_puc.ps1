[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
$url = "https://github.com/YahnisElsts/plugin-update-checker/releases/latest/download/plugin-update-checker.zip"
$output = "inc/Libraries/puc.zip"
$destination = "inc/Libraries/"

Write-Host "Downloading PUC from $url..."
Invoke-WebRequest -Uri $url -OutFile $output
Write-Host "Download complete. Extracting..."
Expand-Archive -Path $output -DestinationPath $destination -Force
Write-Host "Extraction complete. Cleaning up..."
Remove-Item $output
Write-Host "Done."
