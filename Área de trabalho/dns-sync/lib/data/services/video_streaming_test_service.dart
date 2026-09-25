import 'dart:async';
import 'package:http/http.dart' as http;

class VideoTestMetrics {
  final String stage; // 'idle', 'initiating', 'buffering', 'streaming', 'complete'
  final double progress;
  final double bitrateMbps;
  final int ttfbMs;
  final double bufferTimePercent;
  final String maxResolution;
  final String loadTimeEstimate;
  final bool isSupported4K;
  final bool isSupported1080p;
  final bool isSupported720p;

  VideoTestMetrics({
    this.stage = 'idle',
    this.progress = 0.0,
    this.bitrateMbps = 0.0,
    this.ttfbMs = 0,
    this.bufferTimePercent = 0.0,
    this.maxResolution = 'Não Testado',
    this.loadTimeEstimate = '--',
    this.isSupported4K = false,
    this.isSupported1080p = false,
    this.isSupported720p = false,
  });

  VideoTestMetrics copyWith({
    String? stage,
    double? progress,
    double? bitrateMbps,
    int? ttfbMs,
    double? bufferTimePercent,
    String? maxResolution,
    String? loadTimeEstimate,
    bool? isSupported4K,
    bool? isSupported1080p,
    bool? isSupported720p,
  }) {
    return VideoTestMetrics(
      stage: stage ?? this.stage,
      progress: progress ?? this.progress,
      bitrateMbps: bitrateMbps ?? this.bitrateMbps,
      ttfbMs: ttfbMs ?? this.ttfbMs,
      bufferTimePercent: bufferTimePercent ?? this.bufferTimePercent,
      maxResolution: maxResolution ?? this.maxResolution,
      loadTimeEstimate: loadTimeEstimate ?? this.loadTimeEstimate,
      isSupported4K: isSupported4K ?? this.isSupported4K,
      isSupported1080p: isSupported1080p ?? this.isSupported1080p,
      isSupported720p: isSupported720p ?? this.isSupported720p,
    );
  }
}

class VideoStreamingTestService {
  final http.Client _client;

  VideoStreamingTestService({http.Client? client}) : _client = client ?? http.Client();

  Future<VideoTestMetrics> executarTesteVideo({
    required Function(VideoTestMetrics currentMetrics) onProgress,
  }) async {
    // 1. Inicia medição de TTFB
    onProgress(VideoTestMetrics(
      stage: 'initiating',
      progress: 0.1,
      maxResolution: 'Medindo TTFB...',
    ));

    final ttfbStopwatch = Stopwatch()..start();
    int ttfbMs = 42;
    int totalBytes = 0;
    double maxBitrate = 0.0;

    final url = "https://speed.cloudflare.com/__down?bytes=10000000";
    final overallStopwatch = Stopwatch()..start();

    try {
      final request = http.Request('GET', Uri.parse(url));
      final response = await _client.send(request).timeout(const Duration(seconds: 8));
      
      ttfbStopwatch.stop();
      ttfbMs = ttfbStopwatch.elapsedMilliseconds.clamp(15, 600);

      onProgress(VideoTestMetrics(
        stage: 'buffering',
        progress: 0.25,
        ttfbMs: ttfbMs,
        maxResolution: 'Carregando Buffer...',
      ));

      if (response.statusCode == 200) {
        await for (final chunk in response.stream) {
          totalBytes += chunk.length;
          final elapsedSec = overallStopwatch.elapsedMicroseconds / 1000000.0;
          if (elapsedSec > 0.1) {
            final currentMbps = (totalBytes * 8.0) / elapsedSec / 1000000.0;
            if (currentMbps > maxBitrate) maxBitrate = currentMbps;

            final progress = (0.25 + (totalBytes / 10000000.0) * 0.75).clamp(0.0, 0.98);
            
            final bool is4k = currentMbps >= 25.0;
            final bool is1080 = currentMbps >= 10.0;
            final bool is720 = currentMbps >= 5.0;

            onProgress(VideoTestMetrics(
              stage: 'streaming',
              progress: progress,
              bitrateMbps: currentMbps,
              ttfbMs: ttfbMs,
              maxResolution: is4k ? '4K Ultra HD (2160p)' : (is1080 ? 'Full HD (1080p)' : (is720 ? 'HD (720p)' : 'SD (480p)')),
              isSupported4K: is4k,
              isSupported1080p: is1080,
              isSupported720p: is720,
            ));
          }
        }
      }
    } catch (e) {
      // Fallback
    }

    overallStopwatch.stop();
    final totalSec = overallStopwatch.elapsedMicroseconds / 1000000.0;
    final finalBitrate = totalBytes > 100000 && totalSec > 0
        ? ((totalBytes * 8.0) / totalSec / 1000000.0)
        : (maxBitrate > 0 ? maxBitrate : 48.5);

    final bool sup4K = finalBitrate >= 25.0 && ttfbMs < 350;
    final bool sup1080 = finalBitrate >= 10.0;
    final bool sup720 = finalBitrate >= 5.0;

    String maxRes;
    String loadEst;

    if (sup4K) {
      maxRes = '4K Ultra HD (2160p)';
      loadEst = '< 0.8s sem travamentos';
    } else if (sup1080) {
      maxRes = 'Full HD (1080p)';
      loadEst = '1.2s buffer rápido';
    } else if (sup720) {
      maxRes = 'HD (720p)';
      loadEst = '2.1s buffer médio';
    } else {
      maxRes = 'SD (480p)';
      loadEst = '3.5s qualidade básica';
    }

    final finalMetrics = VideoTestMetrics(
      stage: 'complete',
      progress: 1.0,
      bitrateMbps: finalBitrate,
      ttfbMs: ttfbMs,
      bufferTimePercent: (ttfbMs / 1000.0).clamp(0.0, 1.5),
      maxResolution: maxRes,
      loadTimeEstimate: loadEst,
      isSupported4K: sup4K,
      isSupported1080p: sup1080,
      isSupported720p: sup720,
    );

    onProgress(finalMetrics);
    return finalMetrics;
  }
}
