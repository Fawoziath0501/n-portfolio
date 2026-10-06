<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use Illuminate\Http\Request;

/**
 * Journaux du serveur (storage/logs) consultables depuis l'administration : diagnostiquer une erreur sans accès SSH.
 * Seule la fin des fichiers est lue (2 Mo) pour rester rapide même avec de gros journaux.
 */
class LogController extends Controller
{
    private const TAIL = 2 * 1024 * 1024;

    private const MAX_ENTRIES = 400;

    public function index()
    {
        $files = collect(glob(storage_path('logs/*.log')) ?: [])
            ->map(fn ($p) => ['name' => basename($p), 'size' => filesize($p), 'modified' => date(DATE_ATOM, filemtime($p))])
            ->sortByDesc('modified')->values();

        return response()->json(['files' => $files]);
    }

    public function show(Request $request, string $file)
    {
        $path = $this->path($file);
        $v = $request->validate(['level' => 'nullable|string|max:20', 'q' => 'nullable|string|max:200']);
        $size = filesize($path);

        $h = fopen($path, 'rb');
        if ($size > self::TAIL) {
            fseek($h, -self::TAIL, SEEK_END);
        }
        $raw = stream_get_contents($h);
        fclose($h);

        // Une entrée commence par « [date] environnement.NIVEAU: message ».
        $parts = preg_split('/^(?=\[\d{4}-\d{2}-\d{2}[ T][^\]]*\] \w+\.\w+:)/m', $raw) ?: [];
        $entries = [];
        $counts = [];
        foreach ($parts as $part) {
            if (! preg_match('/^\[([^\]]+)\] (\w+)\.(\w+): (.*)$/s', $part, $m)) {
                continue; // début tronqué par la lecture partielle
            }
            $level = strtolower($m[3]);
            $counts[$level] = ($counts[$level] ?? 0) + 1;
            $text = rtrim($m[4]);
            if (! empty($v['level']) && $v['level'] !== $level) {
                continue;
            }
            if (! empty($v['q']) && stripos($text, $v['q']) === false) {
                continue;
            }
            $nl = strpos($text, "\n");
            $entries[] = [
                'at' => $m[1],
                'env' => $m[2],
                'level' => $level,
                'message' => mb_strimwidth($nl === false ? $text : substr($text, 0, $nl), 0, 600, '…'),
                'details' => $nl === false ? '' : mb_strimwidth(substr($text, $nl + 1), 0, 20000, "\n…"),
            ];
        }

        return response()->json([
            'file' => basename($path),
            'size' => $size,
            'partial' => $size > self::TAIL,
            'counts' => $counts,
            'total' => count($entries),
            'entries' => array_slice(array_reverse($entries), 0, self::MAX_ENTRIES),
        ]);
    }

    public function download(string $file)
    {
        return response()->download($this->path($file));
    }

    public function clear(string $file)
    {
        $path = $this->path($file);
        file_put_contents($path, '');
        Activity::log('Journal '.basename($path).' vidé');

        return response()->json(['ok' => true]);
    }

    /** Nombre d'erreurs (error et plus grave) enregistrées depuis 24 h, tous journaux confondus (fin des fichiers). */
    public static function recentErrors(): int
    {
        $since = now()->subDay()->format('Y-m-d H:i:s');
        $n = 0;
        foreach (glob(storage_path('logs/*.log')) ?: [] as $path) {
            if (filemtime($path) < now()->subDay()->getTimestamp()) {
                continue;
            }
            $h = fopen($path, 'rb');
            if (filesize($path) > 512 * 1024) {
                fseek($h, -512 * 1024, SEEK_END);
            }
            preg_match_all('/^\[(\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2})[^\]]*\] \w+\.(ERROR|CRITICAL|ALERT|EMERGENCY):/m', stream_get_contents($h), $m);
            fclose($h);
            foreach ($m[1] as $at) {
                $n += str_replace('T', ' ', $at) >= $since ? 1 : 0;
            }
        }

        return $n;
    }

    /** Fichier .log de storage/logs uniquement (pas de chemin arbitraire). */
    private function path(string $file): string
    {
        abort_unless(preg_match('/^[\w.-]+\.log$/', $file), 404);
        $path = storage_path('logs/'.$file);
        abort_unless(is_file($path), 404);

        return $path;
    }
}
