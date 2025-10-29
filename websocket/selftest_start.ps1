param(
    [string]$DeviceId = "DEV_34F059C9F6D325DF",
    [decimal]$Amount = 20.00
)

$OrderId = "ORDER_SELFTEST_" + (Get-Date -Format "yyyyMMddHHmmss")

$Payload = @{
  device_id = $DeviceId
  order_id  = $OrderId
  amount    = $Amount
  openid    = "openid_selftest"
  user_id   = 0
}

$Json = $Payload | ConvertTo-Json -Depth 4

Write-Host "POST http://127.0.0.1:3001/api/start-lottery"
Write-Host "Payload:" $Json

try {
  $resp = Invoke-RestMethod -Uri "http://127.0.0.1:3001/api/start-lottery" -Method Post -ContentType "application/json" -Body $Json -TimeoutSec 10
  $out = $resp | ConvertTo-Json -Depth 6
  Write-Host "Response:" $out
} catch {
  Write-Error $_
  exit 1
}