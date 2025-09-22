<div class="modal-body">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12 clearfix">
                <h4 class="mt0 float-start">
                
                    <?php
                    $share_title = app_lang("share_with") . ": ";
                    if (!$model_info->share_with) {
                        $share_title .= app_lang("only_me");
                    } else if ($model_info->share_with == "all") {
                        $share_title .= app_lang("all_team_members");
                    } else {
                        $share_title .= app_lang("specific_members_and_teams");
                    }

                    echo "<span title='$share_title' style='color:" . $model_info->color . "' class='float-start mr10'><i data-feather='$event_icon' class='icon-16'></i></span> " . $model_info->title;
                    ?>
                </h4>

                <?php if ($model_info->google_event_id) { ?>
                    <div class="float-end pb10 ">
                        <i data-feather="external-link" class="icon-16"></i>
                        <?php echo anchor(get_uri("events/show_event_in_google_calendar/$model_info->google_event_id"), app_lang("open_in_google_calendar"), array("target" => "_blank")); ?>
                    </div>
                <?php } ?>

            </div>

<!-- Eliminar o comentar todo el bloque -->
<?php /*
<?php if ($status) { ?>
    <div class="col-md-12 pb10">
        <?php echo $status; ?>
    </div>
<?php } ?>
*/ ?>

            <div class="col-md-12 pb10 ">
                <i data-feather="clock" class="icon-16"></i>
                <?php
                echo view("events/event_time");
                ?>
            </div>

            <div class="col-md-12 pb10">
                <?php echo $labels; ?>
            </div>

            <?php if ($model_info->description) { ?>
    <div class="col-md-12">
        <blockquote class="font-14 text-justify" 
            style="
                border-color: <?php echo $model_info->color; ?>;
                max-height: 200px; /* Ajusta este valor según necesites */
                overflow-y: auto;
                padding: 10px;
                background-color: #f8f9fa; /* Color de fondo opcional */
                border-left: 5px solid <?php echo $model_info->color; ?>;
                box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);
            ">
            <?php echo custom_nl2br(process_images_from_content($model_info->description)); ?>
        </blockquote>
    </div>
<?php } ?>


            <?php if ($model_info->company_name && $login_user->user_type != "client") { ?>
                <div class="col-md-12 pb10 pt10 ">
                    <i data-feather="<?php echo $model_info->is_lead ? "box" : "briefcase"; ?>" class="icon-16"></i>
                    <?php
                    echo $model_info->is_lead ? anchor("leads/view/" . $model_info->client_id, $model_info->company_name) : anchor("clients/view/" . $model_info->client_id, $model_info->company_name);
                    ?>
                </div>
            <?php } ?>

            <?php if ($model_info->location) { ?>
                <div class="col-md-12 mt5">
                    <div class="font-14"><i data-feather="map-pin" class="icon-16"></i> <?php echo custom_nl2br($model_info->location); ?></div>
                </div>
            <?php } 
            ?>

<?php if ($login_user->is_admin || get_array_value($login_user->permissions, "can_view_event_details")): ?>








<?php
// Verificamos que al menos una de las variables tenga un valor antes de mostrar la sección
if (!empty($model_info->ingesta) || !empty($model_info->conv) || !empty($model_info->ig) || !empty($model_info->asiIp)) { ?>
    <div class="col-md-12 pb10">
        <div class="row text-center">
            <div class="col-md-3">
                <strong>SMD/NOD</strong>
            </div>
            <div class="col-md-3">
                <strong>Conv</strong>
            </div>
            <div class="col-md-3">
                <strong>IG</strong>
            </div>
            <div class="col-md-3">
                <strong>ASI IP</strong>
            </div>
        </div>
        <div class="row text-center mt-2">
            <?php if (!empty($model_info->smd)) { ?>
                <div class="col-md-3">
                    <span class="mt0 badge large" style="background-color:#f10a4a;"><?php echo htmlspecialchars($model_info->smd); ?></span>
                </div>
            <?php } else { ?>
                <div class="col-md-3">-</div>
            <?php } ?>

            <?php if (!empty($model_info->conv)) { ?>
                <div class="col-md-3">
                    <span class="mt0 badge large" style="background-color:#f48a4a;"><?php echo htmlspecialchars($model_info->conv); ?></span>
                </div>
            <?php } else { ?>
                <div class="col-md-3">-</div>
            <?php } ?>

            <?php if (!empty($model_info->ig)) { ?>
                <div class="col-md-3">
                    <span class="mt0 badge large" style="background-color:#4af48a;"><?php echo htmlspecialchars($model_info->ig); ?></span>
                </div>
            <?php } else { ?>
                <div class="col-md-3">-</div>
            <?php } ?>

            <?php if (!empty($model_info->asiIp)) { ?>
                <div class="col-md-3">
                    <span class="mt0 badge large" style="background-color:#f4d04a;"><?php echo htmlspecialchars($model_info->asiIp); ?></span>
                </div>
            <?php } else { ?>
                <div class="col-md-3">-</div>
            <?php } ?>
        </div>
    </div>
<?php } ?>
<?php endif; ?>

<?php if (!empty($modifications)) : ?>
    <div class="col-md-12 pt10 pb10">

        <!-- Cabecera: título + botón -->
        <div class="d-flex justify-content-between align-items-center mb10">
            <strong>Historial de Modificaciones</strong>
            <button type="button" id="btnShowAllHistory" class="btn btn-sm btn-history">
                Mostrar todo
            </button>
        </div>

        <!-- Avatares debajo -->
        <div class="pt5">
            <?php 
            $total = count($modifications);
            $max   = 10;
            $shown = 0;

            foreach ($modifications as $mod) :
                if ($shown >= $max) break;

                $image_url = get_avatar($mod->image);
                $tooltip = esc($mod->first_name . ' ' . $mod->last_name)
                    . " | " . format_to_datetime($mod->modification_time, false)
                    . "\n" . esc($mod->modification_details);
                $icon_type = (stripos($mod->modification_details, 'creado') !== false) ? 'plus-circle' : 'edit-3';
                ?>
                <span class="avatar avatar-xs mr5 position-relative"
                      data-detail="<?= $tooltip; ?>">
                    <img src="<?= $image_url ?>" alt="...">
                    <i data-feather="<?= $icon_type ?>" 
                       style="position: absolute; bottom: -3px; right: -3px; width: 10px; height: 10px;" 
                       class="text-muted"></i>
                </span>
                <?php $shown++; ?>
            <?php endforeach; ?>

            <?php if ($total > $max): ?>
                <span class="avatar avatar-xs mr5 position-relative more-avatars"
                      data-action="show-all">
                    +<?= $total - $max ?>
                </span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal -->
    <div id="historialModal" class="custom-modal">
      <div class="custom-modal-content">
        <span id="closeHistorial" class="close">&times;</span>
        <div id="historialContentWrapper">
          <div id="historialContent"></div>
        </div>
      </div>
    </div>
<?php endif; ?>


<style>
.custom-modal {
  display: none;
  position: fixed;
  z-index: 5000;
  left: 0; top: 0;
  width: 100%; height: 100%;
  background-color: rgba(0,0,0,0.4);
}

.custom-modal.show {
  display: block !important;
}

.more-avatars {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 30px;
  height: 30px;
  border-radius: 50%;
  background: #5e5e5e;
  color: #fff;
  font-size: 12px;
  font-weight: bold;
}


.btn-history {
  background-color: #5e5e5e !important;
  border-color: #707070 !important;
  color: #FFF !important;
}

.btn-history:hover {
  background-color: #707070 !important;
  border-color: #8a8a8a !important;
  color: #ffffff !important;
}


.custom-modal-content {
  background: #292929;
  margin: 10% auto;
  padding: 20px;
  padding-top: 35px; /* 👈 espacio extra arriba para que la cruz no tape el texto */
  border: 1px solid #3d3d3d;
  width: 560px;
  border-radius: 6px;
  box-shadow: 0 2px 8px rgba(0,0,0,.3);
  font-family: "Segoe UI", sans-serif;
  position: relative;
}

.close {
  position: absolute;
  top: 8px;
  right: 12px;
  font-size: 22px;
  font-weight: bold;
  cursor: pointer;
  color: #f0f0f0;
  line-height: 1;
  z-index: 10;
}

.close:hover {
  color: #ff5b5b;
}

/* Wrapper con scroll */
#historialContentWrapper {
  max-height: 400px;
  overflow-y: auto;
  margin-top: 5px;
}

#historialContent ul {
  list-style: none;
  padding-left: 0;
  font-family: "Segoe UI", sans-serif;
  font-size: 14px;
}

#historialContent li {
  margin-bottom: 8px;
  border-bottom: 1px solid #444;
  padding-bottom: 5px;
  white-space: pre-line;
  color: #f0f0f0;
}

</style>

<script>
document.addEventListener("click", function(e) {
  // ¿hiciste click en el botón?
  if (e.target && e.target.id === "btnShowAllHistory") {
    console.log("✅ Botón clickeado");
    const modal = document.getElementById("historialModal");
    const contentBox = document.getElementById("historialContent");

    const items = Array.from(document.querySelectorAll(".avatar[data-detail]"))
      .map(el => el.getAttribute("data-detail"));

    if (items.length > 0) {
      contentBox.innerHTML = `
        <ul style="list-style:none; padding-left:0; font-family:Segoe UI, sans-serif; font-size:14px;">
          ${items.map(txt => `<li style="margin-bottom:8px; border-bottom:1px solid #ddd; padding-bottom:5px; white-space:pre-line;">${txt}</li>`).join("")}
        </ul>
      `;
    } else {
      contentBox.innerHTML = "<em>No hay modificaciones registradas.</em>";
    }
    modal.classList.add("show");
  }

  // ¿hiciste click en la X de cerrar?
  if (e.target && e.target.id === "closeHistorial") {
    document.getElementById("historialModal").classList.remove("show");
  }
});
</script>















            <?php if ($confirmed_by) { ?>
                <div class="col-md-12 clearfix">
                    <div class="pl10 pr10">
                        <div class="row">
                            <div class="col-md-1 p0">
                                <span title="<?php echo app_lang("confirmed"); ?>" class='confirmed-by-logo'><span data-feather="zap"></span></span>
                            </div>
                            <div class="col-md-11 pt10 pl0">
                                <?php echo $confirmed_by; ?>
                            </div>
                        </div> 
                    </div>
                </div>
            <?php } ?>

            <?php if ($rejected_by) { ?>
                <div class="col-md-12 clearfix">
                    <div class="pl10 pr10">
                        <div class="row">
                            <div class="col-md-1 p0">
                                <span title="<?php echo app_lang("rejected"); ?>" class="rejected-by-logo"><i data-feather="x-circle"></i></span>
                            </div>
                            <div class="col-md-11 pt10 pl0">
                                <?php echo $rejected_by; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>




            <?php
            if (count($custom_fields_list)) {
                foreach ($custom_fields_list as $data) {
                    if ($data->value) {
                        ?>
                        <div class="col-md-12 pt10">
                            <strong><?php echo $data->title . ": "; ?> </strong> <?php echo view("custom_fields/output_" . $data->field_type, array("value" => $data->value)); ?>
                        </div>
                        <?php
                    }
                }
            }
            ?>

            <?php
            $files = @unserialize($model_info->files);
            if ($files && is_array($files) && count($files)) {
                ?>
                <div class="clearfix">
                    <div class="col-md-12 mt10 row">
                        <div class="mb10 strong"><?php echo app_lang("files"); ?></div>
                        <?php
                        echo view("includes/file_list", array("files" => $model_info->files, "mode_type" => "view", "context" => "events"));
                        ?>
                    </div>
                </div>


            <?php } ?>

        </div>
    </div>
</div>

<div class="modal-footer">



<?php
if (
    $login_user->id == $model_info->created_by
    || $login_user->is_admin
    || get_array_value($login_user->permissions, "can_edit_public_events") === "1"
    || get_array_value($login_user->permissions, "can_delete_event") === "1"
) {

    // recurring child event's can't be deleted
    $show_delete = true;

    if (isset($model_info->cycle) && $model_info->cycle) {
        $show_delete = false;
    }

    if ($show_delete) {
        echo js_anchor(
            "<i data-feather='x-circle' class='icon-16'></i> Eliminar evento",
            array(
                "class" => "btn btn-default float-start",
                "id" => "delete_event",
                "data-encrypted_event_id" => $encrypted_event_id
            )
        );
    }

    echo modal_anchor(
        get_uri("events/modal_form"),
        "<i data-feather='edit' class='icon-16'></i> " . app_lang('edit_event'),
        array(
            "class" => "btn btn-default",
            "data-post-encrypted_event_id" => $encrypted_event_id,
            "title" => app_lang('edit_event')
        )
    );
}

// show a button to confirm or reject the event
if ($login_user->id != $model_info->created_by) {
    echo $status_button;
}
?>

<?php
// Aseguramos que la variable esté definida
$encrypted_event_id = isset($encrypted_event_id) ? $encrypted_event_id : '';
?>




    <button type="button" class="btn btn-info text-white close-modal" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span> <?php echo app_lang('close'); ?></button>



<script type="text/javascript">
    $(document).ready(function () {

        $('#delete_event').click(function () {
            var encrypted_event_id = $(this).attr("data-encrypted_event_id");
            $(this).appConfirmation({
                title: "<?php echo app_lang('are_you_sure'); ?>",
                btnConfirmLabel: "<?php echo app_lang('yes'); ?>",
                btnCancelLabel: "<?php echo app_lang('no'); ?>",
                onConfirm: function () {
                    appLoader.show();
                    $('.close-modal').trigger("click");

                    $.ajax({
                        url: "<?php echo get_uri('events/delete') ?>",
                        type: 'POST',
                        dataType: 'json',
                        data: {encrypted_event_id: encrypted_event_id},
                        success: function (result) {
                            if (result.success) {
                                window.fullCalendar.refetchEvents();
                                setTimeout(function () {
                                    feather.replace();
                                }, 100);

                                if (typeof getReminders === 'function') {
                                    getReminders();
                                }

                                appAlert.warning(result.message, {duration: 10000});
                            } else {
                                appAlert.error(result.message);
                            }

                            appLoader.hide();
                        }
                    });

                }
            });

            return false;
        });

        $('[data-bs-toggle="tooltip"]').tooltip();

    });


    $(document).ready(function () {
    $("#addRow").click(function () {
        $("#eventTable tbody").append(`
            <tr>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td><button class="btn btn-danger btn-sm removeRow">Eliminar</button></td>
            </tr>
        `);
    });

    $(document).on("click", ".removeRow", function () {
        $(this).closest("tr").remove();
    });

    $("#saveEvents").click(function () {
        let eventos = [];
        $("#eventTable tbody tr").each(function () {
            let fila = $(this).find("td");
            let evento = {
                title: fila.eq(0).text(),
                description: fila.eq(1).text(),
                start_date: fila.eq(2).text(),
                end_date: fila.eq(3).text(),
                start_time: fila.eq(4).text(),
                end_time: fila.eq(5).text(),
                extra: fila.eq(6).text()
            };
            eventos.push(evento);
        });

        $.ajax({
            url: "tu_controlador/guardarEventos",
            type: "POST",
            data: { eventos: JSON.stringify(eventos) },
            success: function (response) {
                alert("Eventos guardados correctamente.");
            },
            error: function () {
                alert("Error al guardar los eventos.");
            }
        });
    });
});

function update_event_color_and_close(encrypted_id, color) {
    console.log("📍 Clic detectado: botón presionado");
    appAlert.info("Enviando solicitud de cambio de color...");

    $.ajax({
        url: "<?= get_uri('events/save_event_color') ?>",
        type: "POST",
        dataType: "json",
        data: {
            encrypted_event_id: encrypted_id,
            color: color
        },
        success: function (response) {
            console.log("✅ Respuesta del servidor:", response);

            if (response.success) {
                appAlert.success(response.message || "Color actualizado correctamente");

                // Cierra el modal correctamente
                $("#ajaxModal").modal("hide");
                $('body').removeClass('modal-open');
                $('.modal-backdrop').remove();

                // Refrescar el calendario entero
                if ($("#calendar").length && $.isFunction($.fn.fullCalendar)) {
                    console.log("🔁 Refrescando eventos del calendario...");
                    $("#calendar").fullCalendar("refetchEvents");
                } else {
                    console.warn("⚠️ No se encontró #calendar o fullCalendar no está disponible.");
                }

            } else {
                console.warn("⚠️ Error reportado por el servidor:", response.message);
                appAlert.error(response.message || "Error al actualizar el color");
            }
        },
        error: function (xhr) {
            console.error("❌ Error de red o del servidor:", xhr.responseText);
            appAlert.error("Hubo un error al actualizar el color");
        }
    });
}




</script>    

