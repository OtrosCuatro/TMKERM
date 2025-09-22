<?php

namespace App\Controllers;

class Picker_options extends Security_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->access_only_admin();
    }

    public function save()
    {
        $tipo    = $this->request->getPost("tipo");
        $nombres = explode(",", $this->request->getPost("nombres"));

        $db = db_connect();
        foreach ($nombres as $nombre) {
            $nombre = trim($nombre);
            if ($nombre) {
                $db->table("picker_options")->insert([
                    "tipo"   => $tipo,
                    "nombre" => $nombre
                ]);
            }
        }

        return redirect()->to(get_uri("custom_fields/view#custom-field-picker_options"));
    }

    public function json()
    {
        $db   = db_connect();
        $rows = $db->table("picker_options")
                   ->orderBy('nombre', 'ASC')
                   ->get()
                   ->getResult();

        $out = [];
        foreach ($rows as $r) {
            $out[$r->tipo][] = [
                "id"     => $r->id,
                "nombre" => $r->nombre
            ];
        }

        return $this->response->setJSON($out);
    }

    public function delete_by_tipo($tipo = null)
{
    if (! $tipo) {
        return $this->response->setJSON([
            "success" => false,
            "message" => "Tipo faltante"
        ]);
    }

    $db = db_connect();
    $db->table("picker_options")->where("tipo", $tipo)->delete();

    return $this->response->setJSON(["success" => true]);
}

public function list_all()
{
    $db = \Config\Database::connect();
    $builder = $db->table('picker_options');
    $rows = $builder->orderBy('nombre', 'ASC')->get()->getResultArray();

    $result = [];
    foreach ($rows as $row) {
        $tipo = $row['tipo'];
        if (!isset($result[$tipo])) {
            $result[$tipo] = [];
        }
        $result[$tipo][] = $row['nombre'];
    }

    return $this->response->setJSON($result);
}

public function list_by_type($tipo)
{
    $db = \Config\Database::connect();
    $builder = $db->table('picker_options');
    $builder->select('nombre');
    $builder->where('tipo', $tipo);
    $builder->orderBy('nombre', 'ASC');
    $query = $builder->get();

    $results = [];
    foreach ($query->getResult() as $row) {
        $results[] = ["id" => $row->nombre, "text" => $row->nombre];
    }

    return $this->response->setJSON($results);
}



    public function delete($id = null)
    {
        if (! $id) {
            return $this->response->setJSON([
                "success" => false,
                "message" => "ID faltante"
            ]);
        }

        $db = db_connect();
        $db->table("picker_options")->where("id", $id)->delete();

        return $this->response->setJSON(["success" => true]);
    }
}
