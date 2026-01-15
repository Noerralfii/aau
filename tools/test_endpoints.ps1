# Simple endpoint check for local AAU app
$base = "http://localhost/aau/api"
$endpoints = @("ping.php","get_classes.php","get_users.php","get_recent_presensi.php?since=0")

Write-Host "Testing endpoints on $base" -ForegroundColor Cyan
$ok = $true
foreach($e in $endpoints){
  $url = "$base/$e"
  try {
    $r = Invoke-WebRequest -Uri $url -UseBasicParsing -TimeoutSec 10 -ErrorAction Stop
    $j = $r.Content | ConvertFrom-Json
    if ($j -and $j.ok) {
      Write-Host "OK: $e" -ForegroundColor Green
    } else {
      Write-Host "WARN: $e returned unexpected payload" -ForegroundColor Yellow
      $ok = $false
    }
  } catch {
    Write-Host "FAIL: $e - $_" -ForegroundColor Red
    $ok = $false
  }
}
if ($ok) {
  Write-Host "All checks passed" -ForegroundColor Green
} else {
  Write-Host "Some checks failed" -ForegroundColor Red
}