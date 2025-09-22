<?php
class Other_model extends Crud_model {

    public function get_asi_ip_by_event_id($event_id) {
        $this->db->select('asi_ip');
        $this->db->from('rise_custom_fields');
        $this->db->where('event_id', $event_id); // Ajusta el nombre del campo según sea necesario
        $query = $this->db->get();

        if ($query->num_rows() > 0) {
            return $query->row()->asi_ip; // Devuelve el valor de asi_ip
        } else {
            return null; // Si no encuentra nada, retorna null
        }
    }
}
?>
