<div class="color-palet row">
    <?php
    $selected_color = $model_info->color ? $model_info->color : "#6c757d"; // gris por defecto

    $options = array(
        "#28a745" => array("label" => "Cargado", "class" => "btn", "icon" => "check-circle"),
        "#ffc107" => array("label" => "Asignado", "class" => "btn", "icon" => "flag"),
        "#0b3d2e" => array("label" => "Backup", "class" => "btn", "icon" => "shield")
    );

    foreach ($options as $color => $info) {
        $active_class = (strtolower($selected_color) === strtolower($color)) ? "active" : "";
        $box_shadow = ($active_class) ? "box-shadow: 0 0 10px rgba(0,0,0,0.4);" : "";
        echo "
        <div class='col-auto mb-2'>
            <button 
                type='button'
                class='color-tag-selector $active_class' 
                style='min-width: 140px; border-radius: 8px; background-color: $color; color: #fff; border: none; $box_shadow' 
                data-color='$color'>
                <span data-feather='{$info["icon"]}' class='icon-16 me-1'></span> {$info["label"]}
            </button>
        </div>
        ";
    }
    ?>

    <!-- Color personalizado -->
    <div class="col-auto mb-2">
        <input type="color" id="custom-color-picker" value="<?php echo strtolower($selected_color); ?>" class="form-control form-control-color" title="Color personalizado">
        <small class="text-muted">Personalizado</small>
    </div>

    <input type="hidden" id="custom-color" name="color" value="<?php echo strtolower($selected_color); ?>" />
</div>

<script>
    $(document).ready(function () {
        $(".color-tag-selector").click(function () {
            $(".color-tag-selector").removeClass("active").css("box-shadow", "none");
            $(this).addClass("active").css("box-shadow", "0 0 10px rgba(0,0,0,0.4)");
            const color = $(this).data("color");
            $("#custom-color").val(color);
            $("#custom-color-picker").val(color.startsWith('#') ? color : rgbToHex(color));
        });

        $("#custom-color-picker").on("input", function () {
            $(".color-tag-selector").removeClass("active").css("box-shadow", "none");
            $("#custom-color").val($(this).val());
        });

        function rgbToHex(rgb) {
            const result = /^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/.exec(rgb);
            return result ? "#" + result.slice(1).map(x => (+x).toString(16).padStart(2, '0')).join('') : rgb;
        }

        if (typeof feather !== "undefined") {
            feather.replace();
        }
    });
</script>