<?php

namespace App\Controllers;


use App\Models\Events_model;
use Config\Database;
use App\Controllers\Security_Controller;
use Pusher\Pusher;


helper(['general', 'html']);

class Events extends Security_Controller {

    // Carga la vista del calendario
    function index($encrypted_event_id = "") {
        if ($this->login_user->user_type === "staff") {
            $this->check_module_availability("module_event");
        } else {
            if (!$this->can_client_access("event")) {
                app_redirect("forbidden");
            }
        }

        $view_data['job_title'] = $this->login_user->role_title;
        $view_data['encrypted_event_id'] = clean_data($encrypted_event_id);
        $view_data['calendar_filter_dropdown'] = $this->get_calendar_filter_dropdown();
        $view_data['event_labels_dropdown'] = json_encode($this->make_labels_dropdown("event", "", true, app_lang("event_label")));

        return $this->template->rander("events/index", $view_data);
    }

    private function can_share_events() {
        if ($this->login_user->user_type === "staff") {
            return get_array_value($this->login_user->permissions, "disable_event_sharing") == "1" ? false : true;
        }
    }

    public function get_sharing_options_view($return_json = false, $model_info = null) {
        $view_data["id"] = isset($model_info->id) ? $model_info->id : $this->request->getPost('id');
        $view_data["client_id"] = isset($model_info->client_id) ? $model_info->client_id : $this->request->getPost('client_id');
        $view_data["share_with"] = isset($model_info->share_with) ? $model_info->share_with : $this->request->getPost('share_with');

        $view_data["options"] = array("only_me", "all_team_members", 'specific_members_and_teams', 'all_contacts_of_the_client', 'specific_contacts_of_the_client');
        $view_data["members_and_teams_dropdown_source_url"] = get_uri("events/get_members_and_teams_dropdown");
        $view_data["client_contacts_of_selected_client_source_url"] = get_uri("events/get_all_contacts_of_client");

        $sharing_options_view = view("includes/sharing_options", $view_data);

        if ($return_json) {
            return json_encode(array("sharing_options_view" => $sharing_options_view));
        } else {
            return $sharing_options_view;
        }
    }

    public function save_quick_events()
{
    $eventModel = new Events_model();
    $events = $this->request->getPost('events');

    if (!$events || !is_array($events)) {
        return $this->response->setJSON([
            'status' => 'error',
            'message' => 'No se recibieron eventos válidos'
        ])->setStatusCode(400);
    }

    foreach ($events as $event) {
        $data = [
            'title'       => $event['title'] ?? '',
            'description' => $event['description'] ?? '',
            'start_date'  => $event['start_date'] ?? '',
            'end_date'    => $event['end_date'] ?? '',
            'start_time'  => $event['start_time'] ?? '',
            'end_time'    => $event['end_time'] ?? '',
            'extra'       => $event['extra'] ?? '',
            'created_by'  => session('user_id') ?? 1,
        ];

        if (!$eventModel->insert($data)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Error al guardar evento',
                'error' => $eventModel->errors() // <-- Esto mostrará los errores
            ])->setStatusCode(500);
        }
    }

    return $this->response->setJSON([
        'status' => 'success',
        'message' => 'Eventos guardados correctamente'
    ]);
}  

    //show add/edit event modal form
    function modal_form() {
        $encrypted_event_id = $this->request->getPost('encrypted_event_id');
        if (!$encrypted_event_id) {
            $encrypted_event_id = "";
        }
        $event_id = decode_id($encrypted_event_id, "event_id");
        validate_numeric_value($event_id);

        $model_info = $this->Events_model->get_one($event_id);

        $model_info->start_date = $model_info->start_date ? $model_info->start_date : $this->request->getPost('start_date');
        $model_info->end_date = $model_info->end_date ? $model_info->end_date : $this->request->getPost('end_date');
        $model_info->start_time = $model_info->start_time ? $model_info->start_time : $this->request->getPost('start_time');
        $model_info->end_time = $model_info->end_time ? $model_info->end_time : $this->request->getPost('end_time');

        $view_data['client_id'] = $this->request->getPost('client_id');

        //don't show clients dropdown for lead's estimate editing
        $client_info = $this->Clients_model->get_one($model_info->client_id);
        if ($client_info->is_lead) {
            $view_data['client_id'] = $client_info->id;
        }

        $view_data['model_info'] = $model_info;
        $view_data['members_and_teams_dropdown'] = $this->get_members_and_teams_dropdown();
        $view_data['time_format_24_hours'] = get_setting("time_format") == "24_hours" ? true : false;

        //prepare clients dropdown, check if user has permission to access the client

        $clients_dropdown = array();
        if ($this->_can_access_clients()) {
            $clients_dropdown = $this->get_clients_and_leads_dropdown(true);
        }

        $view_data['clients_dropdown'] = $clients_dropdown;

        $view_data["can_share_events"] = $this->can_share_events();

        //prepare label suggestion dropdown
        $view_data['label_suggestions'] = $this->make_labels_dropdown("event", $model_info->labels);

        $view_data['get_sharing_options_view'] = $this->get_sharing_options_view(false, $model_info);

        $view_data["custom_fields"] = $this->Custom_fields_model->get_combined_details("events", $view_data['model_info']->id, $this->login_user->is_admin, $this->login_user->user_type)->getResult();

        return $this->template->view('events/modal_form', $view_data);
    }

    function _can_access_clients() {
        $client_access_info = $this->get_access_info("client");
        return $this->login_user->is_admin || $client_access_info->access_type == "all";
    }

    function get_members_and_teams_dropdown() {
        return json_encode(get_team_members_and_teams_select2_data_list(true));
    }

    //save an event
    function save() {
        $type = $this->request->getPost('type');
        $validation_array = array(
            "id" => "numeric",
            "title" => "required",
            "start_date" => "required"
        );

        //check event access permission for client
        if ($this->login_user->user_type === "client" && $type !== "reminder") {
            if (!$this->can_client_access("event")) {
                app_redirect("forbidden");
            }
        }

        if ($type === "reminder") {
            $validation_array["start_time"] = "required";
        }

        $this->validate_submitted_data($validation_array);

        $id = $this->request->getPost('id');

        //convert to 24hrs time format
        $start_time = $this->request->getPost('start_time');
        $end_time = $this->request->getPost('end_time');

        if (get_setting("time_format") != "24_hours") {
            $start_time = convert_time_to_24hours_format($start_time);
            $end_time = convert_time_to_24hours_format($end_time);
        }

        $share_with = $this->request->getPost('share_with');
        validate_share_with_value($share_with);

        $labels = $this->request->getPost('labels');
        validate_list_of_numbers($labels);

        $start_date = $this->request->getPost('start_date');
        $end_date = $this->request->getPost('end_date');

        

        if ($type != "reminder") {

            $_end_date = $end_date ? $end_date : $start_date; //we can save event without end data. It's for calculation only

            if ($start_date == $_end_date && $start_time && $start_time != "00:00:00" && (!$end_time || $end_time == "00:00:00")) {
                //user added start date without any end date on the same day. Which is invalid. Add end of the day time. 
                $end_time = "23:59:59";
            }

            $start_date_time = strtotime($start_date . " " . $start_time);
            $end_date_time = strtotime($_end_date . " " . $end_time);

            if ($start_date == $_end_date && ($end_date_time < $start_date_time)) {
                echo json_encode(array("success" => false, 'message' => app_lang('end_date_must_be_equal_or_greater_than_start_date')));
                exit();
            }
        }



        $recurring = $this->request->getPost('recurring') ? 1 : 0;
        $repeat_every = $this->request->getPost('repeat_every');
        $repeat_type = $this->request->getPost('repeat_type');
        $no_of_cycles = $this->request->getPost('no_of_cycles');
        $client_id = $this->request->getPost('client_id');

        $target_path = get_setting("timeline_file_path");
        $files_data = move_files_from_temp_dir_to_permanent_dir($target_path, "event");
        $new_files = unserialize($files_data);

        $data = array(
            "title" => $this->request->getPost('title'),
            "description" => $this->request->getPost('description'),
            "start_date" => $start_date,
            "start_time" => $start_time,
            "end_time" => $end_time,
            "location" => $this->request->getPost('location'),
            "labels" => $labels,
            "color" => $this->request->getPost('color'),
            "created_by" => $this->login_user->id,
            "share_with" => $share_with,
            "recurring" => $recurring,
            "repeat_every" => $repeat_every,
            "repeat_type" => $repeat_type ? $repeat_type : NULL,
            "no_of_cycles" => $no_of_cycles ? $no_of_cycles : 0,
            "client_id" => $client_id ? $client_id : 0,
            "type" => $type ? $type : "event",
            "task_id" => $this->request->getPost('task_id'),
            "project_id" => $this->request->getPost('project_id'),
            "lead_id" => $this->request->getPost('lead_id'),
            "ticket_id" => $this->request->getPost('ticket_id'),
            "proposal_id" => $this->request->getPost('proposal_id'),
            "contract_id" => $this->request->getPost('contract_id'),
            "subscription_id" => $this->request->getPost('subscription_id'),
            "invoice_id" => $this->request->getPost('invoice_id'),
            "order_id" => $this->request->getPost('order_id'),
            "estimate_id" => $this->request->getPost('estimate_id'),
            "ingesta" => $this->request->getPost('ingesta'),
            "asi_ip" => $this->request->getPost('asi_ip'),
            "conv" => $this->request->getPost('conv'),
            "ig" => $this->request->getPost('ig'),
            "extra" => $this->request->getPost('extra'),
            "smd" => $this->request->getPost('smd'),
            "smd_color" => $this->request->getPost('smd_color'),
"ig_color" => $this->request->getPost('ig_color'),
"conv_color" => $this->request->getPost('conv_color'),
"asi_ip_color" => $this->request->getPost('asi_ip_color'),
"extra_bg_color" => $this->request->getPost('extra_bg_color'),
"extra_text_color" => $this->request->getPost('extra_text_color'),

            



        );

        if ($end_date) {
            $data["end_date"] = $end_date;
        }

        if (!$id) {
            $data["confirmed_by"] = 0;
            $data["rejected_by"] = 0;
        }

        //prepare a comma sepearted dates of start date.
        $recurring_dates = "";
        $last_start_date = NULL;

        if ($recurring) {
            $no_of_cycles = $this->Events_model->get_no_of_cycles($repeat_type, $no_of_cycles);

            for ($i = 1; $i <= $no_of_cycles; $i++) {
                $start_date = add_period_to_date($start_date, $repeat_every, $repeat_type);
                $recurring_dates .= $start_date . ",";

                $last_start_date = $start_date; //collect the last start date
            }
        }

        $data["recurring_dates"] = $recurring_dates;
        $data["last_start_date"] = $last_start_date;

        if (!$this->can_share_events()) {
            $data["share_with"] = "";
        }


//only admin can edit other team members events
//non-admin team members can edit only their own events
if ($id && !$this->login_user->is_admin) {
    $event_info = $this->Events_model->get_one($id);

    if (
        $event_info->created_by != $this->login_user->id
        && get_array_value($this->login_user->permissions, "can_edit_public_events") !== "1"
    ) {
        app_redirect("forbidden");
    }
}


        if ($id) {
            $event_info = $this->Events_model->get_one($id);
            $timeline_file_path = get_setting("timeline_file_path");

            $new_files = update_saved_files($timeline_file_path, $event_info->files, $new_files);
        }

        $data["files"] = serialize($new_files);

        $data = clean_data($data);

        $save_id = $this->Events_model->ci_save($data, $id);

        $model = new \App\Models\EventModificationsModel();
        

        $model->insert([
            'event_id' => $save_id,
            'user_id' => $this->login_user->id,
            'modification_time' => date('Y-m-d H:i:s'),
            'modification_details' => 'Evento actualizado',
        ]);

        if ($save_id) {
        
            //if the google calendar is integrated, add/modify the event
            if ($type !== "reminder" && get_setting("enable_google_calendar_api") && get_setting('user_' . $this->login_user->id . '_integrate_with_google_calendar') && (get_setting("google_calendar_authorized") || get_setting('user_' . $this->login_user->id . '_google_calendar_authorized'))) {
                $this->Google_calendar_events->save_event($this->login_user->id, $save_id);
            }

            save_custom_fields("events", $save_id, $this->login_user->is_admin, $this->login_user->user_type);

            if ($type === "reminder") {
                $reminder_info = $this->Events_model->get_one($save_id);
                $success_data = $this->_make_reminder_row($reminder_info);
                echo json_encode(array("success" => true, "id" => $save_id, "data" => $success_data, 'message' => app_lang('record_saved'), "reminder_info" => $reminder_info));
            } else {
                echo json_encode(array("success" => true, 'message' => app_lang('record_saved')));

                require_once(APPPATH . "ThirdParty/Pusher/vendor/autoload.php");

$pusher = new \Pusher\Pusher(
    get_setting("pusher_key"),
    get_setting("pusher_secret"),
    get_setting("pusher_app_id"),
    [
        'cluster' => get_setting("pusher_cluster"),
        'useTLS' => true
    ]
);

$all = [
    'title'        => $data['title']        ?? null,
    'description'  => $data['description']  ?? null,
    'start_date'   => $data['start_date']   ?? null,
    'end_date'     => $data['end_date']     ?? null,
    'start_time'   => $data['start_time']   ?? null,
    'end_time'     => $data['end_time']     ?? null,
    'color'        => $data['color']        ?? null,
    'smd'          => $data['smd']          ?? null,
    'smd_color'    => $data['smd_color']    ?? null,
    'asi_ip'       => $data['asi_ip']       ?? null,
    'asi_ip_color' => $data['asi_ip_color'] ?? null,
    'ig'           => $data['ig']           ?? null,
    'ig_color'     => $data['ig_color']     ?? null,
    'conv'         => $data['conv']         ?? null,
    'conv_color'   => $data['conv_color']   ?? null,
    'extra'        => $data['extra']        ?? null,
    'ingesta'      => $data['ingesta']      ?? null,
];

// Filtrar: nada de null ni strings vacíos
$patch = array_filter($all, function($v){
    if ($v === null) return false;
    if (is_string($v) && trim($v) === '') return false;
    return true;
});

$pusher->trigger('calendar-channel', 'event-updated', [
    'event_id' => (string) $save_id,
    'updated_fields' => $patch
]);




            }

            if ($share_with) {
                if ($id) {
                    //the event modified and shared with others, log the notificaiton
                    log_notification("calendar_event_modified", array("event_id" => $save_id));
                } else {
                    //new event added and shared with others, log the notificaiton
                    log_notification("new_event_added_in_calendar", array("event_id" => $save_id));
                }
            }
                 


        } else {
            
            echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
        }
        
    }


    public function update_field()
{
    $event_id = $this->request->getPost("event_id");
    $field = $this->request->getPost("field");
    $value = $this->request->getPost("value");

    if (!$event_id || !$field) {
        return $this->response->setJSON(["success" => false]);
    }

    $db = \Config\Database::connect();
    $builder = $db->table('rise_events');
    $builder->where('id', $event_id);
    $builder->update([$field => $value]);

    return $this->response->setJSON(["success" => true]);
}

public function update_event_fields()
{
    $id = $this->request->getPost("id");
    if (!$id) {
        return $this->response->setJSON([
            "success" => false,
            "message" => "ID no especificado."
        ]);
    }

    // Claves permitidas
    $allowed = [
        'smd','asi_ip','conv','ingesta','extra','ig',
        'smd_color','asi_ip_color','conv_color','ig_color',
        'extra_bg_color','extra_text_color','color' // dejamos color general
    ];

    $incoming = $this->request->getPost(null);
    $patch = [];

    foreach ($allowed as $f) {
        if (array_key_exists($f, $incoming)) {
            $val = $incoming[$f];
            if ($val === null || (is_string($val) && trim($val) === '')) {
                $patch[$f] = null;
                continue;
            }
            $patch[$f] = $val;
        }
    }

    // soporte clear[]=...
    $clear = $this->request->getPost('clear');
    if (is_array($clear)) {
        foreach ($clear as $key) {
            if (in_array($key, $allowed, true)) {
                $patch[$key] = null;
            }
        }
    }

    if (empty($patch)) {
        return $this->response->setJSON([
            "success" => false,
            "message" => "Nada para actualizar"
        ]);
    }

    $ok = $this->Events_model->update($id, $patch);
    if (!$ok) {
        return $this->response->setJSON(["success" => false]);
    }

    // === Guardar historial ===
    try {
        $model = new \App\Models\EventModificationsModel();

        // Map campos
        $fieldLabels = [
            'smd' => 'SMD/NOD',
            'asi_ip' => 'ASI IP',
            'conv' => 'Conversor',
            'ingesta' => 'Ingesta',
            'extra' => 'Extra',
            'ig' => 'IG',
        ];

        // Traducciones de colores a estados
        $colorStates = [
            'extra_bg_color' => [
                '#fb8c00' => 'Cargado',
                '#4caf50' => 'Chequeado',
                '#e53935' => 'Faltan datos',
            ],
            'ig_color' => [
                '#4caf50' => 'Grabando',
                '#00b5e2' => 'Finalizado',
            ]
        ];

        foreach ($patch as $campo => $valor) {
            $nombreCampo = $fieldLabels[$campo] ?? $campo;

            $texto = null;

            // 1. Traducción especial por color
            if (isset($colorStates[$campo])) {
                $estado = $colorStates[$campo][strtolower($valor)] ?? null;
                if ($estado) {
                    $texto = "Acción rápida: {$estado}";
                }
            }

            // 2. Campos de datos importantes (no colores técnicos)
            if (!$texto && isset($fieldLabels[$campo])) {
                if ($valor !== null && $valor !== "") {
                    $texto = "Edición rápida: {$nombreCampo} → {$valor}";
                } else {
                    $texto = "Edición rápida: {$nombreCampo} (limpiado)";
                }
            }

            // 3. Ignorar cambios de colores técnicos (smd_color, conv_color, color, etc.)
            //    solo se guardan si entran en 1 o 2

            if ($texto) {
                $model->insert([
                    'event_id' => $id,
                    'user_id' => $this->login_user->id,
                    'modification_time' => date('Y-m-d H:i:s'),
                    'modification_details' => $texto,
                ]);
            }
        }
    } catch (\Exception $e) {
        log_message('error', '[Historial eventos] '.$e->getMessage());
    }

    // Emitir al pusher
    require_once(APPPATH . 'ThirdParty/Pusher/vendor/autoload.php');
    $pusher = new \Pusher\Pusher(
        config('Pusher')->key,
        config('Pusher')->secret,
        config('Pusher')->app_id,
        [
            'cluster' => config('Pusher')->cluster,
            'useTLS' => config('Pusher')->useTLS
        ]
    );

    $pusher->trigger('calendar-channel', 'event-updated', [
        'event_id' => (string) $id,
        'updated_fields' => $patch
    ]);

    return $this->response->setJSON([
        "success" => true,
        "message" => "Evento actualizado"
    ]);
}



    function delete() {
        $id = $this->request->getPost('id'); //reminder
        if (!$id) { //event
            $this->validate_submitted_data(array(
                "encrypted_event_id" => "required"
            ));

            $id = decode_id($this->request->getPost('encrypted_event_id'), "event_id"); //to make is secure we'll use the encrypted id
        }

        $event_info = $this->Events_model->get_one($id);

if ($id && !$this->login_user->is_admin) {
    $can_delete = get_array_value($this->login_user->permissions, "can_delete_event") === "1";

    if (!$can_delete && $event_info->created_by != $this->login_user->id) {
        app_redirect("forbidden");
    }
}



        if ($this->Events_model->delete($id)) {
            //if there has event associated with this on google calendar, delete that too
            if (get_setting("enable_google_calendar_api") && $event_info->google_event_id && $event_info->editable_google_event && get_setting('user_' . $this->login_user->id . '_integrate_with_google_calendar') && (get_setting("google_calendar_authorized") || get_setting('user_' . $this->login_user->id . '_google_calendar_authorized'))) {
                $this->Google_calendar_events->delete($event_info->google_event_id, $this->login_user->id);
            }

            //delete the files
            $file_path = get_setting("timeline_file_path");
            if ($event_info->files) {
                $files = unserialize($event_info->files);

                foreach ($files as $file) {
                    delete_app_files($file_path, array($file));
                }
            }

            echo json_encode(array("success" => true, 'message' => app_lang('event_deleted')));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('record_cannot_be_deleted')));
        }
    }

    public function get_picker_options($type) {
    $db = \Config\Database::connect();
    $builder = $db->table('picker_options');
    $builder->where('type', $type);
    $builder->orderBy('name', 'ASC');
    $query = $builder->get();

    $options = [];
    foreach ($query->getResult() as $row) {
        $options[] = $row->name;
    }

    return $this->response->setJSON($options);
}


    //get calendar event
    function calendar_events($filter_values = "", $event_label_id = 0, $client_id = 0) {
        $start = isset($_GET["start"]) ? $_GET["start"] : null;
        $end = isset($_GET["end"]) ? $_GET["end"] : null;
    
        // Si no tienes start o end, puedes devolver un error o los eventos predeterminados.
        if (!$start || !$end) {
            echo json_encode(["success" => false, "message" => "Missing start or end parameters"]);
            return;
        }
        
        $result = array();

        $filter_values_array = explode('-', $filter_values);

        if (in_array("events", $filter_values_array)) {
            //get all events
            $is_client = false;
            if ($this->login_user->user_type == "client") {
                $is_client = true;
            }

            validate_numeric_value($event_label_id);
            validate_numeric_value($client_id);
            $options_of_events = array("user_id" => $this->login_user->id, "team_ids" => $this->login_user->team_ids, "client_id" => $client_id, "start_date" => $start, "end_date" => $end, "include_recurring" => true, "is_client" => $is_client, "label_id" => $event_label_id);

            $list_data_of_events = $this->Events_model->get_details($options_of_events)->getResult();

            foreach ($list_data_of_events as $data) {

                //check if this recurring event, generate recurring evernts based on the condition

                $data->cycle = 0; //it's required to calculate the recurring events

                $result[] = $this->_make_calendar_event($data); //add regular event

                if ($data->recurring) {
                    $no_of_cycles = $this->Events_model->get_no_of_cycles($data->repeat_type, $data->no_of_cycles);

                    for ($i = 1; $i <= $no_of_cycles; $i++) {
                        $data->start_date = add_period_to_date($data->start_date, $data->repeat_every, $data->repeat_type);
                        $data->end_date = add_period_to_date($data->end_date, $data->repeat_every, $data->repeat_type);
                        $data->cycle = $i;

                        $result[] = $this->_make_calendar_event($data);
                    }
                }
            }
        }

        if (in_array("leave", $filter_values_array) && $this->login_user->user_type == "staff") {
            //get all approved leaves
            $leave_access_info = $this->get_access_info("leave");
            $options_of_leaves = array("start_date" => $start, "end_date" => $end, "login_user_id" => $this->login_user->id, "access_type" => $leave_access_info->access_type, "allowed_members" => $leave_access_info->allowed_members, "status" => "approved");

            $list_data_of_leaves = $this->Leave_applications_model->get_list($options_of_leaves)->getResult();

            foreach ($list_data_of_leaves as $leave) {
                $result[] = $this->_make_leave_event($leave);
            }
        }

        if (in_array("project_deadline", $filter_values_array) || in_array("project_start_date", $filter_values_array)) {
            //get all project deadlines
            $options = array(
                "status_id" => 1,
                "start_date" => $start,
                "deadline" => $end,
                "client_id" => $client_id,
                "for_events_table" => true
            );

            if ($this->login_user->user_type == "staff") {
                if (!$this->can_manage_all_projects()) {
                    $options["user_id"] = $this->login_user->id;
                }
            } else {
                $options["client_id"] = $this->login_user->client_id;
            }

            //project start dates
            if (in_array("project_start_date", $filter_values_array)) {
                $options["start_date_for_events"] = true;
                $list_data_of_projects = $this->Projects_model->get_details($options)->getResult();
                if ($list_data_of_projects) {
                    foreach ($list_data_of_projects as $project) {
                        $result[] = $this->_make_project_event($project, true);
                    }
                }
            }

            //project deadlines
            if (in_array("project_deadline", $filter_values_array)) {
                unset($options["start_date_for_events"]);
                $list_data_of_projects = $this->Projects_model->get_details($options)->getResult();
                if ($list_data_of_projects) {
                    foreach ($list_data_of_projects as $project) {
                        $result[] = $this->_make_project_event($project);
                    }
                }
            }
        }

        if ($this->login_user->user_type == "staff" && (in_array("task_deadline", $filter_values_array) || in_array("task_start_date", $filter_values_array))) {
            //get all task deadlines
            $options = array(
                "start_date" => $start,
                "deadline" => $end,
                "show_assigned_tasks_only_user_id" => $this->show_assigned_tasks_only_user_id(),
                "for_events" => true
            );

            //for non-admin users, show only the assigned tasks
            if (!$this->login_user->is_admin) {
                $options["show_assigned_tasks_only_user_id"] =  $this->login_user->id;
            }

            if (in_array("task_deadline", $filter_values_array)) {
                //deadlines
                $options["deadline_for_events"] = true;
                $list_data_of_tasks = $this->Tasks_model->get_details($options)->getResult();
                foreach ($list_data_of_tasks as $task) {
                    $result[] = $this->_make_task_event($task);
                }
            }

            if (in_array("task_start_date", $filter_values_array)) {
                //start dates
                $options["start_date_for_events"] = true;
                $list_data_of_tasks = $this->Tasks_model->get_details($options)->getResult();
                foreach ($list_data_of_tasks as $task) {
                    $result[] = $this->_make_task_event($task, true);
                }
            }
        }

        echo json_encode($result);
    }

    //prepare calendar event
    private function _make_calendar_event($data) {

        $end_time = $data->end_time;
        if ($data->start_date != $data->end_date && $end_time == "00:00:00") {
            $end_time = "23:59:59";
        }
    
        return array(
            'id' => $data->id,
            "title" => $data->title,
            "start" => $data->start_date . " " . $data->start_time,
            "end" => $data->end_date . " " . $end_time,
            "backgroundColor" => $data->color ? $data->color : "#83c340",
            "borderColor" => $data->color ? $data->color : "#83c340",
            "extendedProps" => array(
                "icon" => get_event_icon($data->share_with),
                "asi_ip" => isset($data->asi_ip) ? $data->asi_ip : "", // Se usa $data->asi_ip en lugar de $event->asi_ip
                "conv" => isset($data->conv) ? $data->conv : "", // Se usa $data->asi_ip en lugar de $event->asi_ip
                "ingesta" => isset($data->ingesta) ? $data->ingesta : "", // Se usa $data->asi_ip en lugar de $event->asi_ip
                "ig" => isset($data->ig) ? $data->ig : "", // Se usa $data->asi_ip en lugar de $event->asi_ip
                "extra" => isset($data->extra) ? $data->extra : "", // Se usa $data->asi_ip en lugar de $event->asi_ip
                "smd" => isset($data->smd) ? $data->smd : "", // Se usa $data->asi_ip en lugar de $event->asi_ip
                "encrypted_event_id" => encode_id($data->id, "event_id"), // to make is secure we'll use the encrypted id
                "cycle" => $data->cycle,
                "smd_color" => isset($data->smd_color) ? $data->smd_color : "",
"ig_color" => isset($data->ig_color) ? $data->ig_color : "",
"conv_color" => isset($data->conv_color) ? $data->conv_color : "",
"asi_ip_color" => isset($data->asi_ip_color) ? $data->asi_ip_color : "",
"extra_bg_color"   => isset($data->extra_bg_color) ? $data->extra_bg_color : "",
"extra_text_color" => isset($data->extra_text_color) ? $data->extra_text_color : "",
                "event_type" => "event",



            )
        );
    }

    //prepare approved leave event
    private function _make_leave_event($data) {

        return array(
            "title" => $data->applicant_name,
            "start" => $data->start_date . " " . "00:00:00",
            "end" => $data->end_date . " " . "23:59:59", //show leave applications for the full day
            "backgroundColor" => $data->leave_type_color,
            "borderColor" => $data->leave_type_color,
            "extendedProps" => array(
                "icon" => "log-out",
                "asi_ip" => isset($event->asi_ip) ? $event->asi_ip : "", // Asegurar que asi_ip no sea null

                "leave_id" => $data->id, //to make is secure we'll use the encrypted id
                "cycle" => 0,
                "event_type" => "leave",
            )
        );
    }

    //prepare project deadline event
    private function _make_project_event($data, $start_date_event = false) {
        $color = "#1ccacc"; //future events
        $my_local_time = get_my_local_time("Y-m-d");
        if (($data->deadline && ($my_local_time > $data->deadline)) || (!$data->deadline && $data->start_date && ($my_local_time > $data->start_date))) { //back-dated events
            $color = "#d9534f";
        } else if (($data->deadline && $my_local_time == $data->deadline) || (!$data->deadline && $data->start_date && $my_local_time == $data->start_date)) { //today events
            $color = "#f0ad4e";
        }

        $event_type = "project_deadline";
        $event_custom_class = "event-deadline-border";
        if ($start_date_event) {
            $event_type = "project_start_date";
            $event_custom_class = "";
        }

        return array(
            "title" => $data->title,
            "start" => ($start_date_event ? $data->start_date : $data->deadline) . " " . "00:00:00",
            "end" => ($start_date_event ? $data->start_date : $data->deadline) . " " . "23:59:59", //show project deadline for the full day
            "backgroundColor" => $color,
            "borderColor" => $color,
            "classNames" => $event_custom_class,
            "extendedProps" => array(
                "icon" => "grid",
                "asi_ip" => isset($event->asi_ip) ? $event->asi_ip : "", // Asegurar que asi_ip no sea null

                "project_id" => $data->id,
                "cycle" => 0,
                "event_type" => $event_type,
            )
        );
    }

    //prepare task deadline event
    private function _make_task_event($data, $start_date_event = false) {
        $event_type = "task_deadline";
        $event_custom_class = "event-deadline-border";

        $start = $data->deadline;
        $end = $data->deadline;

        if ($start_date_event) {
            //prepare the event based on start date.
            $event_type = "task_start_date";
            $event_custom_class = "";

            $start = $data->start_date;
            $end = $data->start_date;
        }

        if ($end && date("H:i:s", strtotime($end)) == "00:00:00") {
            $end = get_date_from_datetime($end) . " 23:59:59";
        }

        return array(
            "title" => $data->title,
            "start" => $start,
            "end" => $end, //show task deadline for the full day
            "backgroundColor" => $data->status_color,
            "borderColor" => $data->status_color,
            "classNames" => $event_custom_class,
            "extendedProps" => array(
                "icon" => "list",
                "asi_ip" => isset($event->asi_ip) ? $event->asi_ip : "", // Asegurar que asi_ip no sea null

                "task_id" => $data->id,
                "cycle" => 0,
                "event_type" => $event_type,
            )
        );
    }

    //view an evnet
    function view() {
        $encrypted_event_id = $this->request->getPost('id');
        $cycle = $this->request->getPost('cycle');



        //check access permission for client
        if ($this->login_user->user_type === "client") {
            if (!$this->can_client_access("event")) {
                app_redirect("forbidden");
            }
        }

        $this->validate_submitted_data(array(
            "id" => "required"
        ));

        $view_data = $this->_make_view_data($encrypted_event_id, $cycle);

        return $this->template->view('events/view', $view_data);
    }

    private function _make_view_data($encrypted_event_id, $cycle = "0") {
        $event_id = decode_id($encrypted_event_id, "event_id");
        validate_numeric_value($event_id);
        validate_numeric_value($cycle);

        $model_info = $this->Events_model->get_details(array("id" => $event_id))->getRow();

        if (!$model_info->end_date) {
            $model_info->end_date = $model_info->start_date;
        }

        if ($event_id && $model_info->id) {

            $model_info->cycle = $cycle * 1;

            if ($model_info->recurring && $cycle) {
                $model_info->start_date = add_period_to_date($model_info->start_date, $model_info->repeat_every * $cycle, $model_info->repeat_type);
                $model_info->end_date = add_period_to_date($model_info->end_date, $model_info->repeat_every * $cycle, $model_info->repeat_type);
            }


            $view_data['encrypted_event_id'] = $encrypted_event_id; //to make is secure we'll use the encrypted id 
            $view_data['editable'] = $this->request->getPost('editable');
            $view_data['model_info'] = $model_info;
            $view_data['event_icon'] = get_event_icon($model_info->share_with);
            $view_data['custom_fields_list'] = $this->Custom_fields_model->get_combined_details("events", $event_id, $this->login_user->is_admin, $this->login_user->user_type)->getResult();

            $confirmed_by_array = explode(",", $model_info->confirmed_by);
            $rejected_by_array = explode(",", $model_info->rejected_by);

            //prepare event lable
            $view_data['labels'] = make_labels_view_data($model_info->labels_list, "", true);

            //prepare status lable and status buttons
            $status = "";
            $status_button = "";

            $view_data["status_button"] =
    js_anchor("<i data-feather='check-circle' class='icon-16'></i> Cargado", array(
        "class" => "btn btn-success me-2",
        "onclick" => "update_event_color('" . $encrypted_event_id . "', '#28a745')"
    )) .
    js_anchor("<i data-feather='square' class='icon-16'></i> Asignado", array(
        "class" => "btn btn-warning",
        "onclick" => "update_event_color('" . $encrypted_event_id . "', '#ffc107')"
    ));

        

            //prepare confimed/rejected user's list
            $confimed_rejected_users = $this->_get_confirmed_and_rejected_users_list($confirmed_by_array, $rejected_by_array);

            $view_data['confirmed_by'] = get_array_value($confimed_rejected_users, 'confirmed_by');
            $view_data['rejected_by'] = get_array_value($confimed_rejected_users, 'rejected_by');

            $modifications_model = new \App\Models\EventModificationsModel();
            $view_data["modifications"] = $modifications_model->get_details_by_event_id($event_id);            

            return $view_data;
        } else {
            show_404();
        }
    }

    private function _get_confirmed_and_rejected_users_list($confirmed_by_array, $rejected_by_array) {

        $confirmed_by = "";
        $rejected_by = "";

        $response_by_users = $this->Events_model->get_response_by_users(($confirmed_by_array + $rejected_by_array));
        if ($response_by_users) {
            foreach ($response_by_users->getResult() as $user) {
                $image_url = get_avatar($user->image);
                $response_by_user = "<span data-bs-toggle='tooltip' title='" . $user->member_name . "' class='avatar avatar-xs mr10'><img src='$image_url' alt='...'></span>";

                if ($user->user_type === "client") {
                    $profile_link = get_client_contact_profile_link($user->id, $response_by_user);
                } else {
                    $profile_link = get_team_member_profile_link($user->id, $response_by_user);
                }

                if (in_array($user->id, $confirmed_by_array)) {
                    $confirmed_by .= $profile_link;
                } else {
                    $rejected_by .= $profile_link;
                }
            }
        }

        return array("confirmed_by" => $confirmed_by, "rejected_by" => $rejected_by);
    }

    function save_event_status() {
        $encrypted_event_id = $this->request->getPost('encrypted_event_id');
        $event_id = decode_id($encrypted_event_id, "event_id");
        validate_numeric_value($event_id);

        $status = $this->request->getPost('status');
        $user_id = $this->login_user->id;

        $this->Events_model->save_event_status($event_id, $user_id, $status);

        $view_data = $this->_make_view_data($encrypted_event_id);

        return $this->template->view('events/view', $view_data);
    }

    //get all contacts of a selected client
    function get_all_contacts_of_client($client_id) {
        validate_numeric_value($client_id);

        if ($client_id && $this->_can_access_clients()) {
            $client_contacts = $this->Users_model->get_all_where(array("status" => "active", "client_id" => $client_id, "deleted" => 0))->getResult();
            $client_contacts_array = array();

            if ($client_contacts) {
                foreach ($client_contacts as $contacts) {
                    $client_contacts_array[] = array("type" => "contact", "id" => "contact:" . $contacts->id, "text" => $contacts->first_name . " " . $contacts->last_name);
                }
            }
            echo json_encode($client_contacts_array);
        }
    }

    function google_calendar_settings_modal_form() {
        if (get_setting("enable_google_calendar_api") && (get_setting("google_calendar_authorized") || get_setting('user_' . $this->login_user->id . '_google_calendar_authorized'))) {
            $user_calendar_ids = get_setting('user_' . $this->login_user->id . '_calendar_ids');
            $calendar_ids = $user_calendar_ids ? unserialize($user_calendar_ids) : array();

            return $this->template->view("events/google_calendar_settings_modal_form", array("calendar_ids" => $calendar_ids));
        }
    }

    function save_google_calendar_settings() {
        if (get_setting("enable_google_calendar_api") && (get_setting("google_calendar_authorized") || get_setting('user_' . $this->login_user->id . '_google_calendar_authorized'))) {
            $integrate_with_google_calendar = $this->request->getPost("integrate_with_google_calendar");
            $integrate_with_google_calendar = clean_data($integrate_with_google_calendar);
            $this->Settings_model->save_setting("user_" . $this->login_user->id . "_integrate_with_google_calendar", $integrate_with_google_calendar, "user");

            //save calendar ids
            $calendar_ids_array = $this->request->getPost('calendar_id');
            if (!is_null($calendar_ids_array) && count($calendar_ids_array)) {
                //remove null value
                foreach ($calendar_ids_array as $key => $value) {
                    if (!get_array_value($calendar_ids_array, $key)) {
                        unset($calendar_ids_array[$key]);
                    }
                }

                $calendar_ids_array = array_unique($calendar_ids_array);
                $calendar_ids_array = serialize($calendar_ids_array);
                $calendar_ids_array = clean_data($calendar_ids_array);

                $this->Settings_model->save_setting("user_" . $this->login_user->id . "_calendar_ids", $calendar_ids_array, "user");
            }

            echo json_encode(array("success" => true, 'message' => app_lang('settings_updated')));
        }
    }

    function show_event_in_google_calendar($google_event_id = "") {
        if (!$google_event_id) {
            show_404();
        }

        $event_link = $this->Google_calendar_events->get_event_link($google_event_id, $this->login_user->id);
        $event_link ? app_redirect($event_link, true) : show_404();
    }

    function file_preview($id = "", $key = "") {
        if ($id) {
            validate_numeric_value($id);
            $event_info = $this->Events_model->get_one($id);
            $files = unserialize($event_info->files);
            $file = get_array_value($files, $key);

            $file_name = get_array_value($file, "file_name");
            $file_id = get_array_value($file, "file_id");
            $service_type = get_array_value($file, "service_type");

            $view_data["file_url"] = get_source_url_of_file($file, get_setting("timeline_file_path"));
            $view_data["is_image_file"] = is_image_file($file_name);
            $view_data["is_iframe_preview_available"] = is_iframe_preview_available($file_name);
            $view_data["is_google_preview_available"] = is_google_preview_available($file_name);
            $view_data["is_viewable_video_file"] = is_viewable_video_file($file_name);
            $view_data["is_google_drive_file"] = ($file_id && $service_type == "google") ? true : false;
            $view_data["is_iframe_preview_available"] = is_iframe_preview_available($file_name);

            return $this->template->view("events/file_preview", $view_data);
        } else {
            show_404();
        }
    }

    function reminders() {
        $this->can_create_reminders();
        $view_data["project_id"] = $this->request->getPost("project_id");
        $view_data["client_id"] = $this->request->getPost("client_id");
        $view_data["lead_id"] = $this->request->getPost("lead_id");
        $view_data["ticket_id"] = $this->request->getPost("ticket_id");
        $view_data["reminder_view_type"] = $this->request->getPost("reminder_view_type");
        return $this->template->view("reminders/index", $view_data);
    }

    function reminders_list_data($type = "", $reminder_context = "", $reminder_context_id = 0) {
        validate_numeric_value($reminder_context_id);
        $this->can_create_reminders();

        $options = array(
            "user_id" => $this->login_user->id,
            "type" => "reminder"
        );

        if ($reminder_context != "global") {
            $reminder_context_id_key = $reminder_context . "_id";
            $options["$reminder_context_id_key"] = $reminder_context_id;
        }

        if ($type !== "all") {
            $options["reminder_start_date_time"] = get_my_local_time("Y-m-d H:i") . ":00";
            $options["reminder_status"] = "new";
        }

        $list_data = $this->Events_model->get_details($options)->getResult();

        $result = array();
        foreach ($list_data as $data) {
            $result[] = $this->_make_reminder_row($data);
        }
        echo json_encode(array("data" => $result));
    }

    private function _make_reminder_row($data = array()) {
        $reminder_status_value = "done";

        if ($data->reminder_status === "done" || $data->reminder_status === "shown") {
            $reminder_status_value = "new";
        }

        $context_info = get_reminder_context_info($data);
        $context_icon = get_array_value($context_info, "context_icon");
        $context_icon = $context_icon ? "<i class='icon-14 text-off' data-feather='$context_icon'></i> " : "";
        $context_url = get_array_value($context_info, "context_url");
        $title_value = "<span class='strong'>$context_icon" . ($context_url ? anchor($context_url, $data->title) : link_it($data->title)) . "</span>";

        $icon = "";
        $target_date = "";
        if ($data->snoozing_time) {
            $icon = "<span class='icon-14 text-off'>" . view("reminders/svg_icons/snooze") . "</span>";
            $target_date = new \DateTime($data->snoozing_time);
        } else if ($data->recurring) {
            $icon = "<i class='icon-14 text-off' data-feather='repeat'></i>";

            if ($data->next_recurring_time) {
                $target_date = new \DateTime($data->next_recurring_time);
            }
        }

        if ($target_date) {
            //assign dedicated values to main start and end date time to work with existing method
            $data->start_date = $target_date->format("Y-m-d");
            $data->start_time = $target_date->format("H:i:s");
        }

        $data->end_date = $data->start_date;
        $time_value = view("events/event_time", array("model_info" => $data, "is_reminder" => true));
        $time_value = "<div class='small'>$icon " . $time_value . "</div>";

        //show left border for missed reminders
        $missed_reminder_class = "";
        $local_time = get_my_local_time("Y-m-d H:i") . ":00";

        if ($data->reminder_status === 'new' && ($data->start_date . ' ' . $data->start_time) < $local_time && $data->snoozing_time < $local_time && $data->next_recurring_time < $local_time) {
            $missed_reminder_class = "missed-reminder";
        }

        $title = "<span class='$missed_reminder_class'>" . $title_value . $time_value . "</span>";

        $delete = '<li role="presentation">' . js_anchor("<i data-feather='x' class='icon-16'></i>" . app_lang('delete'), array('title' => app_lang('delete_reminder'), "class" => "delete dropdown-item reminder-action", "data-id" => $data->id, "data-post-id" => $data->id, "data-action-url" => get_uri("events/delete"), "data-action" => "delete", "data-undo" => "0")) . '</li>';

        $status = '<li role="presentation">' . js_anchor("<i data-feather='check-circle' class='icon-16'></i> " . app_lang('mark_as_done'), array('title' => app_lang('mark_as_done'), "class" => "dropdown-item reminder-action", "data-action-url" => get_uri("events/save_reminder_status/$data->id/done"), "data-action" => "delete", "data-undo" => "0")) . '</li>';
        if ($data->reminder_status === "done" || $data->reminder_status === "shown") {
            $status = "";
        }

        $options = '<span class="dropdown inline-block">
                        <div class="dropdown-toggle clickable p10" type="button" data-bs-toggle="dropdown" aria-expanded="true" data-bs-display="static">
                            <i data-feather="more-horizontal" class="icon-16"></i>
                        </div>
                        <ul class="dropdown-menu dropdown-menu-end" role="menu">' . $status . $delete . '</ul>
                    </span>';

        if ($missed_reminder_class) {
            //show direct option to complete the missed reminders
            $options = js_anchor("<i data-feather='check-circle' class='icon-16'></i>", array('title' => app_lang('mark_as_done'), "class" => "reminder-action p10", "data-action-url" => get_uri("events/save_reminder_status/$data->id/done"), "data-action" => "delete", "data-undo" => "0"));
        }

        return array(
            $data->start_date . " " . $data->start_time, //for sort
            $title,
            $options
        );
    }

    private function can_access_this_reminder($reminder_info) {
        if ($reminder_info->created_by === $this->login_user->id) {
            //this user is the creator of the event/reminder
            return true;
        }

        if ($reminder_info->share_with) {
            //this user is not the creator of the event/reminder
            //check in shared users
            $shared_users = $this->Events_model->get_share_with_users_of_event($reminder_info)->getResult();
            foreach ($shared_users as $user) {
                if ($user->id === $this->login_user->id) {
                    return true;
                }
            }
        }

        app_redirect("forbidden");
    }

    private function can_create_reminders() {
        if (get_setting("module_reminder") && ($this->login_user->user_type === "staff" || ($this->login_user->user_type === "client" && get_setting("client_can_create_reminders")))) {
            return true;
        }

        app_redirect("forbidden");
    }

    function save_reminder_status($id = 0, $status = "") {
        $this->can_create_reminders();
        if (!$id) {
            show_404();
        }

        validate_numeric_value($id);

        if (!$status) {
            $this->validate_submitted_data(array(
                "value" => "required"
            ));
            $status = $this->request->getPost("value");
        }

        $reminder_info = $this->Events_model->get_one($id);
        $this->can_access_this_reminder($reminder_info);

        if ($reminder_info->share_with) {
            echo json_encode(array("success" => true, 'message' => app_lang('record_saved')));
        } else {
            if ($reminder_info->recurring && (!$reminder_info->no_of_cycles || $reminder_info->no_of_cycles_completed < $reminder_info->no_of_cycles) && ($status === "shown" || $status === "done")) {
                //calculate next recurring time on reminder action
                $next_recurring_time = add_period_to_date(is_null($reminder_info->next_recurring_time) ? ($reminder_info->start_date . " " . $reminder_info->start_time) : $reminder_info->next_recurring_time, $reminder_info->repeat_every, $reminder_info->repeat_type, "Y-m-d H:i:s");
                $data['next_recurring_time'] = $next_recurring_time;
                $data['no_of_cycles_completed'] = (int) $reminder_info->no_of_cycles_completed + 1;

                if ($next_recurring_time < get_my_local_time()) {
                    //if the next recurring time is a past date, mark it as done
                    $status = "done";
                } else {
                    //to remind again
                    $status = "new";
                }
            }

            $data["reminder_status"] = $status;

            $save_id = $this->Events_model->ci_save($data, $id);
            if ($save_id) {
                $reminder_info = $this->Events_model->get_one($id);
                echo json_encode(array("success" => true, "data" => $this->_make_reminder_row($reminder_info), 'id' => $save_id, 'message' => app_lang('record_saved')));
            } else {
                echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
            }
        }
    }


    public function mochila_modal_form() {
    return $this->template->view('events/mochila_modal_form');
}

public function save_mochila() {
$start_date_input = $this->request->getPost('start_date'); // Ej: 03-06-2025
$start_time_input = $this->request->getPost('start_time'); // Ej: 14:30

// Separar manualmente el día, mes y año
list($day, $month, $year) = explode('-', $start_date_input);

// Recombinar al formato YYYY-MM-DD
$start_date = $year . '-' . $month . '-' . $day;

// Si no viene tiempo, default a 00:00:00
$start_time = $start_time_input ? $start_time_input . ':00' : '00:00:00';

$data = array(
    "title" => $this->request->getPost('title'),
    "start_date" => $start_date,
    "start_time" => $start_time,
    "extra" => $this->request->getPost('extra'),
    "ig" => $this->request->getPost('ig'),
    "created_by" => $this->login_user->id,
    "labels" => "2",  // 👈 aquí agregás el label fijo
    "type" => "event"
);



    $save_id = $this->Events_model->insert($data);
    if ($save_id) {
        echo json_encode(array("success" => true, 'message' => "Mochila guardada."));
    } else {
        echo json_encode(array("success" => false, 'message' => "Error al guardar."));
    }
}



    function snooze_reminder() {
        $this->can_create_reminders();
        $this->validate_submitted_data(array(
            "id" => "required|numeric"
        ));

        $id = $this->request->getPost('id');

        $reminder_info = $this->Events_model->get_one($id);
        $this->can_access_this_reminder($reminder_info);
        if ($reminder_info->share_with) {
            app_redirect("forbidden");
        }

        $snooze_length = get_setting('user_' . $this->login_user->id . '_reminder_snooze_length');
        $snooze_length = $snooze_length ? $snooze_length : 5;

        $reminder_time = $reminder_info->start_date . " " . $reminder_info->start_time;
        if (!is_null($reminder_info->snoozing_time)) {
            $reminder_time = $reminder_info->snoozing_time;
        } else if (!is_null($reminder_info->next_recurring_time)) {
            $reminder_time = $reminder_info->next_recurring_time;
        }

        $data["snoozing_time"] = add_period_to_date($reminder_time, $snooze_length, "minutes", "Y-m-d H:i:s");

        $save_id = $this->Events_model->ci_save($data, $id);
        if ($save_id) {
            $reminder_info = $this->Events_model->get_one($id);
            echo json_encode(array("success" => true, "reminder_info" => $reminder_info, 'message' => app_lang('record_saved')));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
        }
    }

    function reminder_view() {
        $this->can_create_reminders();
        $this->validate_submitted_data(array(
            "id" => "required|numeric"
        ));

        $id = $this->request->getPost('id');

        $reminder_info = $this->Events_model->get_one($id);
        $this->can_access_this_reminder($reminder_info);

        $reminder_info->end_date = $reminder_info->start_date;
        $view_data["model_info"] = $reminder_info;
        return $this->template->view("reminders/view", $view_data);
    }

    function get_reminders_for_current_user() {
        $this->can_create_reminders();
        echo json_encode(array("success" => true, "reminders" => reminders_widget(true)));
    }

    function count_missed_reminders() {
        $this->can_create_reminders();
        $reminders = $this->Events_model->count_missed_reminders($this->login_user->id, $this->login_user->notification_checked_at);
        echo json_encode(array("success" => true, 'total_reminders' => $reminders));
    }
    
    public function save_event_color()
    {
        try {
            $this->validate_submitted_data([
                "encrypted_event_id" => "required",
                "color" => "required"
            ]);
    
            $encrypted_id = $this->request->getPost("encrypted_event_id");
            $color = $this->request->getPost("color");
    
            helper("general");
            $event_id = decode_id($encrypted_id, "event_id");
    
            if (!$event_id) {
                return $this->response->setStatusCode(400)->setJSON([
                    "success" => false,
                    "message" => "ID inválido"
                ]);
            }
    
            $this->Events_model = new \App\Models\Events_model();
    
if ($this->Events_model->update($event_id, ["color" => $color])) {
    require_once(APPPATH . "ThirdParty/Pusher/vendor/autoload.php");

    $pusher = new \Pusher\Pusher(
        get_setting("pusher_key"),
        get_setting("pusher_secret"),
        get_setting("pusher_app_id"),
        [
            'cluster' => get_setting("pusher_cluster"),
            'useTLS' => true
        ]
    );

    $pusher->trigger('calendar-channel', 'event-updated', [
        'event_id' => $event_id,
        'updated_fields' => [
            'color' => $color
        ]
    ]);

    return $this->response->setJSON([
        "success" => true,
        "message" => "Color actualizado correctamente",
        "encrypted_event_id" => $encrypted_id,
        "color" => $color
    ]);
}

             else {
                return $this->response->setStatusCode(500)->setJSON([
                    "success" => false,
                    "message" => "No se pudo actualizar el evento"
                ]);
            }
        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)->setJSON([
                "success" => false,
                "message" => $e->getMessage()
            ]);
        }
    }

public function import_csv()
{

    
    log_message('debug', 'Entrando al método import_csv()');

    $file = $this->request->getFile('csv_file');
    $base_date = $this->request->getPost('base_date'); // YYYY-MM-DD

    if (!($file && $file->isValid() && $file->getExtension() === 'csv' && $base_date)) {
        log_message('error', 'CSV inválido o falta base_date');
        $this->session->setFlashdata('error', 'CSV inválido o falta fecha base.');
        return redirect()->to('events');
    }

    log_message('debug', 'Archivo CSV válido y fecha base: ' . $base_date);

    $handle = fopen($file->getTempName(), "r");
    if ($handle === false) {
        $this->session->setFlashdata('error', 'No se pudo abrir el archivo.');
        return redirect()->to('events');
    }

    // Cabecera
    $header = fgetcsv($handle, 1000, ",");
    if (!$header) {
        $this->session->setFlashdata('error', 'CSV vacío o cabecera inválida.');
        return redirect()->to('events');
    }
    // Limpieza BOM y espacios
    $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
    $header = array_map('trim', $header);

    // === Marcas de importación ===
    $batchId = date('Ymd_His') . '_' . bin2hex(random_bytes(4));
    $now     = date('Y-m-d H:i:s');

    // === Control de fecha por filas ===
    $dayOffset    = 0;
    $current_date = $base_date; // arranca en base_date

    // Acumular para insertBatch
    $bulk = [];

    // Helpers
    $is_hhmm = function($val) {
        $val = trim((string)$val);
        return (bool)preg_match('/^\d{1,2}:\d{2}$/', $val);
    };

    while (($data = fgetcsv($handle, 1000, ",")) !== false) {
        $row = @array_combine($header, $data);
        if (!$row) {
            log_message('error', 'Fila inválida: ' . json_encode($data));
            continue;
        }
        // Limpia claves esperadas
        $titulo = isset($row["TITULO"]) ? trim($row["TITULO"]) : '';
        $extra  = isset($row["EXTRA"])  ? trim($row["EXTRA"])  : '';
        $hin    = isset($row["HORA INICIO"]) ? trim($row["HORA INICIO"]) : '';
        $hfin   = isset($row["HORA FINAL"])  ? trim($row["HORA FINAL"])  : '';

        log_message('debug', 'Fila procesada: ' . json_encode($row));

        // Marca de cambio de día:
        // Si "HORA INICIO" existe pero NO tiene formato hh:mm, interpretamos “corte de día”
        if ($hin !== '' && !$is_hhmm($hin)) {
            $dayOffset++;
            $current_date = date("Y-m-d", strtotime("$base_date +$dayOffset day"));
            log_message('debug', "Se detectó marca de cambio de día -> nueva fecha: $current_date (offset=$dayOffset)");
            continue; // no es evento, solo separador
        }

        // Evento válido requiere TÍTULO y hora inicio (aunque sea 00:00)
        if ($titulo === '' || $hin === '' || !$is_hhmm($hin)) {
            log_message('error', "Fila omitida (titulo u hora inválida). Título='$titulo' HORA INICIO='$hin'");
            continue;
        }

        // Calcular fin
        $end_date = $current_date;
        if ($hfin === '' || !$is_hhmm($hfin)) {
            // fallback: +5h
            $ts_inicio = strtotime("$current_date $hin");
            if ($ts_inicio === false) {
                $ts_inicio = strtotime("$current_date 00:00");
            }
            $ts_final  = $ts_inicio + 5 * 3600;
            $hfin      = date("H:i", $ts_final);
            $end_date  = date("Y-m-d", $ts_final);

            if ($end_date !== $current_date) {
                log_message('debug', "Evento '$titulo' cruza de día: end_date=$end_date (hora_final=$hfin)");
            } else {
                $end_date = $current_date;
            }
            log_message('debug', "Hora final calculada para '$titulo': $hfin");
} else {
    // HORA FINAL provista, mantenemos mismo día por defecto
    $end_date = $current_date;

    // 👇 ajuste: si la hora final es menor o igual que la de inicio, significa que cruza medianoche
    $ts_inicio = strtotime("$current_date $hin");
    $ts_final  = strtotime("$current_date $hfin");
    if ($ts_inicio !== false && $ts_final !== false && $ts_final <= $ts_inicio) {
        $end_date = date("Y-m-d", strtotime("$current_date +1 day"));
        log_message('debug', "Evento '$titulo' cruza medianoche: $hin → $hfin, end_date corregido a $end_date");
    }
}

        // Armar registro (usa current_date, no base_date)
        $bulk[] = [
            "title"          => $titulo,
            "description"    => null,
            "start_date"     => $current_date, // << clave: fecha correcta por fila
            "start_time"     => $hin,
            "end_date"       => $end_date,
            "end_time"       => $hfin,
            "extra"          => $extra,
            "created_by"     => $this->login_user->id,
            "color"          => "#c2c2c2ff",
            "share_with"     => "all",
            "deleted"        => 0,

            // marcas de importación
            "import_source"   => "csv",
            "import_batch_id" => $batchId,
            "imported_at"     => $now,
        ];
    }
    fclose($handle);

    if (empty($bulk)) {
        $this->session->setFlashdata('warning', 'No se encontraron filas válidas para importar.');
        return redirect()->to('events');
    }

    // Transacción + insertBatch
    $db = \Config\Database::connect();
    $db->transStart();
    try {
        // chunk en caso de CSVs enormes
        $chunkSize = 500;
        $total = count($bulk);
        for ($i = 0; $i < $total; $i += $chunkSize) {
            $slice = array_slice($bulk, $i, $chunkSize);
            $this->Events_model->insertBatch($slice);
        }
        $db->transComplete();
        if ($db->transStatus() === false) {
            throw new \RuntimeException('Fallo de transacción en import_csv');
        }
    } catch (\Throwable $e) {
        $db->transRollback();
        log_message('error', 'Error importando CSV: '.$e->getMessage());
        $this->session->setFlashdata('error', 'Error importando CSV.');
        return redirect()->to('events');
    }

    log_message('debug', 'Importación finalizada. Lote: ' . $batchId);
    $this->session->setFlashdata('success', 'Eventos importados correctamente. Lote: '.$batchId);
    return redirect()->to('events');

    
}



    

    
    
    public function import_csv_modal_form()
{
    return $this->template->view("events/import_csv_modal_form");
}

public function bulk_delete_day()
{
    return $this->bulkDeleteDay();
}

public function bulkDeleteDay()
{
    log_message('debug', 'bulkDeleteDay(): entrando');

    $rules = [
        'date'     => 'required|valid_date[Y-m-d]',
        'scope'    => 'required|in_list[csv,csv_batch,all]',
        'batch_id' => 'permit_empty|max_length[64]'
    ];

    if (!$this->validate($rules)) {
        log_message('error', 'bulkDeleteDay(): validación falló -> ' . json_encode($this->validator->getErrors()));
        return $this->response->setStatusCode(422)->setJSON([
            'ok' => false,
            'error' => $this->validator->getErrors()
        ]);
    }

    $date     = $this->request->getPost('date');
    $scope    = $this->request->getPost('scope');
    $batch_id = trim((string)$this->request->getPost('batch_id'));

    // ✅ conectar DB seguro en controlador
    $db = Database::connect();
    $b  = $db->table('rise_events');

    $b->set('deleted', 1)
      ->where('deleted', 0)
      ->where('start_date', $date);

    if ($scope === 'csv') {
        $b->where('import_source', 'csv');
    } elseif ($scope === 'csv_batch') {
        if ($batch_id === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'ok' => false,
                'error' => ['batch_id' => 'Falta el batch_id para scope=csv_batch']
            ]);
        }
        $b->where('import_source', 'csv')
          ->where('import_batch_id', $batch_id);
    }
    $b->update();

    $affected = $db->affectedRows();
    log_message('debug', "bulkDeleteDay(): afectados=$affected date=$date scope=$scope batch=$batch_id");

    return $this->response->setJSON([
        'ok'      => true,
        'deleted' => $affected,
        'message' => "Marcados como eliminados $affected eventos del $date (" .
                     ($scope==='all' ? 'todos' : ($scope==='csv' ? 'solo CSV' : "lote $batch_id")) . ")"
    ]);
}









// abre el modal
public function resource_usage_modal()
{
    return $this->template->view("events/resource_usage_modal");
}

public function get_resource_usage_data()
{
    $filters = [
        "mode"      => trim($this->request->getPost("mode") ?? ""),
        "datetime"  => trim($this->request->getPost("datetime") ?? ""),
        "date_from" => trim($this->request->getPost("date_from") ?? ""),
        "date_to"   => trim($this->request->getPost("date_to") ?? ""),
        "resource"  => trim($this->request->getPost("resource") ?? ""),
        "client_id" => trim($this->request->getPost("client_id") ?? ""),
        "created_by"=> trim($this->request->getPost("created_by") ?? ""),
    ];

    $data["usage"] = $this->Events_model->get_resource_usage($filters);

    return $this->response->setJSON([
        "success" => true,
        "data"    => $data["usage"]
    ]);
}








public function get_timeline_events()
{
    $events = $this->Events_model->get_all()->getResult();

    $items = [];
    $groups = [];

    $grupos_ids = [];

    foreach ($events as $event) {
        // usamos 'conv' como grupo de ejemplo
        $group_id = "g_" . $event->id; // cada grupo único por evento

        // agregamos el grupo si aún no existe
        if (!in_array($group_id, $grupos_ids)) {
            $groups[] = [
                'id' => $group_id,
                'content' => $event->title
            ];
            $grupos_ids[] = $group_id;
        }

        $items[] = [
            'id' => $event->id,
            'content' => $event->title,
            'start' => "{$event->start_date} {$event->start_time}",
            'end' => "{$event->end_date} {$event->end_time}",
            'group' => $group_id
        ];
    }

    return $this->response->setJSON([
        'items' => $items,
        'groups' => $groups
    ]);
}




    
}

/* End of file events.php */
    /* Location: ./app/controllers/events.php */