# Architecture & Design: Speed Test, Video Streaming & Native Sharing

## 1. Real ISP & Network Intelligence Service (`IspDetectionService`)
- Multi-source fallback (`speed.cloudflare.com/meta`, `ipinfo.io/json`, `ip-api.com/json`):
  - Returns `IspInfoModel`:
    - `isp`: e.g. "Claro Brasil" / "Vivo Fibra" / "Starlink"
    - `asn`: e.g. "AS28573"
    - `ip`: e.g. "177.18.92.14"
    - `city`: e.g. "São Paulo"
    - `country`: e.g. "Brasil"
    - `flag`: "🇧🇷"
    - `server`: "Cloudflare Edge GRU"

## 2. Video Streaming Test Engine (`VideoStreamingTestService`)
- Measures:
  - `ttfbMs` (Time To First Byte / Buffer initiation latency)
  - `bufferingTimePercent` (%)
  - `maxBitrateMbps` (Mbps)
  - `maxResolution`: "4K 2160p", "1080p FHD", "720p HD", "480p SD"
  - `loadTimeEstimateSeconds`: estimated time to buffer 10 minutes of 4K content.

## 3. Native Share Action
- `CanalNativo.compartilharResultado(String texto, String titulo)` invokes Android `Intent(Intent.ACTION_SEND)` via `MethodChannel`.

## 4. UI/UX Refinement (`SpeedTestScreen.dart`)
- Modern Segmented Tab: [Velocidade] | [Teste de Vídeo]
- Real-time ISP detection banner with country flag and server name.
- Live animated video resolution capability badge (4K Ultra HD ready with glowing green indicator).
- Result Action Bar with "Compartilhar Resultado 🚀" button.
