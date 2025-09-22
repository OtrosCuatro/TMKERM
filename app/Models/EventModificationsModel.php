<?php

namespace App\Models;

use CodeIgniter\Model;

class EventModificationsModel extends Model {
    protected $table = 'event_modifications';
    protected $primaryKey = 'id';
    protected $allowedFields = ['event_id', 'user_id', 'modification_time', 'modification_details'];
    public $timestamps = false;

    // ✅ Nuevo método para traer historial de modificaciones con nombres de usuario
    public function get_details_by_event_id($event_id) {
        return $this->db
            ->table($this->table)
            ->select("event_modifications.*, users.first_name, users.last_name, users.image")
            ->join("users", "users.id = event_modifications.user_id")
            ->where("event_modifications.event_id", $event_id)
            ->orderBy("event_modifications.modification_time", "desc")
            ->get()
            ->getResult();
    }
}
