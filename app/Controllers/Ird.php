<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class Ird extends Controller {
    public function status() {
        // Simulación: deberías sacar estos valores de tu DB o lógica
        $total = 10; 
        $en_uso = 4;

        return $this->response->setJSON([
            "total" => $total,
            "en_uso" => $en_uso
        ]);
    }
}


