<div id="page-content" class="page-wrapper clearfix">
    <div class="row">
        <div class="col-sm-3 col-lg-2">
            <?php
            $tab_view['active_tab'] = "custom_fields";
            echo view("settings/tabs", $tab_view);
            ?>
        </div>
        <div class="col-sm-9 col-lg-10">

            <div class="card clearfix">

                <ul id="custom-field-tab" data-bs-toggle="ajax-tab" class="nav nav-tabs bg-white title scrollable-tabs" role="tablist">
                    <li class="title-tab"><h4 class="pl15 pt10 pr15"><?php echo app_lang("custom_fields"); ?></h4></li>
                    <li>
  <a role="presentation" data-bs-toggle="tab"
     data-related_to="picker_options"
     href="<?php echo_uri("custom_fields/picker_options/"); ?>"
     data-bs-target="#custom-field-picker_options">
     <?php echo "Fuentes"; ?>
  </a>
</li>

                    <li><a role="presentation" data-bs-toggle="tab" data-related_to="clients"  href="javascript:;" data-bs-target="#custom-field-clients"><?php echo app_lang("clients"); ?></a></li>
                    <li><a role="presentation" data-bs-toggle="tab" data-related_to="client_contacts" class="" href="<?php echo_uri("custom_fields/client_contacts/"); ?>" data-bs-target="#custom-field-client_contacts"><?php echo app_lang("client_contacts"); ?></a></li>
                    <?php if (get_setting("module_lead")) { ?>
                        <li><a role="presentation" data-bs-toggle="tab" data-related_to="leads"  href="<?php echo_uri("custom_fields/leads/"); ?>" data-bs-target="#custom-field-leads"><?php echo app_lang("leads"); ?></a></li>
                        <li><a role="presentation" data-bs-toggle="tab" data-related_to="lead_contacts" class="" href="<?php echo_uri("custom_fields/lead_contacts/"); ?>" data-bs-target="#custom-field-lead_contacts"><?php echo app_lang("lead_contacts"); ?></a></li>
                    <?php } ?>
                    <li><a role="presentation" data-bs-toggle="tab" data-related_to="projects" href="<?php echo_uri("custom_fields/projects/"); ?>" data-bs-target="#custom-field-projects"><?php echo app_lang('projects'); ?></a></li>
                    <li><a role="presentation" data-bs-toggle="tab" data-related_to="tasks" href="<?php echo_uri("custom_fields/tasks/"); ?>" data-bs-target="#custom-field-tasks"><?php echo app_lang('tasks'); ?></a></li>
                    <li><a role="presentation" data-bs-toggle="tab" data-related_to="team_members" href="<?php echo_uri("custom_fields/team_members/"); ?>" data-bs-target="#custom-field-team_members"><?php echo app_lang('team_members'); ?></a></li>
                    <?php if (get_setting("module_ticket")) { ?>
                        <li><a role="presentation" data-bs-toggle="tab" data-related_to="tickets" href="<?php echo_uri("custom_fields/tickets/"); ?>" data-bs-target="#custom-field-tickets"><?php echo app_lang('tickets'); ?></a></li>
                    <?php } ?>
                    <?php if (get_setting("module_invoice")) { ?>
                        <li><a role="presentation" data-bs-toggle="tab" data-related_to="invoices" href="<?php echo_uri("custom_fields/invoices/"); ?>" data-bs-target="#custom-field-invoices"><?php echo app_lang('invoices'); ?></a></li>
                    <?php } ?>
                    <?php if (get_setting("module_event")) { ?>
                        <li><a role="presentation" data-bs-toggle="tab" data-related_to="events" href="<?php echo_uri("custom_fields/events/"); ?>" data-bs-target="#custom-field-events"><?php echo app_lang('events'); ?></a></li>
                    <?php } ?>
                    <?php if (get_setting("module_expense")) { ?>
                        <li><a role="presentation" data-bs-toggle="tab" data-related_to="expenses" href="<?php echo_uri("custom_fields/expenses/"); ?>" data-bs-target="#custom-field-expenses"><?php echo app_lang('expenses'); ?></a></li>
                    <?php } ?>
                    <?php if (get_setting("module_estimate")) { ?>
                        <li><a role="presentation" data-bs-toggle="tab" data-related_to="estimates" href="<?php echo_uri("custom_fields/estimates/"); ?>" data-bs-target="#custom-field-estimates"><?php echo app_lang('estimates'); ?></a></li>
                    <?php } ?>
                    <?php if (get_setting("module_contract")) { ?>
                        <li><a role="presentation" data-bs-toggle="tab" data-related_to="contracts" href="<?php echo_uri("custom_fields/contracts/"); ?>" data-bs-target="#custom-field-contracts"><?php echo app_lang('contracts'); ?></a></li>
                    <?php } ?>
                    <?php if (get_setting("module_proposal")) { ?>
                        <li><a role="presentation" data-bs-toggle="tab" data-related_to="proposals" href="<?php echo_uri("custom_fields/proposals/"); ?>" data-bs-target="#custom-field-proposals"><?php echo app_lang('proposals'); ?></a></li>
                    <?php } ?>
                    <?php if (get_setting("module_order")) { ?>
                        <li><a role="presentation" data-bs-toggle="tab" data-related_to="orders" href="<?php echo_uri("custom_fields/orders/"); ?>" data-bs-target="#custom-field-orders"><?php echo app_lang('orders'); ?></a></li>
                    <?php } ?>
                    <?php if (get_setting("module_project_timesheet")) { ?>
                        <li><a role="presentation" data-bs-toggle="tab" data-related_to="timesheets" href="<?php echo_uri("custom_fields/timesheets/"); ?>" data-bs-target="#custom-field-timesheets"><?php echo app_lang('timesheets'); ?></a></li>
                    <?php } ?>
                    <li><a role="presentation" data-bs-toggle="tab" data-related_to="project_files" href="<?php echo_uri("custom_fields/project_files/"); ?>" data-bs-target="#custom-field-project_files"><?php echo app_lang('project_files'); ?></a></li>
                    <?php if (get_setting("module_subscription")) { ?>
                        <li><a role="presentation" data-bs-toggle="tab" data-related_to="subscriptions" href="<?php echo_uri("custom_fields/subscriptions/"); ?>" data-bs-target="#custom-field-subscriptions"><?php echo app_lang('subscriptions'); ?></a></li>
                    <?php } ?>
                    <div class="tab-title clearfix no-border">
                        <div class="title-button-group">
                            <?php echo modal_anchor(get_uri("custom_fields/modal_form"), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('add_field'), array("class" => "btn btn-default", "id" => "add-field-button", "data-post-related_to" => "clients", "title" => app_lang('add_field'))); ?>
                        </div>
                    </div>
                </ul>


                <div class="tab-content">
<div role="tabpanel" class="tab-pane fade" id="custom-field-picker_options">
    <div class="mb0 p20">
        <div class="table-responsive general-form">

            <!-- Formulario de alta -->
            <form id="picker-options-form" method="post" action="<?php echo_uri("picker_options/save"); ?>">
                <label for="tipo">Categoría:</label>
                <select name="tipo" class="form-control">
                    <option value="conv">Conversores (CV)</option>
                    <option value="asi_ip">IRD / ASI-IP</option>
                    <option value="ig">IG</option>
                    <option value="ingesta">Ingesta</option>
                    <option value="smd">SMD</option>
                </select>

                <label for="nombres" class="mt10">Valores (separados por coma):</label>
                <textarea name="nombres" class="form-control" rows="3"
                    placeholder="Ej: CV 001, CV 002, RX 301, IG 1.1"></textarea>

                <button type="submit" class="btn btn-primary mt10"><?php echo app_lang('save'); ?></button>
            </form>

            <!-- Listado dinámico -->
            <div id="picker-options-list" class="mt20"></div>
        </div>
    </div>
</div>


                    <div role="tabpanel" class="tab-pane fade" id="custom-field-client_contacts"></div>
                    <div role="tabpanel" class="tab-pane fade" id="custom-field-lead_contacts"></div>
                    <div role="tabpanel" class="tab-pane fade" id="custom-field-leads"></div>
                    <div role="tabpanel" class="tab-pane fade" id="custom-field-projects"></div>
                    <div role="tabpanel" class="tab-pane fade" id="custom-field-tasks"></div>
                    <div role="tabpanel" class="tab-pane fade" id="custom-field-team_members"></div>
                    <div role="tabpanel" class="tab-pane fade" id="custom-field-tickets"></div>
                    <div role="tabpanel" class="tab-pane fade" id="custom-field-invoices"></div>
                    <div role="tabpanel" class="tab-pane fade" id="custom-field-events"></div>
                    <div role="tabpanel" class="tab-pane fade" id="custom-field-expenses"></div>
                    <div role="tabpanel" class="tab-pane fade" id="custom-field-estimates"></div>
                    <div role="tabpanel" class="tab-pane fade" id="custom-field-contracts"></div>
                    <div role="tabpanel" class="tab-pane fade" id="custom-field-proposals"></div>
                    <div role="tabpanel" class="tab-pane fade" id="custom-field-orders"></div>
                    <div role="tabpanel" class="tab-pane fade" id="custom-field-timesheets"></div>
                    <div role="tabpanel" class="tab-pane fade" id="custom-field-project_files"></div>
                    <div role="tabpanel" class="tab-pane fade" id="custom-field-subscriptions"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.delete-option {
    font-size: 11px;       /* más pequeña */
    color: red;
    cursor: pointer;
    display: none;         /* oculta por defecto */
    position: absolute;
    left: 0;               /* ahora a la izquierda */
    top: 0;
    bottom: 0;
    margin: auto;
    height: fit-content;
    padding-right: 4px;    /* un mini espacio entre ❌ y el texto */
}

li:hover .delete-option {
    display: inline;
}


</style>

<script type="text/javascript">
    $(document).ready(function () {
        $("#custom-field-tab a").click(function () {
            $("#add-field-button").attr("data-post-related_to", $(this).attr("data-related_to"));
        });

        setTimeout(function () {
            var tab = "<?php echo $tab; ?>";
            if (tab) {
                $("[data-bs-target='#custom-field-" + tab + "']").trigger("click");
            }
        }, 210);


        loadCustomFieldTable("clients");

    });

    loadCustomFieldTable = function (relatedTo) {

        $("#custom-field-table-" + relatedTo).appTable({
            source: '<?php echo_uri("custom_fields/list_data") ?>' + "/" + relatedTo,
            order: [[1, "asc"]],
            hideTools: true,
            displayLength: 100,
            columns: [
                {title: '<?php echo app_lang("title") ?>'},
                {visible: false},
                {title: '<i data-feather="menu" class="icon-16"></i>', "class": "text-right option w100"}
            ],
            onInitComplete: function () {
                //apply sortable
                $("#custom-field-table-" + relatedTo).find("tbody").attr("id", "custom-field-table-sortable-" + relatedTo);
                var $selector = $("#custom-field-table-sortable-" + relatedTo);

                Sortable.create($selector[0], {
                    animation: 150,
                    chosenClass: "sortable-chosen",
                    ghostClass: "sortable-ghost",
                    onUpdate: function (e) {
                        appLoader.show();
                        //prepare sort indexes 
                        var data = "";
                        $.each($selector.find(".field-row"), function (index, ele) {
                            if (data) {
                                data += ",";
                            }

                            data += $(ele).attr("data-id") + "-" + index;
                        });

                        //update sort indexes
                        $.ajax({
                            url: '<?php echo_uri("custom_fields/update_field_sort_values") ?>' + "/" + relatedTo,
                            type: "POST",
                            data: {sort_values: data},
                            success: function () {
                                appLoader.hide();
                            }
                        });
                    }
                });

            }
        });
    };

// cargar listado actual de opciones
function loadPickerOptions() {
    $.get("<?php echo_uri('picker_options/json'); ?>", function(data) {
        let html = "";
        for (let tipo in data) {
            html += "<h6>"+tipo+"</h6><ul>";
            data[tipo].forEach(v => { html += "<li>"+v+"</li>"; });
            html += "</ul>";
        }
        $("#picker-options-list").html(html);
    }, "json");
}

$(document).ready(function () {
    loadPickerOptions();
});

function loadPickerOptions() {
    $.get("<?php echo_uri('picker_options/json'); ?>", function(data) {
        let html = "";
        for (let tipo in data) {
            html += `
                <div style="margin-top:20px;">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <h6 style="margin:0;">${tipo.toUpperCase()}</h6>
                        <button class="btn btn-sm btn-danger delete-all" data-tipo="${tipo}">
                            Vaciar categoría
                        </button>
                    </div>
                    <ul style="margin-top:8px; list-style:none; padding-left:0;">
            `;
data[tipo].forEach(item => {
    html += `
        <li style="margin-bottom:4px; position:relative; padding-left:18px;">
            <span class="delete-option" data-id="${item.id}">❌</span>
            ${item.nombre}
        </li>
    `;
});

            html += "</ul></div>";
        }
        $("#picker-options-list").html(html);

        // Borrar un item
        $(".delete-option").click(function(){
            const id = $(this).data("id");
            if (confirm("¿Eliminar este valor?")) {
                $.post("<?php echo_uri('picker_options/delete'); ?>/" + id, function(r){
                    if (r.success) {
                        loadPickerOptions();
                    }
                }, "json");
            }
        });

        // Vaciar categoría
        $(".delete-all").click(function(){
            const tipo = $(this).data("tipo");
            if (confirm("¿Eliminar todos los valores de " + tipo.toUpperCase() + "?")) {
                $.post("<?php echo_uri('picker_options/delete_by_tipo'); ?>/" + tipo, function(r){
                    if (r.success) {
                        loadPickerOptions();
                    }
                }, "json");
            }
        });
    }, "json");
}


</script>