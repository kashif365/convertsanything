Add-Type -AssemblyName System.Runtime.WindowsRuntime

function AwaitWinRt($op, $typeName) {
  $type = [Type]::GetType($typeName)
  $method = [System.WindowsRuntimeSystemExtensions].GetMethods() | Where-Object {
    $_.Name -eq 'AsTask' -and $_.IsGenericMethod -and $_.GetParameters().Count -eq 1
  } | Select-Object -First 1
  $generic = $method.MakeGenericMethod($type)
  $task = $generic.Invoke($null, @($op))
  $task.Wait()
  return $task.Result
}

$null = [Windows.Storage.StorageFile, Windows.Storage, ContentType=WindowsRuntime]
$null = [Windows.Media.Ocr.OcrEngine, Windows.Foundation, ContentType=WindowsRuntime]
$null = [Windows.Graphics.Imaging.BitmapDecoder, Windows.Foundation, ContentType=WindowsRuntime]

$inputPath = $args[0]
$outputPath = $args[1]

$fileOp = [Windows.Storage.StorageFile]::GetFileFromPathAsync($inputPath)
$file = AwaitWinRt $fileOp 'Windows.Storage.StorageFile, Windows, ContentType=WindowsRuntime'
$streamOp = $file.OpenAsync([Windows.Storage.FileAccessMode]::Read)
$stream = AwaitWinRt $streamOp 'Windows.Storage.Streams.IRandomAccessStream, Windows, ContentType=WindowsRuntime'
$decoderOp = [Windows.Graphics.Imaging.BitmapDecoder]::CreateAsync($stream)
$decoder = AwaitWinRt $decoderOp 'Windows.Graphics.Imaging.BitmapDecoder, Windows, ContentType=WindowsRuntime'
$bitmapOp = $decoder.GetSoftwareBitmapAsync()
$bitmap = AwaitWinRt $bitmapOp 'Windows.Graphics.Imaging.SoftwareBitmap, Windows, ContentType=WindowsRuntime'
$engine = [Windows.Media.Ocr.OcrEngine]::TryCreateFromUserProfileLanguages()
$resultOp = $engine.RecognizeAsync($bitmap)
$result = AwaitWinRt $resultOp 'Windows.Media.Ocr.OcrResult, Windows, ContentType=WindowsRuntime'

$lines = @()
foreach ($line in $result.Lines) {
  $lines += [PSCustomObject]@{
    text = $line.Text
    x = [int]$line.BoundingRect.X
    y = [int]$line.BoundingRect.Y
    width = [int]$line.BoundingRect.Width
    height = [int]$line.BoundingRect.Height
  }
}

$lines | ConvertTo-Json -Depth 4 | Set-Content -Encoding UTF8 $outputPath