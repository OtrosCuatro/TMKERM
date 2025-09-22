
<?php
load_css(array(
    "assets/js/fullcalendar/fullcalendar.min.css"
));

load_js(array(
    "assets/js/fullcalendar/fullcalendar.min.js",
    "assets/js/fullcalendar/locales-all.min.js"
));



$client = "";
if (isset($client_id)) {
    $client = $client_id;
}

// NUEVO: Configuración de columnas por role
$role_id = $login_user->role_id;

$columns_all = [
    ["key" => "conv", "title" => "CONV", "color" => "#616161"],
    ["key" => "asi_ip", "title" => "ASI IP", "color" => "#616161"],
    ["key" => "ig", "title" => "IG", "color" => "#616161"],
    ["key" => "smd", "title" => "SMD", "color" => "#616161"],
    ["key" => "extra", "title" => "extra", "color" => "#616161"]

];

$visible_columns_by_role = [
    1 => ["conv", "asi_ip", "ig", "extra", "smd"],  // Admin
    2 => ["conv", "ingesta", "smd"],                 // Operador
    3 => ["asi_ip"],                                 // Técnico
];

$visible_keys = $visible_columns_by_role[$role_id] ?? [];
$visible_columns = array_filter($columns_all, function ($col) use ($visible_keys) {
    return in_array($col["key"], $visible_keys);
});
?>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success">
        <?= session()->getFlashdata('success') ?>
    </div>
<?php endif; ?>

<link href="https://unpkg.com/vis-timeline/styles/vis-timeline-graph2d.min.css" rel="stylesheet" />
<script src="https://unpkg.com/vis-timeline/standalone/umd/vis-timeline-graph2d.min.js"></script>

<div id="mytimeline"></div>


<div id="page-content<?php echo $client; ?>" class="page-wrapper<?php echo $client; ?> clearfix">
    <div class="card full-width-button">
        <div class="page-title clearfix">
            <?php if ($client) { ?>
                <h4><?php echo app_lang('events'); ?></h4>
            <?php } else { ?>
                <h1><?php echo app_lang('event_calendar'); ?></h1>
            <?php } ?>
            <div class="title-button-group custom-toolbar events-title-button">

            

                <?php
                echo form_input(array(
                    "id" => "event-labels-dropdown",
                    "name" => "event-labels-dropdown",
                    "class" => "select2 w200 mr10 float-start mt15"
                ));
                ?>

                <?php if ($calendar_filter_dropdown) { ?>
                    <div id="calendar-filter-dropdown"
                        class="float-start <?php echo (count($calendar_filter_dropdown) == 1) ? "hide" : ""; ?>"></div>
                <?php } ?>

                <?php echo modal_anchor(get_uri("labels/modal_form"), "<i data-feather='tag' class='icon-16'></i> " . app_lang('manage_labels'), array("class" => "btn btn-default", "title" => app_lang('manage_labels'), "data-post-type" => "event")); ?>

                <?php echo modal_anchor(get_uri("events/import_csv_modal_form"), "<i data-feather='upload' class='icon-16'></i> Añad. / supr. lotes", array("class" => "btn btn-default", "title" => "Añad. / supr. lotes")); ?>

                <?php
                if (get_setting("enable_google_calendar_api") && (get_setting("google_calendar_authorized") || get_setting('user_' . $login_user->id . '_google_calendar_authorized'))) {
                    echo modal_anchor(get_uri("events/google_calendar_settings_modal_form"), "<i data-feather='settings' class='icon-16'></i> " . app_lang('google_calendar_settings'), array("class" => "btn btn-default", "title" => app_lang('google_calendar_settings')));
                }
                ?>

<?php echo modal_anchor(get_uri("events/mochila_modal_form"), "<i data-feather='video' class='icon-16'></i> Agregar Mochila", array("class" => "btn btn-default", "title" => "Agregar Mochila")); ?>

<button id="btnTablero" class="btn btn-default">
    <i data-feather="grid" class="icon-16"></i> Tablero de Ingestas
</button>

<?php echo modal_anchor(get_uri("events/resource_usage_modal"), "<i data-feather='bar-chart-2'></i> " . "Uso de Recursos", array("class" => "btn btn-default", "title" => "Uso de Recursos")); ?>







                <?php echo modal_anchor(get_uri("events/modal_form"), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('add_event'), array("class" => "btn btn-default add-btn", "title" => app_lang(lang: 'add_event'), "data-post-client_id" => $client)); ?>

                <?php echo modal_anchor(get_uri("events/modal_form"), "", array("class" => "hide", "id" => "add_event_hidden", "title" => app_lang('add_event'), "data-post-client_id" => $client)); ?>
                <?php echo modal_anchor(get_uri("events/view"), "", array("class" => "hide", "id" => "show_event_hidden", "data-post-client_id" => $client, "data-post-cycle" => "0", "data-post-editable" => "1", "title" => app_lang('event_details'))); ?>
                <?php echo modal_anchor(get_uri("leaves/application_details"), "", array("class" => "hide", "data-post-id" => "", "id" => "show_leave_hidden")); ?>
                <?php echo modal_anchor(get_uri("tasks/view"), "", array("class" => "hide", "data-post-id" => "", "id" => "show_task_hidden", "data-modal-lg" => "1")); ?>
            </div>
        </div>
        <div class="card-body">

            <div id="event-calendar"></div>

            <!-- Menú contextual -->
<div id="customContextMenu">
<?php if ($login_user->is_admin || get_array_value($login_user->permissions, "asignar_smd")): ?>
    <div class="menu-item">Asignar SMD</div>
<?php endif; ?>

<?php if ($login_user->is_admin || get_array_value($login_user->permissions, "asignar_ird")): ?>
    <div class="menu-item">Asignar IRD</div>
<?php endif; ?>

<?php if ($login_user->is_admin || get_array_value($login_user->permissions, "asignar_cv")): ?>
    <div class="menu-item">Asignar CV</div>
<?php endif; ?>

<?php if ($login_user->is_admin || get_array_value($login_user->permissions, "asignar_ig")): ?>
    <div class="menu-item">Asignar IG</div>
<?php endif; ?>


<?php if ($login_user->is_admin || get_array_value($login_user->permissions, "estado_cargado")): ?>
    <div class="menu-item">Cargado</div>
<?php endif; ?>

<?php if ($login_user->is_admin || get_array_value($login_user->permissions, "estado_chequeado")): ?>
    <div class="menu-item group1">Chequeado</div>
<?php endif; ?>

<?php if ($login_user->is_admin || get_array_value($login_user->permissions, "estado_faltan_datos")): ?>
    <div class="menu-item group4">Faltan datos</div>
<?php endif; ?>

<?php if ($login_user->is_admin || get_array_value($login_user->permissions, "estado_grabando")): ?>
    <div class="menu-item group2">Grabando</div>
<?php endif; ?>

<?php if ($login_user->is_admin || get_array_value($login_user->permissions, "estado_backup")): ?>
    <div class="menu-item group5">Backup</div>
<?php endif; ?>

<?php if ($login_user->is_admin || get_array_value($login_user->permissions, "estado_finalizado")): ?>
    <div class="menu-item group3">Finalizado</div>
<?php endif; ?>





</div>


            


        </div>
    </div>
</div>

  <script>
  $(document).on("show.bs.modal", "#ajaxModal", function (e) {
      var $modal = $(this);
      var trigger = $(e.relatedTarget); // el botón que abrió el modal
      var href = trigger.attr("data-action-url") || trigger.attr("href");

      if (href && href.indexOf("ingestas/board_modal") !== -1) {
          $modal.addClass("tablero-full");
      } else {
          $modal.removeClass("tablero-full");
      }
  });
  </script>




  <script type="text/javascript">
let isHeaderRendered = false;
let __provisionalAnchor = null;   // 👈 declarar aquí

let __navBusy = false; // ← flag anti doble click (global)
      
      var filterValues = "",
          eventLabel = "";


          var loadCalendar = function () {
      var filter_values = filterValues || "events",
          $eventCalendar = document.getElementById('event-calendar'),
          event_label = eventLabel || "0";

      appLoader.show();

      let editingQuickEventId = null;

      


window.fullCalendar = new FullCalendar.Calendar($eventCalendar, {
  locale: 'es',
  timeZone: 'local',
  initialView: 'listDay',
  height: isMobile() ? "auto" : $(window).height() - 210,
  nowIndicator: true,

  // === BOTONES CUSTOM: mover ±1 día ===
  customButtons: {
    prevOne: {
      text: '⟵ 1d',
      click: function () {
        if (__navBusy) return;
        __navBusy = true;
        window.fullCalendar.incrementDate({ days: -1 });
      }
    },
    nextOne: {
      text: '1d ⟶',
      click: function () {
        if (__navBusy) return;
        __navBusy = true;
        window.fullCalendar.incrementDate({ days: 1 });
      }
    }
  },

headerToolbar: {
  left: 'prev,next today',
  center: 'title',
    right: 'listDay,listDayPlus6h,timeGridDay,listMonth'
},

views: {
  listDay: { buttonText: 'Día' },
  listMonth: { buttonText: 'Mes' },
  timeGridWeek: { buttonText: 'Semana' },
  timeGridDay: { buttonText: 'Horas' },

listDayPlus6h: {
  type: 'list',
  buttonText: '+ Dia siguiente',
  dateIncrement: { days: 1 },
  visibleRange: function (currentDate) {
    const start = new Date(currentDate);
    start.setHours(0, 0, 0, 0);

    const end = new Date(start);
    end.setDate(end.getDate() + 1); // al día siguiente
    end.setHours(6, 0, 0, 0);

    return { start, end };
  },
  // 👇 esto obliga a mostrar headings de día aunque no haya eventos
  dayHeaders: true,
  dayHeaderFormat: { weekday: 'long', month: 'short', day: 'numeric' }
}

},

  // ⛔ NO definimos otro dateClick acá
  // (dejamos tu dateClick de más abajo que abre el modal de "agregar evento")

  // ✅ Permitir navegar a la vista extendida clickeando enlaces de día (en títulos/mes)
  navLinkDayClick: function (date) {
    const iso = date.toISOString().slice(0, 10);
    window.__selectedDateISO = iso;
    window.fullCalendar.changeView('listDayPlus6h', iso);
  },

  // === SOLO pintar título y liberar flag ===
  datesSet: function (arg) {
    __navBusy = false; // libera los botones custom
    updateToolbarDateText(arg.view.currentStart, arg.view.type);
    // console.log('anchor =', arg.view.currentStart.toISOString().slice(0,10), 'view=', arg.view.type);
  },

  viewDidMount: function (arg) {
    updateToolbarDateText(arg.view.currentStart, arg.view.type);
  },

  // === TU LÓGICA SIGUE IGUAL DESDE ACÁ ===
  events: "<?php echo_uri("events/calendar_events/"); ?>" + filter_values + "/" + event_label + "/" + "<?php echo "/$client"; ?>",
  dayMaxEvents: false,

  // ⚠️ Mantenemos tu dateClick que abre el modal de crear evento
  dateClick: function (date, jsEvent, view) {
    $("#add_event_hidden").attr("data-post-start_date", moment(date.date).format("YYYY-MM-DD"));
    var startTime = moment(date.date).format("HH:mm:ss");
    if (startTime === "00:00:00") { startTime = ""; }
    $("#add_event_hidden").attr("data-post-start_time", startTime);
    var endDate = moment(date.date).add(1, 'hours');
    $("#add_event_hidden").attr("data-post-end_date", endDate.format("YYYY-MM-DD"));
    var endTime = "";
    if (startTime != "") { endTime = endDate.format("HH:mm:ss"); }
    $("#add_event_hidden").attr("data-post-end_time", endTime);
    $("#add_event_hidden").trigger("click");
  },

  eventDidMount: function (info) {
    $(info.el).tooltip({ title: info.event.title });
    info.el.setAttribute("data-event-id", info.event.id);

    const viewType = info.view.type;
    if ((viewType === "timeGridDay" || viewType === "timeGridWeek" || viewType === "dayGridMonth") && info.el && info.event.backgroundColor) {
      info.el.style.background = `
        linear-gradient(to right, rgba(0, 0, 0, 0.4), rgba(0,0,0,0)),
        ${info.event.backgroundColor}
      `;
    }

  // Right-click custom context menu hook (inlined for reliability)
  if (info && info.el) {
    info.el.addEventListener("contextmenu", function(e){
      e.preventDefault();
      try {
        var menuEl = document.getElementById("customContextMenu");
        if (!menuEl) { console.warn("No #customContextMenu in DOM"); return; }

        // Guardar el ID del evento
        menuEl.dataset.eventId = info.event && (info.event.id || (info.event.extendedProps && info.event.extendedProps.id)) || "";

        // Mostrar primero para medir tamaño real
        menuEl.style.display = "block";
        var rect = menuEl.getBoundingClientRect();

        // Usar coordenadas de viewport (position: fixed)
        var OFFSET_X = 12;
        var OFFSET_Y = 8;

        var x = e.clientX + OFFSET_X;
        var y = e.clientY + OFFSET_Y;

        // Clampear a los bordes del viewport
        var maxX = window.innerWidth  - rect.width  - 8;
        var maxY = window.innerHeight - rect.height - 8;

        if (x > maxX) x = e.clientX - rect.width  - OFFSET_X;
        if (y > maxY) y = e.clientY - rect.height - OFFSET_Y;

        x = Math.max(4, Math.min(x, maxX));
        y = Math.max(4, Math.min(y, maxY));

        menuEl.style.left = x + "px";
        menuEl.style.top  = y + "px";
      } catch(err){
        console.error("contextmenu handler error:", err);
      }
    }, { passive:false });
  }

      
      },







  eventClick: function (calEvent) {
      const eventId = calEvent.event.id;

      // 👉 Bloquea apertura si estamos editando este evento
      if (editingQuickEventId === eventId) {
          return false;
      }

      const props = calEvent.event.extendedProps;

      if (props.event_type === "event") {
          $("#show_event_hidden").attr("data-post-id", props.encrypted_event_id);
          $("#show_event_hidden").trigger("click");

      } else if (props.event_type === "leave") {
          $("#show_leave_hidden").attr("data-post-id", props.leave_id);
          $("#show_leave_hidden").trigger("click");

      } else if (props.event_type === "project_deadline" || props.event_type === "project_start_date") {
          window.location = "<?php echo site_url('projects/view'); ?>/" + props.project_id;

      } else if (props.event_type === "task_deadline" || props.event_type === "task_start_date") {
          $("#show_task_hidden").attr("data-post-id", props.task_id);
          $("#show_task_hidden").trigger("click");
      }
  },







eventContent: function (arg) {
    const props = arg.event.extendedProps;
    const title = arg.event.title;
    const conv = props.conv || '';
    const asiIp = props.asi_ip || '';
    const ig = props.ig || '';
    const smd = props.smd || '';
    const extra = props.extra || '';

    let cells = {};
    const viewType = arg.view.type;

    // ===============================
    // VISTA TIMEGRID (día/semana)
    // ===============================
    if (viewType === "timeGridDay" || viewType === "timeGridWeek") {
        const eventRow = document.createElement("div");
        eventRow.style.display = "flex";
        eventRow.style.flexDirection = "column";
        eventRow.style.background = `
            linear-gradient(to right, rgba(0, 0, 0, 0.4), rgba(0,0,0,0)),
            ${arg.event.backgroundColor}
        `;
        eventRow.style.color = "#fff";
        eventRow.style.padding = "6px";
        eventRow.style.borderRadius = "4px";
        eventRow.style.fontSize = "0.9em";

        const titleWrapper = document.createElement("div");
        titleWrapper.textContent = title;
        titleWrapper.style.textAlign = "left";
        titleWrapper.style.fontWeight = "bold";
        titleWrapper.style.marginBottom = "5px";

        const badgesWrapper = document.createElement("div");
        badgesWrapper.style.display = "flex";
        badgesWrapper.style.gap = "4px";
        badgesWrapper.style.flexWrap = "wrap";

        function createBadge(text, bgColor) {
            const badge = document.createElement("div");
            badge.textContent = text;
            badge.style.backgroundColor = bgColor || "#616161";
            badge.style.padding = "3px 6px";
            badge.style.borderRadius = "4px";
            badge.style.fontSize = "0.85em";
            return badge;
        }

        badgesWrapper.appendChild(createBadge(smd, "#616161"));
        badgesWrapper.appendChild(createBadge(asiIp, "#616161"));
        badgesWrapper.appendChild(createBadge(conv, "#616161"));
        badgesWrapper.appendChild(createBadge(ig, "#616161"));
        badgesWrapper.appendChild(createBadge(extra, "#616161"));

        const actionCell = document.createElement("div");
        actionCell.style.textAlign = "center";
        const editBtn = document.createElement("button");
        editBtn.textContent = "✏️";
        editBtn.style.cursor = "pointer";
        editBtn.style.background = "transparent";
        editBtn.style.border = "none";
        editBtn.style.color = "#fff";
        actionCell.appendChild(editBtn);

        eventRow.appendChild(titleWrapper);
        eventRow.appendChild(badgesWrapper);
        eventRow.appendChild(actionCell);

        return { domNodes: [eventRow] };
    }

    // ===============================
    // VISTA LISTA
    // ===============================
    const eventRow = document.createElement("div");
    eventRow.style.display = "grid";
    eventRow.style.gridTemplateColumns = "1fr 100px 100px 100px 100px 347px 40px";
    eventRow.style.gap = "4px";
    eventRow.style.background = `
        linear-gradient(to right, rgba(255, 255, 255, 0), rgba(0, 0, 0, 0.28), rgba(0, 0, 0, 0)),
        ${arg.event.backgroundColor}
    `;
    eventRow.style.color = "#000";
    eventRow.style.padding = "5px";
    eventRow.style.borderRadius = "4px";
    eventRow.style.fontSize = "1.2em";
    eventRow.style.fontWeight = "bold";
    eventRow.style.alignItems = "center";

    function createCell(text, bgColor, align = "center", transparent = false) {
        const cell = document.createElement("div");
        cell.textContent = text;
        cell.style.backgroundColor = transparent ? "transparent" : (bgColor || "#616161");
        cell.style.padding = "3px 6px";
        cell.style.borderRadius = "4px";
        cell.style.textAlign = align;
        return cell;
    }

    const titleCell = createCell(title, "", "left", true);
    eventRow.appendChild(titleCell);

    const fields = [
        { key: 'smd', defaultColor: '#616161', colorKey: 'smd_color' },
        { key: 'asi_ip', defaultColor: '#616161', colorKey: 'asi_ip_color' },
        { key: 'conv', defaultColor: '#616161', colorKey: 'conv_color' },
        { key: 'ig', defaultColor: '#616161', colorKey: 'ig_color' },
        { key: 'extra', defaultColor: '#616161', bgColorKey: 'extra_bg_color', textColorKey: 'extra_text_color' }
    ];

    fields.forEach(field => {
        if (field.key === 'extra') {
            const bgColor = props.extra_bg_color || field.defaultColor;
            const textColor = props.extra_text_color || "#000";
            const cell = createCell(props[field.key], bgColor);
            cell.style.color = textColor;
            cells[field.key] = cell;
            eventRow.appendChild(cell);
        } else {
            const finalColor = props[field.colorKey] || field.defaultColor;
            const cell = createCell(props[field.key], finalColor);
            cells[field.key] = cell;
            eventRow.appendChild(cell);
        }
    });

    const actionCell = document.createElement("div");
    const editBtn = document.createElement("button");
    editBtn.textContent = "✏️";
    editBtn.style.cursor = "pointer";
    editBtn.style.background = "transparent";
    editBtn.style.border = "none";
    editBtn.style.color = "#fff";
    actionCell.appendChild(editBtn);
    eventRow.appendChild(actionCell);

    // ===============================
    // EDIT MODE
    // ===============================
    editBtn.onclick = function () {
        editingQuickEventId = arg.event.id;

        fetch("<?= site_url('picker_options/list_all') ?>")
            .then(r => r.json())
            .then(options => {
                ["conv", "asi_ip", "ig", "smd"].forEach(key => {
                    const select = document.createElement("select");
                    select.classList.add("form-control", "select2");
                    select.style.width = "100%";

                    const placeholder = document.createElement("option");
                    placeholder.value = "";
                    placeholder.textContent = "-";
                    select.appendChild(placeholder);

                    (options[key] || []).forEach(opt => {
                        const o = document.createElement("option");
                        o.value = opt;
                        o.textContent = opt;
                        if (opt === props[key]) o.selected = true;
                        select.appendChild(o);
                    });

                    const wrapper = document.createElement("div");
                    wrapper.style.backgroundColor = "#fff";
                    wrapper.appendChild(select);

                    cells[key].replaceWith(wrapper);
                    cells[key] = wrapper;

                    $(select).select2({ dropdownParent: $(wrapper), width: "resolve" });
                });

                // Extra → input editable
                const input = document.createElement("input");
                input.type = "text";
                input.value = props.extra || "";
                input.style.width = "100%";
                input.style.padding = "3px 6px";
                input.style.border = "1px solid #444";
                input.style.borderRadius = "4px";
                input.style.background = "#fff";
                input.style.color = "#000";
                input.style.pointerEvents = "auto";

                input.addEventListener("mousedown", e => e.stopPropagation());

                const inputWrapper = document.createElement("div");
                inputWrapper.appendChild(input);
                cells.extra.replaceWith(inputWrapper);
                cells.extra = inputWrapper;
            });

        // Colores
// Colores
// Colores iniciales desde el evento
const badgeColors = {
    smd: props.smd_color || "#757575",
    asi_ip: props.asi_ip_color || "#757575",
    conv: props.conv_color || "#757575",
    ig: props.ig_color || "#757575"
};

// Paleta de colores
const strongColors = ["#4CAF50", "#FFC107", "#FB8C00", "#757575"];

// Definimos las secciones de color
const colorSections = [
    { keys: ["smd", "asi_ip", "conv"], gridCol: 3, label: "Técnicos" },
    { keys: ["ig"], gridCol: 5, label: "IG" }
];

// Fila de botones de color
const colorButtonsRow = document.createElement("div");
colorButtonsRow.style.display = "grid";
colorButtonsRow.style.gridTemplateColumns = "1fr 100px 100px 100px 100px 347px 40px";
colorButtonsRow.style.gap = "4px";
colorButtonsRow.style.gridColumn = "1 / -1";
colorButtonsRow.style.marginTop = "4px";

// Crear un grupo de botones por sección
colorSections.forEach(section => {
    const sectionWrapper = document.createElement("div");
    sectionWrapper.style.display = "flex";
    sectionWrapper.style.gap = "4px";
    sectionWrapper.style.justifyContent = "center";
    sectionWrapper.style.gridColumn = `${section.gridCol} / ${section.gridCol + 1}`;

    strongColors.forEach(color => {
        const btn = document.createElement("button");
        btn.style.backgroundColor = color;
        btn.style.width = "20px";
        btn.style.height = "20px";
        btn.style.borderRadius = "50%";
        btn.style.cursor = "pointer";
        btn.style.border = "2px solid #1c1c1c";

        // Marcar el botón si algún campo de la sección ya tiene ese color
        const currentColors = section.keys.map(k => badgeColors[k]);
        if (currentColors.includes(color)) {
            btn.style.outline = "2px solid black";
        }

        btn.addEventListener("click", e => {
            e.stopPropagation();
            section.keys.forEach(k => badgeColors[k] = color);
            sectionWrapper.querySelectorAll("button").forEach(b => b.style.outline = "none");
            btn.style.outline = "2px solid black";
        });

        sectionWrapper.appendChild(btn);
    });

    colorButtonsRow.appendChild(sectionWrapper);
});

eventRow.appendChild(colorButtonsRow);


        // Guardar
        const confirmBtn = document.createElement("button");
        confirmBtn.textContent = "✅";
        confirmBtn.style.cursor = "pointer";
        confirmBtn.style.background = "transparent";
        confirmBtn.style.border = "none";
        confirmBtn.style.color = "#fff";
        actionCell.innerHTML = "";
        actionCell.appendChild(confirmBtn);

        confirmBtn.onclick = function (e) {
            e.stopPropagation();
            const data = { id: arg.event.id };

            if (badgeColors.smd) data.smd_color = badgeColors.smd;
            if (badgeColors.asi_ip) data.asi_ip_color = badgeColors.asi_ip;
            if (badgeColors.conv) data.conv_color = badgeColors.conv;
            if (badgeColors.ig) data.ig_color = badgeColors.ig;

            fields.forEach(field => {
                let val = "";
                if (field.key === "extra") {
                    const el = cells.extra.querySelector("input");
                    if (el) val = el.value;
                } else {
                    const el = cells[field.key].querySelector("select");
                    if (el) val = el.value;
                }
                data[field.key] = val;
            });

            fetch("<?= site_url('events/update_event_fields') ?>", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: new URLSearchParams(data).toString()
            }).then(r => r.json()).then(result => {
                if (result.success) {
                    fields.forEach(field => {
                        const badge = createCell(data[field.key] || "", "#616161");
                        cells[field.key].replaceWith(badge);
                        cells[field.key] = badge;
                        arg.event.setExtendedProp(field.key, data[field.key]);
                    });
                    actionCell.innerHTML = "";
                    actionCell.appendChild(editBtn);
                    editingQuickEventId = null;
                } else {
                    alert("Error al actualizar");
                }
            });
        };
    };

    return { domNodes: [eventRow] };
},




viewDidMount: function (arg) {
  // Header pegajoso solo en vistas de lista
  if (arg.view.type.startsWith("list")) {
    insertCustomHeader();
  }
  updateToolbarDateText(arg.view.currentStart, arg.view.type);
},

datesSet: function (arg) {
  // 1) liberar flag de los botones custom
  __navBusy = false;

    // 2) actualizar título
  updateToolbarDateText(arg.view.currentStart, arg.view.type);

  // 3) insertar header si estamos en lista
  if (arg.view.type.startsWith("list")) {
    insertCustomHeader();
  }


  // 4) lógica del ancla para listDayPlus6h
  if (arg.view.type === "listDayPlus6h") {
    if (!__provisionalAnchor) {
      __provisionalAnchor = arg.view.currentStart.toISOString().slice(0,10);
    } else {
      const current = arg.view.currentStart.toISOString().slice(0,10);
      if (current !== __provisionalAnchor) {
        __provisionalAnchor = null;
        window.fullCalendar.changeView("listDay", arg.view.currentStart);
      }
    }
  } else {
    __provisionalAnchor = null;
  }
},

          



loading: function (state) {
  if (state === false) {
    appLoader.hide();
    $(".fc-prev-button").html("<i data-feather='chevron-left' class='icon-16'></i>");
    $(".fc-next-button").html("<i data-feather='chevron-right' class='icon-16'></i>");
    feather.replace();
    setTimeout(function () {
      feather.replace();
    }, 100);
  }
},


  firstDay: AppHelper.settings.firstDayOfWeek
});

      window.fullCalendar.render(); // Asegúrate de que se ejecute aquí


      

      let selectedEventId = null;


      // Forzar ejecución del header si la vista activa es tipo list



  };

  // Llamar a la función loadCalendar cuando el DOM esté listo
  $(document).ready(function () {
      loadCalendar();
  });











          var client = "<?php echo $client; ?>";
          if (client) {
              setTimeout(function () {
                  window.fullCalendar.today();
              });
          }

          //autoload the event popover
          var encrypted_event_id = "<?php echo isset($encrypted_event_id) ? $encrypted_event_id : ''; ?>";
          if (encrypted_event_id) {
              $("#show_event_hidden").attr("data-post-id", encrypted_event_id);
              $("#show_event_hidden").trigger("click");
          }

          $("#event-labels-dropdown").select2({
              data: <?php echo $event_labels_dropdown; ?>
          }).on("change", function () {
              eventLabel = $(this).val();
              loadCalendar();
          });

          $("#event-calendar .fc-header-toolbar .fc-button").click(function () {
              feather.replace();
          });

      // Usando jQuery para una solicitud AJAX (si se carga de esta manera)
 





// Función para mostrar los errores en la pantalla
function showError(message) {
    var errorDiv = document.createElement("div");
    errorDiv.style.position = "fixed";
    errorDiv.style.top = "0";
    errorDiv.style.left = "0";
    errorDiv.style.backgroundColor = "rgba(255, 0, 0, 0.8)";
    errorDiv.style.color = "white";
    errorDiv.style.padding = "10px";
    errorDiv.style.zIndex = "9999";
    errorDiv.style.fontSize = "14px";
    errorDiv.style.width = "100%";
    errorDiv.innerHTML = `<strong>Error:</strong> ${message}`;
    document.body.appendChild(errorDiv);
}

function insertCustomHeader() {
    if (document.querySelector(".custom-event-header")) return;

    const header = document.createElement("div");
    header.className = "custom-event-header";
    header.style.display = "grid";
    header.style.gridTemplateColumns = "70px 40px 1fr 100px 100px 100px 100px 347px 40px";
    header.style.gap = "4px";
    header.style.padding = "6px 10px";
    header.style.backgroundColor = "#222";
    header.style.color = "#fff";
    header.style.fontWeight = "bold";
    header.style.fontSize = "1em";
    header.style.position = "sticky";
    header.style.top = "0";
    header.style.zIndex = "1";

    const columnas = ["Hora", "", "Título", "SMD", "ASI / Fuente", "CV", "IG", "Extra", ""];
    columnas.forEach(text => {
        const cell = document.createElement("div");
        cell.textContent = text;
        cell.style.textAlign = text === "Título" ? "left" : "center";
        header.appendChild(cell);
    });

    // 👇 apuntamos al scroller, no a la tabla
    const scroller = document.querySelector(".fc-list .fc-scroller");
    if (scroller) {
        scroller.insertBefore(header, scroller.firstChild);
    }
}





<?php if (isset($login_user) && in_array($login_user->role_id, array_keys($visible_columns_by_role))) : ?>
    const visibleColumns = <?php echo json_encode(array_values($visible_columns)); ?>;
<?php else: ?>
    const visibleColumns = [];
<?php endif; ?>




</script>
<script>
// Inserta extras en el header solo una vez, sin romper prev/next
function injectCustomHeaderOnce() {
  const toolbar = document.querySelector('.fc-header-toolbar, .fc-toolbar');
  if (!toolbar) return;
  if (toolbar.dataset.customHeaderInjected === '1') return;

  // Si querés agregar un bloque extra bajo el header:
  // const extra = document.createElement('div');
  // extra.className = 'my-list-extra';
  // extra.innerHTML = ''; // tu contenido opcional
  // toolbar.insertAdjacentElement('afterend', extra);

  toolbar.dataset.customHeaderInjected = '1';
}
</script>




<script>
  document.addEventListener("DOMContentLoaded", function(){
    // Inicializar todos los popovers
    const popovers = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
    popovers.map(function (el) {
        return new bootstrap.Popover(el, {
            html: false,
            container: 'body'
        });
    });
});

</script>





<script>
    const BASE_URL = "<?= site_url(); ?>"; 
</script>


<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/locale/es.min.js"></script>
<script>
    moment.locale("es");

function updateToolbarDateText(date, viewType) {
    const toolbarTitle = document.querySelector('.fc-toolbar-title');
    if (!toolbarTitle) return;

    let formatted;

    if (viewType === "listMonth") {
        // 👉 Solo mes y año
        formatted = moment(date).format('MMMM [de] YYYY');
    } else {
        // 👉 Cualquier otra vista, dejamos como estaba
        formatted = moment(date).format('dddd, D [de] MMMM [de] YYYY');
    }

    // Primera letra mayúscula
    toolbarTitle.textContent = formatted.charAt(0).toUpperCase() + formatted.slice(1);
}


</script>

<script>
function setCurrentDateTimeForMochila() {
    const now = moment();
    $('#mochila_date').val(now.format('DD-MM-YYYY'));
    $('#mochila_time').val(now.format('HH:mm'));
}



$(document).ready(function() {
    $('#ajaxModal').on('shown.bs.modal', function() {
        if ($(this).find('#mochila_extra').length) {
            $(this).find('#mochila_extra').focus();
        }
    });
});

$(document).ready(function() {
    $("#mochila-now-button").click(function() {
        var now = moment().format('DD-MM-YYYY HH:mm');
        $("#mochila_date").val(now);
    });
});

$(document).ready(function() {
    $('#ajaxModal').on('shown.bs.modal', function() {
        if ($(this).find('#mochila_ig').length) {
            $(this).find('#mochila_ig').select2({
                width: '100%'
            });
        }
    });
});










</script>

<script>
// ==== Opciones para los pickers (global) ====
// Generador CV (reduce 1000 líneas de HTML 😅)
function genCV(){
  const out=[];
  for(let i=1;i<=161;i++) out.push(`CV ${String(i).padStart(3,'0')}`);
  for(let i=950;i<=978;i++) out.push(`CV ${i}`);
  for(let i=9101;i<=9103;i++) out.push(`CV ${i}`);
  return out;
}

// Pegamos lo que pasaste (puede estar largo; va bien así)
window.PICKER_OPTIONS = {
  // Conversores
  cv: [""].concat(genCV()),

  // IRD / ASI-IP
  asi_ip: [""].concat([
    "IRD 1-1","IRD 1-2","IRD 1-3","IRD 1-4","IRD 2-1","IRD 2-2","IRD 2-3","IRD 2-4",
    "IRD 3-1","IRD 3-2","IRD 3-3","IRD 3-4","IRD 4-1","IRD 4-2","IRD 4-3","IRD 4-4",
    "IRD 5-1","IRD 5-2","IRD 5-3","IRD 5-4","IRD 6-1","IRD 6-2","IRD 6-3","IRD 6-4",
    "IRD 7-1","IRD 7-2","IRD 7-3","IRD 7-4","IRD 8-1","IRD 8-2","IRD 8-3","IRD 8-4",
    "IRD 9","IRD 10","IRD 11","IRD 12","IRD 13","IRD 14","IRD 15-1","IRD 15-2","IRD 15-3","IRD 15-4",
    "IRD 16-1","IRD 16-2","IRD 16-3","IRD 16-4",
    "RX 01","RX 02","RX 03","RX 04","RX 05","RX 06","RX 07","RX 08","RX 09","RX 10","RX 11","RX 12","RX 13","RX 14","RX 15","RX 16","RX 17","RX 18","RX 80",
    "RX 301","RX 302","RX 303","RX 304","RX 305","RX 306","RX 307","RX 308","RX 309","RX 310","RX 311","RX 312","RX 313","RX 314","RX 315","RX 316","RX 317","RX 318","RX 319","RX 320","RX 321","RX 322","RX 323","RX 324","RX 325","RX 326","RX 327","RX 328","RX 329","RX 330","RX 331","RX 332","RX 333","RX 334","RX 335","RX 336","RX 337","RX 338","RX 339","RX 340","RX 341","RX 342","RX 343","RX 344","RX 345","RX 346","RX 347","RX 348","RX 349","RX 350","RX 351","RX 352","RX 353","RX 354","RX 355","RX 356",
    "RX 501","RX 502","RX 503","RX 504","RX 505","RX 506","RX 507","RX 508","RX 509","RX 510","RX 511","RX 512","RX 513","RX 514","RX 515","RX 516",
    "RX 801","RX 802",
    "RX11.071","RX11.072","RX11.075","RX11.076","RX11.077","RX11.078","RX11.079","RX11.080","RX11.081","RX11.082","RX11.107","RX11.108","RX11.161","RX11.162","RX11.163","RX11.164","RX11.165","RX11.166","RX11.167","RX11.168","RX11.170","RX11.171","RX11.172","RX11.173",
    "RX11.190:1","RX11.190:2","RX11.190:3","RX11.190:4","RX11.191:1","RX11.191:2","RX11.191:3","RX11.191:4",
    "RX11.201","RX11.281","RX11.282","RX11.283","RX11.284","RX11.285",
    "RX 1801","RX 1802","RX 1803","RX 1804","RX 1805","RX 1806","RX 1807","RX 1808","RX 1809","RX 1810","RX 1811","RX 1812","RX 1813","RX 1814","RX 1815","RX 1816","RX 1817","RX 1818",
    "CYO 02","CYO 03","CYO 04","CYO 05","CYO 06","CYO 07","CYO 08","CYO 09","CYO 10","CYO 11","CYO 12","CYO 13","CYO 14","CYO 15","CYO 16","CYO 17","CYO 18","CYO 19","CYO 20","CYO 21","CYO 22","CYO 23","CYO 24","CYO 25","CYO 26","CYO 27","CYO 28",
    "STB11.01","STB11.02","STB11.03","STB11.04","STB11.05","STB11.06",
    "FOX 02","FOX 04","FOX 06","FOX 08",
    "TEL 02","TEL 04","TEL 06","TEL 08",
    "TL06.01","TL06.02","TL06.03","TL06.04","TL06.05","TL06.06","TL06.07","TL06.08","TL06.09","TL06.10","TL06.11","TL06.12","TL06.13","TL06.14","TL06.15","TL06.16","TL06.17","TL06.18","TL06.19","TL06.20","TL06.21","TL06.22","TL06.23","TL06.24",
    "TL30.31","TL30.32","TL30.33","TL30.34","TL30.35","TL30.36","TL30.37","TL30.38","TL30.39","TL30.40","TL30.41","TL30.42",
    "APPLETV 1","APPLETV 2","APPLETV 3",
    "SKYPE 1","SKYPE 2",
    "TOR 02","TOR 04","TOR 06","TOR 08","TOR 10","TOR 12","TOR 14","TOR 16","TOR 18","TOR 20","TOR 22","TOR 24",
    "SRT 1.1","SRT 1.2","SRT 1.4","SRT 2.1","SRT 2.2","SRT 2.3","SRT 2.4","SRT 3.1","SRT 3.2","SRT 3.3","SRT 3.4","SRT 4.1","SRT 4.2","SRT 4.3","SRT 4.4","RX SRT 5","RX SRT 6",
    "EDIT 91","EDIT 92",
    "TBC"
  ]),

  // SMD
  smd: [""].concat([
    "SDM1","SDM2","SDM3","SDM4","SDM5","SDM6","SDM7","SDM8","SDM9","SDM10",
    "N 1.11:8","N 1.11:9","N 1.11:10","N 1.12:05","N 1.13:06","N 1.13:09","N 1.13:10","N 1.13:11",
    "N 1.13:12","N 1.13:13","N 1.13:14","N 1.13:15","N 1.15:11","N 1.15:12","N 1.15:13","N 1.15:14","N 1.15:16",
    "N 2.13:9","N 2.13:10","N 2.13:11","N 2.13:12","N 2.13:13","N 2.13:14","N 2.13:15","N 2.13:16","N 2.14:05",
    "N 2.15:9","N 2.15:10","N 2.15:13","N 2.15:14","N 2.15:16",
    "N 2.16:6","N 3.1:2","N 3.1:3","N 3.1:4","N 3.1:5","N 3.1:6","N 3.1:7","N 3.1:8","N 3.1:9","N 3.1:10",
    "N 4.1:1","N 4.1:2","N 4.1:3","N 4.1:4","N 4.1:5","N 4.1:6","N 4.1:7","N 4.1:8","N 4.1:9","N 4.1:10",
    "N 5.1:1","N 5.1:2","N 5.1:3","N 5.1:4","N 5.1:5","N 5.1:6",
    "N 6.4:6","N 6.4:8","TBC"
  ]),

  // (si en el futuro querés picker de IG/ingesta)
  ig: [""].concat([
    "IG 1.1","IG 1.2","IG 1.3","IG 1.4","IG 2.1","IG 2.2","IG 2.3","IG 2.4",
    "IG 3.1","IG 3.2","IG 3.3","IG 3.4","IG 4.1","IG 4.2","IG 4.3","IG 4.4",
    "IG 12.1","IG 12.2","IG 12.3","IG 12.4",
    "IG 101.1","IG 101.2","IG 101.3","IG 101.4","IG 101.5","IG 101.6","IG 101.7","IG 101.8",
    "IG 102.1","IG 102.2","IG 102.3","IG 102.4","IG 102.5","IG 102.6","IG 102.7","IG 102.8",
    "IG 103.1","IG 103.2","IG 103.3","IG 103.4","IG 103.5","IG 103.6","IG 103.7","IG 103.8",
    "IG 15.1","IG 15.2","IG 15.3","IG 15.4","IG 15.5","IG 15.6","IG 15.7","IG 15.8",
    "IG 16.1","IG 16.2","IG 16.3","IG 16.4","IG 16.5","IG 16.6","IG 16.7","IG 16.8"
  ]),
  ingesta: ["Ingesta 1","Ingesta 2","Ingesta 3"]
};
</script>


<script src="https://js.pusher.com/7.2/pusher.min.js"></script>
<script>
    Pusher.logToConsole = true;

    const pusher = new Pusher('babcc0a46b57b4ae4368', {
        cluster: 'sa1'
    });

    const channel = pusher.subscribe('calendar-channel');

    // Evento de edición de evento existente
channel.bind('event-updated', function (data) {
  const calendar = window.fullCalendar;
  const ev = calendar && calendar.getEventById && calendar.getEventById(data.event_id);
  if (!ev) return;

  const fields = (data && data.updated_fields) || {};
  Object.entries(fields).forEach(([key, value]) => {
    if (value === null || value === undefined) return;
    if (typeof value === "string" && value.trim() === "") return;

    if (key === "title") { ev.setProp("title", value); return; }
    if (key === "color" || key === "backgroundColor") { ev.setProp("backgroundColor", value); return; }
    ev.setExtendedProp(key, value);
  });
});



    // Evento para agregar un nuevo evento
    channel.bind('event-created', function (data) {
        console.log("🆕 Nuevo evento recibido:", data);

        const calendar = window.fullCalendar;

        calendar.addEvent({
            id: data.event.id,
            title: data.event.title,
            start: data.event.start,
            end: data.event.end,
            backgroundColor: data.event.color || "#3788d8",
            extendedProps: data.event
        });
    });
</script>


<script>
document.getElementById("btnTablero").addEventListener("click", function() {
  document.getElementById("tableroModal").style.display = "block";
});
document.getElementById("closeTablero").addEventListener("click", () => {
  document.getElementById("tableroModal").style.display = "none";
});

</script>











<style>

#ajaxModal.tablero-full .modal-dialog {
    max-width: 98% !important;
    width: 98% !important;
    margin: 1% auto !important;
    height: 98% !important;
}

#ajaxModal.tablero-full .modal-content {
    height: 100% !important;
    border-radius: 6px;
}

#ajaxModal.tablero-full .modal-body {
    height: calc(100% - 56px) !important; /* deja espacio al header */
    padding: 0 !important;
    overflow: hidden !important;
}




.tablero-modal {
  display: none;
  position: fixed;
  z-index: 2000;
  left: 0; top: 0;
  width: 100%; height: 100%;
  background: rgba(0,0,0,0.2); /* fondo leve, podés poner transparente */
}

.tablero-modal-content {
    position: relative;
    width: 95%;
    height: 69%;
    margin: auto;
    margin-top: 5%;
    background: #292929;
    border-radius: 6px;
    display: flex;
    flex-direction: column;
}

.tablero-modal-header {
    padding: 8px 12px;
    background: #292929;
    /* border-bottom: 1px solid #ddd; */
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.tablero-modal-body {
  flex: 1;
  padding: 0;
}

.tablero-modal-body iframe {
  width: 100%;
  height: 100%;
  border: none;
}

.close-btn {
  cursor: pointer;
  font-size: 28px;
  font-weight: bold;
  line-height: 1;
}
.close-btn:hover {
  color: #ff5b5b;

}

#event-calendar .tooltip {
  display: none !important;
}

.tooltip.show {
    opacity: 0 !important;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-title {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
}





</style>

<style>
/* ======= Context menu ======= */
#customContextMenu{
  display:none; position:fixed;
  background:#fff;
  border-radius:8px; padding:8px;
  box-shadow:0 8px 28px rgba(0,0,0,.15);
  min-width:220px;
  z-index:12000;
}
#customContextMenu .menu-item{
  display:flex; align-items:center; gap:8px;
  padding:10px 12px; border-radius:6px;
  cursor:pointer; user-select:none;
  font-size:14px; line-height:1.2;
  color:#c1c1c1; /* ← color del texto del menú */
}
#customContextMenu .menu-item + .menu-item{ margin-top:6px; }
#customContextMenu .menu-item:hover{ filter:brightness(.97); }



/* ======= Side panel (picker) ======= */
.ctx-panel{
  position:fixed; left:0; top:0;
  width:260px; background:#383838; color:#fff;
  border-radius:8px; box-shadow:0 10px 32px rgba(0,0,0,.35);
  padding:10px; z-index:1;
}
.ctx-head{ display:flex; align-items:center; justify-content:space-between; margin-bottom:8px; }
.ctx-title{ font-weight:600; font-size:14px; }
.ctx-close{
  background:transparent; border:0; color:#bbb;
  font-size:20px; line-height:1; cursor:pointer; padding:2px 6px;
}
.ctx-close:hover{ color:#fff; }

/* Select base dentro del panel */
.ctx-panel select{
  width:100%; background:#fff; color:#000;
  border-radius:6px; border:1px solid #ccc; padding:6px 8px;
}

/* ======= Select2 encima de todo ======= */
.ctx-panel .select2-container{ width:100% !important; z-index:12600 !important; }
.ctx-panel .select2-dropdown{ z-index:13000 !important; }
</style>


<style>
/* Asegurar que el panel y el dropdown queden arriba de todo */
.ctx-panel{ z-index: 1 !important; position: fixed; }
.select2-dropdown{ z-index: 14000 !important; }
</style>


<script>
document.addEventListener("DOMContentLoaded", () => {
  const modal = document.getElementById("tableroModal");
  const closeBtn = document.getElementById("closeTablero");

  // abrir modal desde tu botón
  document.getElementById("btnAbrirTablero")?.addEventListener("click", () => {
    modal.style.display = "block";
  });

  // cerrar con la cruz
  closeBtn.addEventListener("click", () => {
    modal.style.display = "none";
  });

  // cerrar clickeando afuera del contenido
  window.addEventListener("click", (e) => {
    if (e.target === modal) {
      modal.style.display = "none";
    }
  });
});
</script>

<div id="tableroModal" class="tablero-modal">
  <div class="tablero-modal-content">
    <div class="modal-header d-flex justify-content-between align-items-center">
      <h4 class="modal-title mb-0">Tablero de Ingestas</h4>
      <button type="button" class="btn-close" id="closeTablero" aria-label="Close"></button>
    </div>
    <div class="tablero-modal-body">
      <iframe src="<?= site_url('ingestas/board'); ?>" frameborder="0"></iframe>
    </div>
  </div>
</div>


<style>
#customContextMenu {
    display: none;
    position: fixed;
    background: #383838;
    z-index: 9999;
    padding: 6px 0px;
    border-radius: 7px;
    box-shadow: 0px 0px 10px rgb(0 0 0 / 34%);
    min-width: 200px;
}
#customContextMenu .menu-item {
    padding: 13px 12px;
    cursor: pointer;
    border-radius: 0px;
    margin-bottom: 0px;
    font-size: 14px;
}
#customContextMenu .menu-item:hover {
    filter: brightness(1.2);
    /* background-color: #ffffff59; */
    /* filter: opacity(0.1); */
}

.group1 { background-color: #00ff1538; }
.group2 { background-color: #ff0c0042; }
.group3 { background-color: #41dbf038; }

.group4 {     background-color: #ffeb0038;
    color: #4f0000; }
.group5 { background-color: #1f1f1fff; }


#customContextMenu .menu-item {
  font-weight: 400;      /* opcional */
}

#customContextMenu .menu-item + .menu-item {
    margin-top: 0px; 
}





/* Select2 dentro del panel/menú */
#customContextMenu .select2-container, .ctx-panel .select2-container { width: 100% !important; z-index: 10002; }
#customContextMenu .select2-dropdown,  .ctx-panel .select2-dropdown  { z-index: 10003; }


/* Menú base: debajo del panel y del dropdown */
#customContextMenu {
  z-index: 10000; /* ya lo tenías alto, lo dejamos debajo del panel */
}

/* Panel lateral del picker */
.ctx-panel{
  position: fixed;
  z-index: 1; /* por encima del menú */
  background: #383838;
  border-radius: 6px;
  box-shadow: 0px 0px 10px rgba(0,0,0,.4);
  width: 260px;
  padding: 10px;
}

/* Select2 dentro del panel/menú (dropdown aún más arriba) */
#customContextMenu .select2-container,
.ctx-panel .select2-container { 
  width: 100% !important;
  z-index: 12000 !important;
}
#customContextMenu .select2-dropdown,
.ctx-panel .select2-dropdown  { 
  z-index: 13000 !important;
}



</style>

<script>
(function(){
  // evita doble init
  if (window.__ctxV2__) return;
  window.__ctxV2__ = true;

  const menuEl = document.getElementById('customContextMenu');
  if (!menuEl){ console.warn('No existe #customContextMenu'); return; }

  const BASE = window.BASE_URL || "<?= site_url(); ?>";
  let ctxPanel = null;

  function hideMenu(){ menuEl.style.display = 'none'; }
  function teardown(){ if (ctxPanel){ ctxPanel.remove(); ctxPanel = null; } }

  // === función genérica para traer opciones desde picker_options ===
  function fetchPickerOptions(key){
    return fetch("<?= site_url('picker_options/list_all') ?>")
      .then(r => r.json())
      .then(all => {
        const arr = all[key] || [];
        // siempre anteponer un guion vacío
        return [{ id:"", text:"-" }].concat(
          arr.map(v => ({ id: String(v), text: String(v) }))
        );
      })
      .catch(err => {
        console.error("Error cargando opciones dinámicas:", err);
        return [{ id:"", text:"-" }];
      });
  }

  const fetchSmd = () => fetchPickerOptions("smd");
  const fetchIrd = () => fetchPickerOptions("asi_ip");
  const fetchCv  = () => fetchPickerOptions("conv");
  const fetchIg  = () => fetchPickerOptions("ig");

  function postUpdate(data){
    if (!window.$ || !$.ajax){ alert('Falta jQuery'); return; }
    $.ajax({
      url: BASE + "/events/update_event_fields",
      type: "POST",
      data
    })
    .done(function(){
      try{
        if (window.fullCalendar && window.fullCalendar.refetchEvents) {
          window.fullCalendar.refetchEvents();
        }
      }catch(e){}
    })
    .fail(function(xhr){ alert('Error '+xhr.status); });
  }

function openPicker({title, optionsPromise, eventId, currentValue, onApply}) {
  teardown();

  const mr = menuEl.getBoundingClientRect();
  ctxPanel = document.createElement('div');
  ctxPanel.className = 'ctx-panel';
  ctxPanel.style.left = (mr.right + 8) + 'px';
  ctxPanel.style.top  = mr.top + 'px';

  ctxPanel.innerHTML = `
    <div class="ctx-head">
      <div class="ctx-title">${title}</div>
      <button type="button" class="ctx-close" aria-label="Cerrar" title="Cerrar">&times;</button>
    </div>
    <select id="ctx-select" class="form-control select2" style="width:100%"></select>
    <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:8px">
      <button type="button" id="ctx-accept" class="btn btn-sm btn-primary">Aceptar</button>
    </div>
  `;
  document.body.appendChild(ctxPanel);

  const sel = ctxPanel.querySelector('#ctx-select');
  const closeBtn = ctxPanel.querySelector('.ctx-close');
  const acceptBtn = ctxPanel.querySelector('#ctx-accept');

  function accept() {
    const val = sel.value;
    onApply(val);
    teardown(); 
    hideMenu();
  }

  closeBtn.addEventListener('click', (e)=>{ 
    e.stopPropagation(); 
    teardown(); 
    hideMenu(); 
  });
  acceptBtn.onclick = accept;

optionsPromise().then(function(opts){
  sel.innerHTML = opts.map(o=>`<option value="${o.id}">${o.text}</option>`).join('');


    if (currentValue){
      if (!opts.some(o => o.id === currentValue)) {
        sel.insertAdjacentHTML('afterbegin', `<option value="${currentValue}">${currentValue}</option>`);
      }
      sel.value = currentValue;
    }

    if (window.$ && $.fn && $.fn.select2) {
      try { $(sel).select2('destroy'); } catch(e) {}

      $(sel).select2({
        dropdownParent: $(ctxPanel),
        width: '100%',
        placeholder: '-',
        allowClear: true,
        minimumResultsForSearch: 0
      });

      // abrir apenas creado
      setTimeout(() => $(sel).select2('open'), 0);

      // cuando seleccionás, cerrar dropdown y mover foco al botón
      $(sel).on('select2:select', function() {
        $(sel).select2('close');
        acceptBtn.focus();
      });
    } else {
      // fallback sin select2
      sel.focus();
      sel.addEventListener('keydown', (e)=>{ if (e.key==='Enter') accept(); });
    }

    // Enter en el botón dispara aceptar
    acceptBtn.addEventListener('keydown', function(e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        accept();
      }
    });
  });
}




// === Wrappers globales con guardado directo ===
window.openSmdPickerSidecar = function(eventId, currentValue){
  openPicker({
    title: 'Elegí SMD',
    optionsPromise: fetchSmd,
    eventId, currentValue,
    onApply: (value)=> postUpdate({ id:eventId, smd:value, smd_color:'#FFC107' })
  });
};

window.openIrdPickerSidecar = function(eventId, currentValue){
  openPicker({
    title: 'Elegí IRD',
    optionsPromise: fetchIrd,
    eventId, currentValue,
    onApply: (value)=> postUpdate({ id:eventId, asi_ip:value, asi_ip_color:'#FFC107' })
  });
};

window.openCvPickerSidecar = function(eventId, currentValue){
  openPicker({
    title: 'Elegí Conversor',
    optionsPromise: fetchCv,
    eventId, currentValue,
    onApply: (value)=> postUpdate({ id:eventId, conv:value, conv_color:'#FFC107' })
  });
};

function openIgPickerSidecar(eventId, currentValue, onApplyOverride){
  const BADGE_YELLOW = '#FFC107';
  const EVENT_YELLOW = '#FFC107';

  openPicker({
    title: 'Asignar IG',
    optionsPromise: fetchIg,
    eventId,
    currentValue: currentValue || '',
    onApply: function(value){
      if (typeof onApplyOverride === 'function'){
        onApplyOverride(value);
        return;
      }
      postUpdate({
        id: eventId,
        ig: value,
        ig_color: (value || '') === '' ? '#888' : BADGE_YELLOW,
        color: EVENT_YELLOW
      });
    }
  });
}


// click en ítems del menú
menuEl.addEventListener('click', function(e){
  const item = e.target.closest('.menu-item');
  if (!item) return;

  const id = menuEl.dataset.eventId || '';
  if (!id){ hideMenu(); return; }

  const act = (item.textContent || '').toLowerCase().replace(/\s+/g,' ').trim();

  // valores actuales para preseleccionar
  let cur = {};
  try{
    const ev = (window.fullCalendar && typeof fullCalendar.getEventById === 'function')
      ? fullCalendar.getEventById(id) : null;
    cur = ev ? (ev.extendedProps || {}) : {};
  }catch(_){}

  // —— pickers ——
  if (act === 'asignar smd' || act === 'cargar smd') {
    openSmdPickerSidecar(id, cur.smd || '');
    return;
  }
  if (act === 'asignar ird' || act === 'carga ird' || act === 'cargar ird') {
    openIrdPickerSidecar(id, cur.asi_ip || '');
    return;
  }
  if (act === 'asignar cv' || act === 'cargar cv') {
    openCvPickerSidecar(id, cur.conv || '');
    return;
  }
  if (act === 'asignar ig' || act === 'carga ig' || act === 'cargar ig') {
    openIgPickerSidecar(id, cur.ig || '');
    return;
  }

  // —— acciones rápidas con colores/badges ——
  if (act === 'cargado' || act === 'pgm ing'){ 
    const GREEN = '#4CAF50';
    const BG = '#FB8C00';   // fondo naranja
    const FG = '#000000ff'; // texto negro
    postUpdate({ 
      id, 
      extra_bg_color: BG, 
      extra_text_color: FG,
      smd_color:GREEN, 
      asi_ip_color:GREEN, 
      conv_color:GREEN 
    });
    hideMenu(); return;
  }

  if (act === 'chequeado') {
    const GREEN = '#4CAF50';
    const BG = '#4CAF50';   // fondo verde
    const FG = '#000000ff'; // texto negro
    postUpdate({ 
      id, 
      extra_bg_color: BG, 
      extra_text_color: FG,
      smd_color:GREEN, 
      asi_ip_color:GREEN, 
      conv_color:GREEN 
    });
    hideMenu(); return;
  }

  if (act === 'faltan datos') {
    const BG = '#E53935';   // rojo fuerte
    const FG = '#FFC107';   // amarillo
    postUpdate({ 
      id, 
      extra_bg_color: BG, 
      extra_text_color: FG
    });
    hideMenu(); return;
  }

  if (act === 'grabando') {
    const GREENN = '#4CAF50';
    const GREEN  = '#4CAF50';
    const FG     = '#000000ff';
    postUpdate({ 
      id, 
      smd_color: GREEN,
      asi_ip_color: GREEN,
      conv_color: GREEN,
      ig_color: GREEN,
      extra_bg_color: GREEN,
      extra_text_color: FG,
      color: GREENN
    });
    hideMenu(); return;
  }

  if (act === 'backup') {
    const GREY = '#757575ff';
    const FG   = '#000';
    postUpdate({ 
      id, 
      smd_color: GREY,
      asi_ip_color: GREY,
      conv_color: GREY,
      ig_color: GREY,
      extra_bg_color: GREY,
      extra_text_color: FG,
      color: GREY
    });
    hideMenu(); return;
  }

  if (act === 'finalizado') {
    const CELESTE = '#00B5E2';
    const FG      = '#000';
    postUpdate({ 
      id, 
      smd_color: CELESTE,
      asi_ip_color: CELESTE,
      conv_color: CELESTE,
      ig_color: CELESTE,
      extra_bg_color: CELESTE,
      extra_text_color: FG,
      color: CELESTE
    });
    hideMenu(); return;
  }

  hideMenu();
});

// cerrar al click afuera / ESC
document.addEventListener('click', (e)=>{
  const inMenu  = menuEl.contains(e.target);
  const inPanel = ctxPanel && ctxPanel.contains(e.target);
  if (!inMenu && !inPanel){ teardown(); hideMenu(); }
}, true);

document.addEventListener('keydown', (e)=>{
  if (e.key==='Escape'){ teardown(); hideMenu(); }
});
})();
 </script>


