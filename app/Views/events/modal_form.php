<?php echo form_open(get_uri("events/save"), array("id" => "event-form", "class" => "general-form", "role" => "form")); ?>
<!-- En tu layout general -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>


<div id="events-dropzone" class="post-dropzone">
    <div class="modal-body clearfix">
        <div class="container-fluid">
            <input type="hidden" name="id" value="<?php echo $model_info->id; ?>" />
            <div class="form-group">
                <div class="row">
                    <label for="title" class=" col-md-3"><?php echo app_lang('title'); ?></label>
                    <div class=" col-md-9">
                        <?php
                        echo form_input(array(
                            "id" => "title",
                            "name" => "title",
                            "value" => $model_info->title,
                            "class" => "form-control",
                            "placeholder" => app_lang('title'),
                            "autofocus" => true,
                            "data-rule-required" => true,
                            "data-msg-required" => app_lang("field_required"),
                        ));
                        ?>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <label for="description" class=" col-md-3"><?php echo app_lang('description'); ?></label>
                    <div class=" col-md-9">
                        <?php
                        echo form_textarea(array(
                            "id" => "description",
                            "name" => "description",
                            "value" => process_images_from_content($model_info->description, false),
                            "class" => "form-control",
                            "placeholder" => app_lang('description'),
                            "data-rich-text-editor" => true
                        ));
                        ?>
                    </div>
                </div>
            </div>

            <div class="clearfix">
                <div class="row">
                    <label for="start_date" class=" col-md-3 col-sm-3"><?php echo app_lang('start_date'); ?></label>
                    <div class="col-md-3 col-sm-4 form-group">
                        <?php
                        echo form_input(array(
                            "id" => "start_date",
                            "name" => "start_date",
                            "value" => $model_info->start_date,
                            "class" => "form-control",
                            "placeholder" => app_lang('start_date'),
                            "autocomplete" => "off",
                            "data-rule-required" => true,
                            "data-msg-required" => app_lang("field_required"),
                        ));
                        ?>
                    </div>
                    <label for="start_time" class=" col-md-2 col-sm-2"><?php echo app_lang('start_time'); ?></label>
                    <div class="col-md-4 col-sm-3">
                    <div style="display: flex; gap: 6px;">
                        <?php
                        if (is_date_exists($model_info->start_time) && $model_info->start_time == "00:00:00") {
                            $start_time = "";
                        } else {
                            $start_time = $model_info->start_time;
                        }

                        if ($time_format_24_hours) {
                            $start_time = $start_time ? date("H:i", strtotime($start_time)) : "";
                        } else {
                            $start_time = $start_time ? convert_time_to_12hours_format(date("H:i:s", strtotime($start_time))) : "";
                        }

                        echo form_input(array(
                            "id" => "start_time",
                            "name" => "start_time",
                            "value" => $start_time,
                            "class" => "form-control",
                            "placeholder" => app_lang('start_time')
                        ));
                        ?>
                            <button type="button" class="btn btn-sm btn-secondary" onclick="setCurrentDateTime()">
        Ahora
    </button>
</div></div>
                    </div>
                </div>
         


<div class="clearfix">
    <div class="row">
        <label for="end_date" class="col-md-3 col-sm-3"><?php echo app_lang('end_date'); ?></label>
        <div class="col-md-3 col-sm-4 form-group">
            <?php
            echo form_input(array(
                "id" => "end_date",
                "name" => "end_date",
                "value" => $model_info->end_date,
                "class" => "form-control",
                "placeholder" => app_lang('end_date'),
                "autocomplete" => "off",
                "data-rule-greaterThanOrEqual" => "#start_date",
                "data-msg-greaterThanOrEqual" => app_lang("end_date_must_be_equal_or_greater_than_start_date")
            ));
            ?>
        </div>

        <label for="end_time" class="col-md-2 col-sm-2"><?php echo app_lang('end_time'); ?></label>
        <div class="col-md-4 col-sm-3">
            <div style="display: flex; gap: 6px; align-items:center;">
                <?php
                if (is_date_exists($model_info->end_time) && $model_info->end_time == "00:00:00") {
                    $end_time = "";
                } else {
                    $end_time = $model_info->end_time;
                }

                if ($time_format_24_hours) {
                    $end_time = $end_time ? date("H:i", strtotime($end_time)) : "";
                } else {
                    $end_time = $end_time ? convert_time_to_12hours_format(date("H:i:s", strtotime($end_time))) : "";
                }

                echo form_input(array(
                    "id" => "end_time",
                    "name" => "end_time",
                    "value" => $end_time,
                    "class" => "form-control",
                    "placeholder" => app_lang('end_time') // 👈 ahora correcto
                ));
                ?>
                <!-- Botones rápidos -->
                <button type="button" class="btn btn-xs btn-secondary" onclick="setEndTimeOffset(2)">+2h</button>
                <button type="button" class="btn btn-xs btn-secondary" onclick="setEndTimeOffset(3)">+3h</button>
                <button type="button" class="btn btn-xs btn-secondary" onclick="setEndTimeOffset(5)">+5h</button>
            </div>
        </div>
    </div>
</div>



<script>
function parseRiseDate(dateStr) {
    // espera dd/mm/yyyy
    const parts = dateStr.split("/");
    if (parts.length === 3) {
        return `${parts[2]}-${parts[1]}-${parts[0]}`;
    }
    return dateStr; // si ya viene yyyy-mm-dd
}

// Setear ahora mismo
function setCurrentDateTime() {
    const now = new Date();

    const dd = String(now.getDate()).padStart(2, '0');
    const mm = String(now.getMonth() + 1).padStart(2, '0');
    const yyyy = now.getFullYear();
    const formattedDate = `${dd}/${mm}/${yyyy}`;

    const hh = now.getHours(); // 0–23
    const min = String(now.getMinutes()).padStart(2, '0');
    const formattedTime = `${hh}:${min}`;

    document.getElementById("start_date").value = formattedDate;
    document.getElementById("start_time").value = formattedTime;
}

// Botones +2h/+3h/+5h
function setEndTimeOffset(hours) {
    const startDateInput = document.getElementById("start_date");
    const startTimeInput = document.getElementById("start_time");
    const endDateInput   = document.getElementById("end_date");
    const endTimeInput   = document.getElementById("end_time");

    if (!startDateInput.value || !startTimeInput.value) {
        setCurrentDateTime();
    }

    const parts = startDateInput.value.split("/");
    const isoDate = `${parts[2]}-${parts[1]}-${parts[0]}`;

    let [h, m] = (startTimeInput.value || "0:00").split(":");
    h = parseInt(h, 10) || 0;
    m = parseInt(m, 10) || 0;

    let base = new Date(`${isoDate}T${String(h).padStart(2,"0")}:${String(m).padStart(2,"0")}:00`);
    base.setHours(base.getHours() + hours);

    const dd = String(base.getDate()).padStart(2, '0');
    const mm = String(base.getMonth() + 1).padStart(2, '0');
    const yyyy = base.getFullYear();
    endDateInput.value = `${dd}/${mm}/${yyyy}`;

    endTimeInput.value = `${base.getHours()}:${String(base.getMinutes()).padStart(2,"0")}`;
}

// Timepicker libre (minuto a minuto, no 5 en 5)
setTimePicker("#start_time, #end_time", {
    minuteStep: 1,
    showMeridian: false,
    defaultTime: false
});



// 🔧 Normalizar entrada manual al formato H:mm
function normalizeTimeInput(el) {
    let val = el.value.trim();

    // aceptar "09:17", "9:17", "9.17", "917"
    val = val.replace(".", ":").replace(",", ":");

    if (/^\d{1,2}:\d{1,2}$/.test(val)) {
        let [h, m] = val.split(":").map(v => parseInt(v, 10));
        if (isNaN(h) || h < 0 || h > 23) h = 0;
        if (isNaN(m) || m < 0 || m > 59) m = 0;
        el.value = `${h}:${String(m).padStart(2,"0")}`;
    }
}

// Aplicar normalización al salir del input
["start_time", "end_time"].forEach(id => {
    const el = document.getElementById(id);
    if (!el) return;
    el.addEventListener("blur", function() {
        normalizeTimeInput(el);
    });
});


</script>



            <!-- Incluir Chosen.js CSS y JS -->
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/chosen/1.8.7/chosen.min.css">
            <script src="https://cdnjs.cloudflare.com/ajax/libs/chosen/1.8.7/chosen.jquery.min.js"></script>

            <!-- Estilo personalizado para Chosen.js -->
            <style>
                .chosen-container {
                    width: 100% !important;
                    border-radius: 4px !important;
                    background-color: #2d2d2d  !important;
                    border: 0px solidrgb(44, 44, 44) !important;
                    box-shadow: none !important;
                    transition: background 0.5s !important;
                }

                .chosen-container .chosen-single {
                    height: 35px !important;
                    line-height: 32px !important;
                    padding: 0 10px !important;
                    font-size: 14px !important;
                    color: #6c757d !important;
                    /* background-color:rgb(43, 43, 43) !important; */
                    border: 0px solid #20242C !important;
                    background: linear-gradient(#2d2d2d  20%, #2d2d2d  50%, #2d2d2d  52%, #2d2d2d  100%);
                }

                .chosen-container .chosen-single div b {
                    margin-top: 10px !important;
                }

                .chosen-container .chosen-drop {
                    position: absolute;
                    top: 100%;
                    z-index: 1010;
                    width: 100%;
                    border: 0px solid #aaa;
                    border-top: 0;
                    background: rgb(46, 9, 9);
                    -webkit-box-shadow: 0 4px 5px rgba(0, 0, 0, .15);
                    box-shadow: 0 4px 5px rgba(0, 0, 0, .15);
                    clip: rect(0, 0, 0, 0);
                    -webkit-clip-path: inset(100% 100%);
                    clip-path: inset(100% 100%);
                    background-color:rgb(22, 22, 22) !important;
                    /* border: 0px solid #20242C !important; */
                    border-radius: 4px !important;
                    box-shadow: none !important;
                }

                .chosen-container .chosen-results {
                    max-height: 200px !important;
                    overflow-y: auto !important;
                    font-size: 14px !important;
                    color:rgb(104, 104, 104) !important;
                }

                .chosen-container .chosen-results li {
                    padding: 8px 10px !important;
                }

                /* Estilo cuando el select está en foco */
                .chosen-container-single .chosen-single:focus {
                    border-color:rgb(167, 167, 167) !important;
                    box-shadow: 0 0 5px rgba(204, 204, 204, 0.5) !important;
                }

                .chosen-container .chosen-results li.no-results {
                    color: #777;
                    display: list-item;
                    background:rgb(20, 20, 20);
                }

                .chosen-container-single .chosen-search input[type=text] {
                    margin: 1px 0;
                    padding: 4px 20px 4px 5px;
                    width: 100%;
                    height: auto;
                    outline: 0;
                    border: 0px solid #aaa;
                    background: url(chosen-sprite.png) no-repeat 100% -20px;
                    font-size: 1em;
                    font-family: sans-serif;
                    line-height: normal;
                    border-radius: 0;
                    background-color: #20242C;
                    color: #fff;
                }

                .chosen-container-active.chosen-with-drop .chosen-single {
                    border: 1px solid #aaa;
                    border-bottom-right-radius: 0;
                    border-bottom-left-radius: 0;
                    background-image: -webkit-gradient(linear, left top, left bottom, color-stop(20%, #eee), color-stop(80%, #fff));
                    background-image: linear-gradient(#20242C 20%, #20242C 80%);
                    -webkit-box-shadow: 0 1px 0 #fff inset;
                    box-shadow: 0 0px 0 #fff inset;
                }
            </style>


<div class="form-group">
    <div class="row">
        <label for="asi_ip" class="col-md-3">Fuente</label>
        <div class="col-md-9">
            <?php
            $options = array(
                "" => "Selecciona una Fuente",
                "IRD 1-1" => "IRD 1-1",
                "IRD 1-2" => "IRD 1-2",
                "IRD 1-3" => "IRD 1-3",
                "IRD 1-4" => "IRD 1-4",
                "IRD 2-1" => "IRD 2-1",
                "IRD 2-2" => "IRD 2-2",
                "IRD 2-3" => "IRD 2-3",
                "IRD 2-4" => "IRD 2-4",
                "IRD 3-1" => "IRD 3-1",
                "IRD 3-2" => "IRD 3-2",
                "IRD 3-3" => "IRD 3-3",
                "IRD 3-4" => "IRD 3-4",
                "IRD 4-1" => "IRD 4-1",
                "IRD 4-2" => "IRD 4-2",
                "IRD 4-3" => "IRD 4-3",
                "IRD 4-4" => "IRD 4-4",
                "IRD 5-1" => "IRD 5-1",
                "IRD 5-2" => "IRD 5-2",
                "IRD 5-3" => "IRD 5-3",
                "IRD 5-4" => "IRD 5-4",
                "IRD 6-1" => "IRD 6-1",
                "IRD 6-2" => "IRD 6-2",
                "IRD 6-3" => "IRD 6-3",
                "IRD 6-4" => "IRD 6-4",
                "IRD 7-1" => "IRD 7-1",
                "IRD 7-2" => "IRD 7-2",
                "IRD 7-3" => "IRD 7-3",
                "IRD 7-4" => "IRD 7-4",
                "IRD 8-1" => "IRD 8-1",
                "IRD 8-2" => "IRD 8-2",
                "IRD 8-3" => "IRD 8-3",
                "IRD 8-4" => "IRD 8-4",
                "IRD 9" => "IRD 9",
                "IRD 10" => "IRD 10",
                "IRD 11" => "IRD 11",
                "IRD 12" => "IRD 12",
                "IRD 13" => "IRD 13",
                "IRD 14" => "IRD 14",
                "IRD 15-1" => "IRD 15-1",
                "IRD 15-2" => "IRD 15-2",
                "IRD 15-3" => "IRD 15-3",
                "IRD 15-4" => "IRD 15-4",
                "IRD 16-1" => "IRD 16-1",
                "IRD 16-2" => "IRD 16-2",
                "IRD 16-3" => "IRD 16-3",
                "IRD 16-4" => "IRD 16-4",
                "RX 01" => "RX 01",
                "RX 02" => "RX 02",
                "RX 03" => "RX 03",
                "RX 04" => "RX 04",
                "RX 05" => "RX 05",
                "RX 06" => "RX 06",
                "RX 07" => "RX 07",
                "RX 08" => "RX 08",
                "RX 09" => "RX 09",
                "RX 10" => "RX 10",
                "RX 11" => "RX 11",
                "RX 12" => "RX 12",
                "RX 13" => "RX 13",
                "RX 14" => "RX 14",
                "RX 15" => "RX 15",
                "RX 16" => "RX 16",
                "RX 17" => "RX 17",
                "RX 18" => "RX 18",
                "RX 80" => "RX 80",
                "RX 301" => "RX 301",
                "RX 302" => "RX 302",
                "RX 303" => "RX 303",
                "RX 304" => "RX 304",
                "RX 305" => "RX 305",
                "RX 306" => "RX 306",
                "RX 307" => "RX 307",
                "RX 308" => "RX 308",
                "RX 309" => "RX 309",
                "RX 310" => "RX 310",
                "RX 311" => "RX 311",
                "RX 312" => "RX 312",
                "RX 313" => "RX 313",
                "RX 314" => "RX 314",
                "RX 315" => "RX 315",
                "RX 316" => "RX 316",
                "RX 317" => "RX 317",
                "RX 318" => "RX 318",
                "RX 319" => "RX 319",
                "RX 320" => "RX 320",
                "RX 321" => "RX 321",
                "RX 322" => "RX 322",
                "RX 323" => "RX 323",
                "RX 324" => "RX 324",
                "RX 325" => "RX 325",
                "RX 326" => "RX 326",
                "RX 327" => "RX 327",
                "RX 328" => "RX 328",
                "RX 329" => "RX 329",
                "RX 330" => "RX 330",
                "RX 331" => "RX 331",
                "RX 332" => "RX 332",
                "RX 333" => "RX 333",
                "RX 334" => "RX 334",
                "RX 335" => "RX 335",
                "RX 336" => "RX 336",
                "RX 337" => "RX 337",
                "RX 338" => "RX 338",
                "RX 339" => "RX 339",
                "RX 340" => "RX 340",
                "RX 341" => "RX 341",
                "RX 342" => "RX 342",
                "RX 343" => "RX 343",
                "RX 344" => "RX 344",
                "RX 345" => "RX 345",
                "RX 346" => "RX 346",
                "RX 347" => "RX 347",
                "RX 348" => "RX 348",
                "RX 349" => "RX 349",
                "RX 350" => "RX 350",
                "RX 351" => "RX 351",
                "RX 352" => "RX 352",
                "RX 353" => "RX 353",
                "RX 354" => "RX 354",
                "RX 355" => "RX 355",
                "RX 356" => "RX 356",
                "RX 501" => "RX 501",
                "RX 502" => "RX 502",
                "RX 503" => "RX 503",
                "RX 504" => "RX 504",
                "RX 505" => "RX 505",
                "RX 506" => "RX 506",
                "RX 507" => "RX 507",
                "RX 508" => "RX 508",
                "RX 509" => "RX 509",
                "RX 510" => "RX 510",
                "RX 511" => "RX 511",
                "RX 512" => "RX 512",
                "RX 513" => "RX 513",
                "RX 514" => "RX 514",
                "RX 515" => "RX 515",
                "RX 516" => "RX 516",
                "RX 801" => "RX 801",
                "RX 802" => "RX 802",
                "CYO 02" => "CYO 02",
                "CYO 03" => "CYO 03",
                "CYO 04" => "CYO 04",
                "CYO 05" => "CYO 05",
                "CYO 06" => "CYO 06",
                "CYO 07" => "CYO 07",
                "CYO 08" => "CYO 08",
                "CYO 09" => "CYO 09",
                "CYO 10" => "CYO 10",
                "CYO 11" => "CYO 11",
                "CYO 12" => "CYO 12",
                "CYO 13" => "CYO 13",
                "CYO 14" => "CYO 14",
                "CYO 15" => "CYO 15",
                "CYO 16" => "CYO 16",
                "CYO 17" => "CYO 17",
                "CYO 18" => "CYO 18",
                "CYO 19" => "CYO 19",
                "CYO 20" => "CYO 20",
                "CYO 21" => "CYO 21",
                "CYO 22" => "CYO 22",
                "CYO 23" => "CYO 23",
                "CYO 24" => "CYO 24",
                "CYO 25" => "CYO 25",
                "CYO 26" => "CYO 26",
                "CYO 27" => "CYO 27",
                "CYO 28" => "CYO 28",
                "RX11.071" => "RX11.071",
                "RX11.072" => "RX11.072",
                "RX11.075" => "RX11.075",
                "RX11.076" => "RX11.076",
                "RX11.077" => "RX11.077",
                "RX11.078" => "RX11.078",
                "RX11.079" => "RX11.079",
                "RX11.080" => "RX11.080",
                "RX11.081" => "RX11.081",
                "RX11.082" => "RX11.082",
                "RX11.107" => "RX11.107",
                "RX11.108" => "RX11.108",
                "RX11.161" => "RX11.161",
                "RX11.162" => "RX11.162",
                "RX11.163" => "RX11.163",
                "RX11.164" => "RX11.164",
                "RX11.165" => "RX11.165",
                "RX11.166" => "RX11.166",
                "RX11.167" => "RX11.167",
                "RX11.168" => "RX11.168",
                "RX11.170" => "RX11.170",
                "RX11.171" => "RX11.171",
                "RX11.172" => "RX11.172",
                "RX11.173" => "RX11.173",
                "RX11.190:1" => "RX11.190:1",
                "RX11.190:2" => "RX11.190:2",
                "RX11.190:3" => "RX11.190:3",
                "RX11.190:4" => "RX11.190:4",
                "RX11.191:1" => "RX11.191:1",
                "RX11.191:2" => "RX11.191:2",
                "RX11.191:3" => "RX11.191:3",
                "RX11.191:4" => "RX11.191:4",
                "RX11.201" => "RX11.201",
                "RX11.281" => "RX11.281",
                "RX11.282" => "RX11.282",
                "RX11.283" => "RX11.283",
                "RX11.284" => "RX11.284",
                "RX11.285" => "RX11.285",
                "RX 1801" => "RX 1801",
                "RX 1802" => "RX 1802",
                "RX 1803" => "RX 1803",
                "RX 1804" => "RX 1804",
                "RX 1805" => "RX 1805",
                "RX 1806" => "RX 1806",
                "RX 1807" => "RX 1807",
                "RX 1808" => "RX 1808",
                "RX 1809" => "RX 1809",
                "RX 1810" => "RX 1810",
                "RX 1811" => "RX 1811",
                "RX 1812" => "RX 1812",
                "RX 1813" => "RX 1813",
                "RX 1814" => "RX 1814",
                "RX 1815" => "RX 1815",
                "RX 1816" => "RX 1816",
                "RX 1817" => "RX 1817",
                "RX 1818" => "RX 1818",
                "STB11.01" => "STB11.01",
                "STB11.02" => "STB11.02",
                "STB11.03" => "STB11.03",
                "STB11.04" => "STB11.04",
                "STB11.05" => "STB11.05",
                "STB11.06" => "STB11.06",
                "FOX 02" => "FOX 02",
                "FOX 04" => "FOX 04",
                "FOX 06" => "FOX 06",
                "FOX 08" => "FOX 08",
                "TEL 02" => "TEL 02",
                "TEL 04" => "TEL 04",
                "TEL 06" => "TEL 06",
                "TEL 08" => "TEL 08",
                "TL06.01" => "TL06.01",
                "TL06.02" => "TL06.02",
                "TL06.03" => "TL06.03",
                "TL06.04" => "TL06.04",
                "TL06.05" => "TL06.05",
                "TL06.06" => "TL06.06",
                "TL06.07" => "TL06.07",
                "TL06.08" => "TL06.08",
                "TL06.09" => "TL06.09",
                "TL06.10" => "TL06.10",
                "TL06.11" => "TL06.11",
                "TL06.12" => "TL06.12",
                "TL06.13" => "TL06.13",
                "TL06.14" => "TL06.14",
                "TL06.15" => "TL06.15",
                "TL06.16" => "TL06.16",
                "TL06.17" => "TL06.17",
                "TL06.18" => "TL06.18",
                "TL06.19" => "TL06.19",
                "TL06.20" => "TL06.20",
                "TL06.21" => "TL06.21",
                "TL06.22" => "TL06.22",
                "TL06.23" => "TL06.23",
                "TL06.24" => "TL06.24",
                "TL30.31" => "TL30.31",
                "TL30.32" => "TL30.32",
                "TL30.33" => "TL30.33",
                "TL30.34" => "TL30.34",
                "TL30.35" => "TL30.35",
                "TL30.36" => "TL30.36",
                "TL30.37" => "TL30.37",
                "TL30.38" => "TL30.38",
                "TL30.39" => "TL30.39",
                "TL30.40" => "TL30.40",
                "TL30.41" => "TL30.41",
                "TL30.42" => "TL30.42",
                "APPLETV 1" => "APPLETV 1",
                "APPLETV 2" => "APPLETV 2",
                "APPLETV 3" => "APPLETV 3",
                "SKYPE 1" => "SKYPE 1",
                "SKYPE 2" => "SKYPE 2",
                "TOR 02" => "TOR 02",
                "TOR 04" => "TOR 04",
                "TOR 06" => "TOR 06",
                "TOR 08" => "TOR 08",
                "TOR 10" => "TOR 10",
                "TOR 12" => "TOR 12",
                "TOR 14" => "TOR 14",
                "TOR 16" => "TOR 16",
                "TOR 18" => "TOR 18",
                "TOR 20" => "TOR 20",
                "TOR 22" => "TOR 22",
                "TOR 24" => "TOR 24",
                "SRT 1.1" => "SRT 1.1",
                "SRT 1.2" => "SRT 1.2",
                "SRT 1.4" => "SRT 1.4",
                "SRT 2.1" => "SRT 2.1",
                "SRT 2.2" => "SRT 2.2",
                "SRT 2.3" => "SRT 2.3",
                "SRT 2.4" => "SRT 2.4",
                "SRT 3.1" => "SRT 3.1",
                "SRT 3.2" => "SRT 3.2",
                "SRT 3.3" => "SRT 3.3",
                "SRT 3.4" => "SRT 3.4",
                "SRT 4.1" => "SRT 4.1",
                "SRT 4.2" => "SRT 4.2",
                "SRT 4.3" => "SRT 4.3",
                "SRT 4.4" => "SRT 4.4",
                "RX SRT 5" => "RX SRT 5",
                "RX SRT 6" => "RX SRT 6",
                "EDIT 91" => "EDIT 91",
                "EDIT 92" => "EDIT 92",
                "TBC" => "TBC",

            );

            echo form_dropdown("asi_ip", $options, $model_info->asi_ip, 'class="form-control select2" id="asi_ip"');
            ?>
        </div>
    </div>
</div>

            <!-- Formulario con los Selects mejorados con Chosen.js -->
            <div class="form-group">
    <div class="row">
        <label for="conv" class="col-md-3">Conversor</label>    
        <div class="col-md-9">
            <?php
            $options = array("" => "Selecciona un Conversor");

            for ($i = 1; $i <= 161; $i++) {
                $options["CV " . str_pad($i, 3, "0", STR_PAD_LEFT)] = "CV " . str_pad($i, 3, "0", STR_PAD_LEFT);
            }
            for ($i = 950; $i <= 978; $i++) {
                $options["CV $i"] = "CV $i";
            }
            for ($i = 9101; $i <= 9103; $i++) {
                $options["CV $i"] = "CV $i";
            }

            echo form_dropdown(
                "conv",
                $options,
                isset($model_info->conv) ? $model_info->conv : "",
                'class="form-control select2" id="conv"'
            );
            ?>
        </div>
    </div>
</div>




<div class="form-group">
    <div class="row">
        <label for="smd" class="col-md-3">SMD/NOD</label>
        <div class="col-md-9">
            <?php
            $options = array(
                "" => "Selecciona un SMD",
                "SDM1" => "SDM1",
                "SDM2" => "SDM2",
                "SDM3" => "SDM3",
                "SDM4" => "SDM4",
                "SDM5" => "SDM5",
                "SDM6" => "SDM6",
                "SDM7" => "SDM7",
                "SDM8" => "SDM8",
                "SDM9" => "SDM9",
                "SDM10" => "SDM10",
                "N 1.11:8" => "N 1.11:8",
                "N 1.11:9" => "N 1.11:9",
                "N 1.11:10" => "N 1.11:10",
                "N 1.12:05" => "N 1.12:05",
                "N 1.13:06" => "N 1.13:06",
                "N 1.13:09" => "N 1.13:09",
                "N 1.13:10" => "N 1.13:10",
                "N 1.13:11" => "N 1.13:11",
                "N 1.13:12" => "N 1.13:12",
                "N 1.13:13" => "N 1.13:13",
                "N 1.13:14" => "N 1.13:14",
                "N 1.13:15" => "N 1.13:15",
                "N 1.15:11" => "N 1.15:11",
                "N 1.15:12" => "N 1.15:12",
                "N 1.15:13" => "N 1.15:13",
                "N 1.15:14" => "N 1.15:14",
                "N 1.15:16" => "N 1.15:16",
                "N 2.13:9" => "N 2.13:9",
                "N 2.13:10" => "N 2.13:10",
                "N 2.13:11" => "N 2.13:11",
                "N 2.13:12" => "N 2.13:12",
                "N 2.13:13" => "N 2.13:13",
                "N 2.13:14" => "N 2.13:14",
                "N 2.13:15" => "N 2.13:15",
                "N 2.13:16" => "N 2.13:16",
                "N 2.14:05" => "N 2.14:05",
                "N 2.15:9" => "N 2.15:9",
                "N 2.15:10" => "N 2.15:10",
                "N 2.15:13" => "N 2.15:13",
                "N 2.15:14" => "N 2.15:14",
                "N 2.15:16" => "N 2.15:16",
                "N 2.16:6" => "N 2.16:6",
                "N 3.1:2" => "N 3.1:2",
                "N 3.1:3" => "N 3.1:3",
                "N 3.1:4" => "N 3.1:4",
                "N 3.1:5" => "N 3.1:5",
                "N 3.1:6" => "N 3.1:6",
                "N 3.1:7" => "N 3.1:7",
                "N 3.1:8" => "N 3.1:8",
                "N 3.1:9" => "N 3.1:9",
                "N 3.1:10" => "N 3.1:10",
                "N 4.1:1" => "N 4.1:1",
                "N 4.1:2" => "N 4.1:2",
                "N 4.1:3" => "N 4.1:3",
                "N 4.1:4" => "N 4.1:4",
                "N 4.1:5" => "N 4.1:5",
                "N 4.1:6" => "N 4.1:6",
                "N 4.1:7" => "N 4.1:7",
                "N 4.1:8" => "N 4.1:8",
                "N 4.1:9" => "N 4.1:9",
                "N 4.1:10" => "N 4.1:10",
                "N 5.1:1" => "N 5.1:1",
                "N 5.1:2" => "N 5.1:2",
                "N 5.1:3" => "N 5.1:3",
                "N 5.1:4" => "N 5.1:4",
                "N 5.1:5" => "N 5.1:5",
                "N 5.1:6" => "N 5.1:6",
                "N 6.4:6" => "N 6.4:6",
                "N 6.4:8" => "N 6.4:8",
                "TBC" => "TBC"
            );
            echo form_dropdown("smd", $options, $model_info->smd, 'class="form-control select2" id="smd"');
            ?>
        </div>
    </div>
</div>



<div class="form-group">
    <div class="row">
        <label for="ig" class="col-md-3">Ingesta</label>
        <div class="col-md-9">
            <?php
            $options = array(
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
                "IG 16.8" => "IG 16.8",

            );
            echo form_dropdown("ig", $options, $model_info->ig, 'class="form-control select2" id="ig"');
            ?>
        </div>
    </div>
</div>





            <div class="form-group">
                <div class="row">
                    <label for="ingesta" class="col-md-3">Grabar</label>
                    <div class="col-md-9">
                        <?php
                        $options = array(
                            'Si' => 'Si',
                            'No' => 'No'
                        );
                        echo form_dropdown('ingesta', $options, $model_info->ingesta, 'class="form-control"');
                        ?>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="row">
                    <label for="location" class=" col-md-3"><?php echo app_lang('location'); ?></label>
                    <div class=" col-md-9">
                        <?php
                        echo form_input(array(
                            "id" => "location",
                            "name" => "location",
                            "value" => $model_info->location,
                            "class" => "form-control",
                            "placeholder" => app_lang('location'),
                        ));
                        ?>
                    </div>
                </div>
            </div>



            <div class="form-group">
                <div class="row">
                    <label for="extra" class="col-md-3">Datos Sat/SRT</label>
                    <div class="col-md-9 col-sm-9">
                    <div style="display: flex; gap: 6px;">
                        <?php
                        echo form_textarea(array(
                            "id" => "extra",
                            "name" => "extra",
                            "value" => $model_info->extra, // El valor que viene del modelo
                            "class" => "form-control",
                            "placeholder" => "Ingrese la informacion de recepción",
                        ));
                        ?>
                            <button type="button" class="btn btn-info" onclick="procesarTextoSatelital()">Procesar</button>
                    </div></div>
                </div>
            </div>




            <div class="form-group">
                <div class="row">
                    <label for="event_labels" class=" col-md-3"><?php echo app_lang('labels'); ?></label>
                    <div class=" col-md-9">
                        <?php
                        echo form_input(array(
                            "id" => "event_labels",
                            "name" => "labels",
                            "value" => $model_info->labels,
                            "class" => "form-control",
                            "placeholder" => app_lang('labels')
                        ));
                        ?>
                    </div>
                </div>
            </div>

            <?php if ($client_id) { ?>
                <input type="hidden" name="client_id" value="<?php echo $client_id; ?>" />
            <?php } else if (count($clients_dropdown)) { ?>
                    <div class="form-group">
                        <div class="row">
                            <label for="client_id" class=" col-md-3"><?php echo app_lang('client'); ?></label>
                            <div class=" col-md-9">
                                <?php
                                echo form_input(array(
                                    "id" => "clients_dropdown",
                                    "name" => "client_id",
                                    "value" => $model_info->client_id,
                                    "class" => "form-control"
                                ));
                                ?>
                            </div>
                        </div>
                    </div>
            <?php } ?>

            <?php echo view("custom_fields/form/prepare_context_fields", array("custom_fields" => $custom_fields, "label_column" => "col-md-3", "field_column" => " col-md-9")); ?>

            <?php if ($can_share_events) { ?>
                <?php if ($login_user->user_type == "client") { ?>
                    <input type="hidden" name="share_with" value="">
                <?php } else { ?>
                    <div class="form-group">
                        <div class="row">
                            <label for="share_with" class=" col-md-3"><?php echo app_lang('share_with'); ?></label>
                            <div class=" col-md-9">

                                <div id="share_with_container">
                                    <?php echo $get_sharing_options_view; ?>
                                </div>

                            </div>
                        </div>
                    </div>
                <?php } ?>
            <?php } ?>

            <?php echo form_hidden("smd_color", $model_info->smd_color ?? "#ccc"); ?>
            <?php echo form_hidden("ig_color", $model_info->ig_color ?? "#ccc"); ?>
            <?php echo form_hidden("conv_color", $model_info->conv_color ?? "#ccc"); ?>
            <?php echo form_hidden("asi_ip_color", $model_info->asi_ip_color ?? "#ccc"); ?>


            <div class="form-group">
                <div class="row">
                    <div class="col-md-9 ms-auto">
                        <?php echo view("includes/color_plate"); ?>
                    </div>
                </div>
            </div>



            <div class="form-group mb0">
                <div class="col-md-12 row">
                    <?php
                    echo view("includes/file_list", array("files" => $model_info->files));
                    ?>
                </div>
            </div>

            <?php echo view("includes/dropzone_preview"); ?>
        </div>
    </div>

    <div class="modal-footer">
        <?php echo view("includes/upload_button"); ?>
        <button type="button" class="btn btn-default" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span> <?php echo app_lang('close'); ?></button>
        <button type="submit" class="btn btn-primary"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save'); ?></button>
        
    </div>
</div>


<script src="<?php echo base_url('assets/js/sat_parser_full.js'); ?>"></script>




<?php echo form_close(); ?>


<script type="text/javascript">




$(document).ready(function () {
        // Esto asegura que los selects se inicialicen al abrir el modal
        setTimeout(function () {
            $('.select2').select2({
                width: '100%',
                dropdownParent: $('#ajaxModal') // clave para modals
            });
        }, 200);
    });

document.addEventListener("DOMContentLoaded", function () {
        const filterInput = document.getElementById("conv-filter");
        const select = document.getElementById("conv");

        filterInput.addEventListener("keyup", function () {
            const filter = this.value.toUpperCase();
            for (let i = 0; i < select.options.length; i++) {
                const option = select.options[i];
                const txt = option.text.toUpperCase();
                option.style.display = txt.includes(filter) ? "" : "none";
            }
        });
    });



    $(document).ready(function () {

        $("#event-form").appForm({
            onSuccess: function (result) {
                if ($("#event-calendar").length) {
                    window.fullCalendar.refetchEvents();
                    setTimeout(function () {
                        feather.replace();
                    }, 100);
                }

                if (typeof getReminders === 'function') {
                    getReminders();
                }
            }
        });

        setDatePicker("#start_date, #end_date");
        setTimePicker("#start_time, #end_time");

        setTimeout(function () {
            $("#title").focus();
        }, 200);

        $("#event_labels").select2({
            multiple: true,
            data: <?php echo json_encode($label_suggestions); ?>
        });
        $("#event-form .select2").select2();

        //show the specific client contacts readio button after select any client
        $('#clients_dropdown').select2({
            data: <?php echo json_encode($clients_dropdown); ?>
        }).on("change", function () {
            var clientId = $(this).val();

            // re-render the sharing options view
            $.ajax({
                url: "<?php echo_uri("events/get_sharing_options_view") ?>/1",
                type: 'POST',
                data: {
                    id: "<?php echo $model_info->id; ?>",
                    client_id: clientId,
                    share_with: "<?php echo $model_info->share_with; ?>",
                },
                dataType: 'json',
                success: function (result) {
                    $("#share_with_container").html(result.sharing_options_view)
                }
            });
        });

        //show/hide recurring fields
        $("#event_recurring").click(function () {
            if ($(this).is(":checked")) {
                $("#recurring_fields").removeClass("hide");
            } else {
                $("#recurring_fields").addClass("hide");
            }
        });

        $('[data-bs-toggle="tooltip"]').tooltip();

    });

    function onEventColorUpdated(response, $element) {
    if (response.success && response.encrypted_event_id && response.color) {
        var calendarEl = $("#calendar");

        if (calendarEl.length && typeof calendarEl.fullCalendar === "function") {
            var calendar = calendarEl.fullCalendar('getCalendar');
            var event = calendar.clientEvents(function (e) {
                return e.id === response.encrypted_event_id;
            })[0];

            if (event) {
                event.color = response.color;
                event.backgroundColor = response.color;
                event.borderColor = response.color;
                calendar.fullCalendar('updateEvent', event);
            }
        }
    }
}

    
</script>