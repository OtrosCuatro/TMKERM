<?php echo form_open(get_uri("events/save_mochila"), array("id" => "mochila-form", "class" => "general-form", "role" => "form")); ?>



<div class="modal-body clearfix">
<div class="form-group row">
    <label for="mochila_extra" class="col-md-2">Mochila</label>
    <div class="col-md-2">
        <?php
        echo form_input(array(
            "id" => "mochila_extra",
            "name" => "extra",
            "class" => "form-control",
            "placeholder" => "Mochila"
        ));
        ?>
    </div>

    <label for="mochila_title" class="col-md-2">Título</label>
    <div class="col-md-6">
        <?php
        echo form_input(array(
            "id" => "mochila_title",
            "name" => "title",
            "class" => "form-control",
            "placeholder" => "Nombre"
        ));
        ?>
    </div>
</div>

<div class="clearfix">
    <div class="row">
        <label for="mochila_date" class="col-md-2 col-sm-2">Fecha</label>
        <div class="col-md-4 col-sm-4 form-group">
            <?php
            echo form_input(array(
                "id" => "mochila_date",
                "name" => "start_date",
                "class" => "form-control",
                "placeholder" => "DD-MM-YYYY",
                "autocomplete" => "off"
            ));
            ?>
        </div>

        <label for="mochila_time" class="col-md-2 col-sm-2">Hora</label>
        <div class="col-md-4 col-sm-4">
            <div style="display: flex; gap: 6px;">
                <?php
                echo form_input(array(
                    "id" => "mochila_time",
                    "name" => "start_time",
                    "class" => "form-control",
                    "placeholder" => "HH:MM"
                ));
                ?>
                <button type="button" class="btn btn-sm btn-secondary" onclick="setCurrentDateTimeForMochila()">
                    Ahora
                </button>
            </div>
        </div>
    </div>
</div>


<div class="form-group row">
    <label for="mochila_ig" class="col-md-2">Ingesta</label>
    <div class="col-md-10">
        <?php
        $ig_options = array(
            "" => "Selecciona una Ingesta",
            "IG 1.1" => "IG 1.1",
            "IG 1.2" => "IG 1.2",
            "IG 1.3" => "IG 1.3",
            "IG 1.4" => "IG 1.4",
            "IG 2.1" => "IG 2.1",
            "IG 2.2" => "IG 2.2",
            "IG 2.3" => "IG 2.3",
            "IG 2.4" => "IG 2.4",
            "IG 3.1" => "IG 3.1",
            "IG 3.2" => "IG 3.2",
            "IG 3.3" => "IG 3.3",
            "IG 3.4" => "IG 3.4",
            "IG 4.1" => "IG 4.1",
            "IG 4.2" => "IG 4.2",
            "IG 4.3" => "IG 4.3",
            "IG 4.4" => "IG 4.4",
            "IG 12.1" => "IG 12.1",
            "IG 12.2" => "IG 12.2",
            "IG 12.3" => "IG 12.3",
            "IG 12.4" => "IG 12.4",
            "IG 101.1" => "IG 101.1",
            "IG 101.2" => "IG 101.2",
            "IG 101.3" => "IG 101.3",
            "IG 101.4" => "IG 101.4",
            "IG 101.5" => "IG 101.5",
            "IG 101.6" => "IG 101.6",
            "IG 101.7" => "IG 101.7",
            "IG 101.8" => "IG 101.8",
            "IG 102.1" => "IG 102.1",
            "IG 102.2" => "IG 102.2",
            "IG 102.3" => "IG 102.3",
            "IG 102.4" => "IG 102.4",
            "IG 102.5" => "IG 102.5",
            "IG 102.6" => "IG 102.6",
            "IG 102.7" => "IG 102.7",
            "IG 102.8" => "IG 102.8",
            "IG 103.1" => "IG 103.1",
            "IG 103.2" => "IG 103.2",
            "IG 103.3" => "IG 103.3",
            "IG 103.4" => "IG 103.4",
            "IG 103.5" => "IG 103.5",
            "IG 103.6" => "IG 103.6",
            "IG 103.7" => "IG 103.7",
            "IG 103.8" => "IG 103.8",
            "IG 15.1" => "IG 15.1",
            "IG 15.2" => "IG 15.2",
            "IG 15.3" => "IG 15.3",
            "IG 15.4" => "IG 15.4",
            "IG 15.5" => "IG 15.5",
            "IG 15.6" => "IG 15.6",
            "IG 15.7" => "IG 15.7",
            "IG 15.8" => "IG 15.8",
            "IG 16.1" => "IG 16.1",
            "IG 16.2" => "IG 16.2",
            "IG 16.3" => "IG 16.3",
            "IG 16.4" => "IG 16.4",
            "IG 16.5" => "IG 16.5",
            "IG 16.6" => "IG 16.6",
            "IG 16.7" => "IG 16.7",
            "IG 16.8" => "IG 16.8"
        );

echo form_dropdown("ig", $ig_options, "", 'class="form-control select2" id="mochila_ig"');
        ?>
    </div>
</div>



<div class="modal-footer">
<button type="button" class="btn btn-default" data-bs-dismiss="modal">
    <span data-feather="x" class="icon-16"></span> Cerrar
</button>
    <button type="submit" class="btn btn-primary">Guardar Mochila</button>
</div>
<?php echo form_close(); ?>


