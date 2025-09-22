<?php
namespace App\Controllers;

class Ingests extends \CodeIgniter\Controller
{
    /** Lista fija de ingestas */
    private array $labelsIG = [
        "IG 1.1","IG 1.2","IG 1.3","IG 1.4",
        "IG 2.1","IG 2.2","IG 2.3","IG 2.4",
        "IG 3.1","IG 3.2","IG 3.3","IG 3.4",
        "IG 4.1","IG 4.2","IG 4.3","IG 4.4",
        "IG 12.1","IG 12.2","IG 12.3","IG 12.4",
        "IG 101.1","IG 101.2","IG 101.3","IG 101.4","IG 101.5","IG 101.6","IG 101.7","IG 101.8",
        "IG 102.1","IG 102.2","IG 102.3","IG 102.4","IG 102.5","IG 102.6","IG 102.7","IG 102.8",
        "IG 103.1","IG 103.2","IG 103.3","IG 103.4","IG 103.5","IG 103.6","IG 103.7","IG 103.8",
        "IG 15.1","IG 15.2","IG 15.3","IG 15.4","IG 15.5","IG 15.6","IG 15.7","IG 15.8",
        "IG 16.1","IG 16.2","IG 16.3","IG 16.4","IG 16.5","IG 16.6","IG 16.7","IG 16.8",
    ];

public function board_modal()
{
    $p = config('Pusher'); // igual que en board()

    return view('ingestas/board', [
        'labels'    => $this->labelsIG(),
        'pusher'    => [
            'key'     => $p->key ?? '',
            'cluster' => $p->cluster ?? 'sa1',
        ],
        'refreshMs' => 5000,
    ]);
}


    /** Vista del tablero */
    public function board()
    {
        $p = config('Pusher'); // el mismo que ya usás en Events.php

        return view('ingestas/board', [
            'labels'    => $this->labelsIG(),
            'pusher'    => [
                'key'     => $p->key ?? '',
                'cluster' => $p->cluster ?? 'sa1',
            ],
            'refreshMs' => 5000,
        ]);
    }


    /** API de estado: devuelve qué labels están ocupadas */
public function status()
{
    $labels = $this->labelsIG();
    $slots  = [];
    foreach ($labels as $name) {
        $slots[$name] = [
            'name'      => $name,
            'busy'      => false,
            'event_id'  => null,
            'title'     => null,
            'color'     => null,
            'start'     => null,
            'end'       => null,
            'recording' => null,
            'conv'      => null,
        ];
    }

    $db   = \Config\Database::connect();
    $cols = array_column($db->query("SHOW COLUMNS FROM rise_events")->getResultArray(), 'Field');

    $hasStartDate = in_array('start_date', $cols);
    $hasEndDate   = in_array('end_date', $cols);
    $hasStartTime = in_array('start_time', $cols);
    $hasEndTime   = in_array('end_time', $cols);
    $hasRecording = in_array('recording', $cols);
    $hasConv      = in_array('conv', $cols);

    $select = ['id','title','ig'];
    if ($hasStartDate) $select[] = 'start_date';
    if ($hasEndDate)   $select[] = 'end_date';
    if ($hasStartTime) $select[] = 'start_time';
    if ($hasEndTime)   $select[] = 'end_time';
    if ($hasRecording) $select[] = 'recording';
    if ($hasConv)      $select[] = 'conv';

    $query = $db->table('rise_events')
        ->select(implode(', ', $select))
        ->where("(ig IS NOT NULL AND ig != '')", null, false)
        ->get();

    if ($query === false) {
        return $this->response->setJSON(['ok' => false, 'sql_error' => $db->error()]);
    }

    $rows = $query->getResultArray();

$now = new \DateTime();
$limitPast = (clone $now)->modify('-1 month');

$debugDescartados = [];

foreach ($rows as $r) {
    $raw = $r['ig'] ?? '';
    $norm = $this->normalizeLabel($raw);
    if (!$norm || !isset($slots[$norm])) continue;

    $startStr = !empty($r['start_date']) ? $r['start_date'] . ' ' . ($r['start_time'] ?? '00:00:00') : null;
if (!empty($r['end_date'])) {
    if (!empty($r['end_time']) && $r['end_time'] === '00:00:00') {
        // Interpretar fin de día como 23:59:59
        $endStr = $r['end_date'] . ' 23:59:59';
    } else {
        $endStr = $r['end_date'] . ' ' . ($r['end_time'] ?? '23:59:59');
    }
} else {
    $endStr = null;
}

    $start = $startStr ? new \DateTime($startStr) : null;
    $end   = $endStr   ? new \DateTime($endStr)   : null;

    // 🚫 Descartar eventos sin fin
    if ($end === null) {
        $debugDescartados[] = [
            'id'    => $r['id'],
            'title' => $r['title'],
            'start' => $startStr,
            'end'   => $endStr,
            'motivo'=> 'sin fin'
        ];
        continue;
    }

    // 🚫 Descartar eventos ya terminados
    if ($end < $now) {
        $debugDescartados[] = [
            'id'    => $r['id'],
            'title' => $r['title'],
            'start' => $startStr,
            'end'   => $endStr,
            'motivo'=> 'ya terminó'
        ];
        continue;
    }

    // 🚫 Descartar eventos muy viejos (más de un mes)
    if ($end < $limitPast) {
        $debugDescartados[] = [
            'id'    => $r['id'],
            'title' => $r['title'],
            'start' => $startStr,
            'end'   => $endStr,
            'motivo'=> 'más de un mes'
        ];
        continue;
    }

    // ✅ Evento válido
    $slots[$norm]['event_id']  = (int)$r['id'];
    $slots[$norm]['title']     = $r['title'] ?: 'En curso';
    $slots[$norm]['color']     = '#00d46a';
    $slots[$norm]['start']     = $startStr;
    $slots[$norm]['end']       = $endStr;
    $slots[$norm]['recording'] = $hasRecording ? (int)($r['recording'] ?? 0) : null;
    $slots[$norm]['conv']      = $hasConv ? ($r['conv'] ?? null) : null;
}

return $this->response->setJSON([
    'ok'    => true,
    'slots' => array_values($slots),
    'debug_descartados' => $debugDescartados
]);

}


    /** Devuelve el listado de labels IG */
    private function labelsIG(): array
    {
        return $this->labelsIG;
    }

    /** Normaliza valores tipo "IG 16.4", "ig16,4", "IG-101.8 1080i" a "IG 16.4" */
    private function normalizeLabel(?string $raw): ?string
    {
        if (!$raw) return null;
        $u = strtoupper(trim($raw));
        // acepta punto o coma, con separadores opcionales
        if (preg_match('/IG[\s\-]*([0-9]+)[\.\,]([0-9]+)/', $u, $m)) {
            return 'IG ' . (int)$m[1] . '.' . (int)$m[2];
        }
        return null;
    }

    /** (Opcional) Limitar por ventana horaria real */
    private function isNowBetween(?string $start, ?string $end): bool
    {
        if (!$start && !$end) return true;
        $now = time();
        $s = $start ? strtotime($start) : null;
        $e = $end ? strtotime($end) : null;
        if ($s && $now < $s) return false;
        if ($e && $now > $e) return false;
        return true;
    }
}


