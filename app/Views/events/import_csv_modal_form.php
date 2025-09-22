<div class="modal-body">

  <!-- Selector de modo -->
  <div class="btn-group mb15" role="group" aria-label="Modo">
    <button type="button" class="btn btn-outline-primary active" id="btnModeImport">Importar CSV</button>
    <button type="button" class="btn btn-outline-danger" id="btnModeDelete">Eliminar día</button>
  </div>

  <!-- ====== FORM IMPORTAR (igual al tuyo) ====== -->
  <?php echo form_open_multipart(get_uri("events/import_csv"), array(
      "id" => "import-csv-form", "class" => "general-form", "role" => "form"
  )); ?>

    <div class="form-group">
      <label class="col-md-12">Plantilla eventos</label><br>
      <a href="https://docs.google.com/spreadsheets/d/1tJdvSLoJD1p_Vgr7QUP5PXWle9UYo4uDqh0AnDqxQqI/edit?usp=sharing" target="_blank">
        Google Sheet <span style="color:#888"> (Exportar como CSV)</span>
      </a>
    </div>

    <div class="form-group">
      <label for="csv_file" class="control-label">Seleccionar archivo CSV</label>
      <?php echo form_upload(array(
          "id" => "csv_file",
          "name" => "csv_file",
          "class" => "form-control w-auto",
          "required" => true
      )); ?>
    </div>

    <div class="form-group">
      <label for="base_date" class="control-label">Fecha base</label>
      <?php echo form_input(array(
          "id" => "base_date",
          "name" => "base_date",
          "class" => "form-control w-auto",
          "type" => "date",
          "required" => true
      )); ?>
    </div>
  <?php echo form_close(); ?>


  <!-- ====== FORM ELIMINAR DÍA (nuevo) ====== -->
  <?php echo form_open(get_uri("events/bulk_delete_day"), array(
      "id" => "bulk-delete-day-form", "class" => "general-form", "role" => "form", "style" => "display:none;"
  )); ?>

    <div class="form-group">
      <label for="del_date" class="control-label">Fecha a eliminar</label>
      <?php echo form_input(array(
          "id" => "del_date",
          "name" => "date",
          "class" => "form-control w-auto",
          "type" => "date"
      )); ?>
    </div>

    <div class="form-group">
      <label class="control-label d-block">¿Qué borrar?</label>

      <div class="radio">
        <label>
          <input type="radio" name="scope" value="csv" checked>
          Solo importados por CSV
        </label>
      </div>

      <div class="radio mt5">
        <label>
          <input type="radio" name="scope" value="csv_batch">
          Solo el lote CSV (batch_id) del día
        </label>
      </div>

      <input type="text" name="batch_id" id="batch_id_input" class="form-control mt10"
             placeholder="Pegá aquí el import_batch_id (ej: 20250915_083418_4e1cf666)" disabled>

      <div class="radio mt10">
        <label>
          <input type="radio" name="scope" value="all">
          Todos los eventos del día
        </label>
      </div>
    </div>

    <div class="alert alert-warning">
      Esto marca los eventos como eliminados (soft-delete). No borra físico.
    </div>

  <?php echo form_close(); ?>

</div>

<div class="modal-footer">
  <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cerrar</button>
  <button type="button" class="btn btn-primary" id="primary-submit-btn">Importar</button>
</div>

<script type="text/javascript">
$(function () {
  var $btnImport = $("#btnModeImport");
  var $btnDelete = $("#btnModeDelete");
  var $formImport = $("#import-csv-form");
  var $formDelete = $("#bulk-delete-day-form");
  var $primaryBtn = $("#primary-submit-btn");

  // Switch de modo
  function setMode(mode) {
    if (mode === "import") {
      $btnImport.addClass("active");
      $btnDelete.removeClass("active");

      $formImport.show();
      $formDelete.hide();
      $primaryBtn.toggleClass("btn-danger btn-primary", false);
      $primaryBtn.text("Importar");
    } else {
      $btnDelete.addClass("active");
      $btnImport.removeClass("active");

      // Prefill fecha si el import tenía algo cargado:
      var baseDateVal = $("#base_date").val();
      if (baseDateVal && !$("#del_date").val()) $("#del_date").val(baseDateVal);

      $formDelete.show();
      $formImport.hide();
      $primaryBtn.toggleClass("btn-primary btn-danger", true);
      $primaryBtn.text("Eliminar");
    }
  }

  $btnImport.on("click", function(){ setMode("import"); });
  $btnDelete.on("click", function(){ setMode("delete"); });

  // Habilitar batch_id solo cuando scope=csv_batch
  $formDelete.find("input[name='scope']").on("change", function(){
    var needsBatch = $(this).val() === "csv_batch";
    $("#batch_id_input").prop("disabled", !needsBatch);
    if (!needsBatch) $("#batch_id_input").val("");
  });

  // Submit del botón principal según modo visible
  $primaryBtn.on("click", function(){
    if ($formImport.is(":visible")) {
      // Validación mínima
      if (!$("#csv_file").val() || !$("#base_date").val()) {
        appAlert.error("Seleccioná un CSV y la fecha base.");
        return;
      }
      $formImport.submit(); // tu import redirige con flashdata (como hoy)
    } else {
      // Validación mínima eliminar
      var delDate = $("#del_date").val();
      if (!delDate) { appAlert.error("Elegí la fecha a eliminar."); return; }

      var scope = $formDelete.find("input[name='scope']:checked").val();
      var batch = $("#batch_id_input").val().trim();
      if (scope === "csv_batch" && !batch) { appAlert.error("Pegá el batch_id."); return; }

      // Confirmación explícita
      var msg = (scope === "csv")
          ? "¿Eliminar TODOS los eventos IMPORTADOS POR CSV del " + delDate + "?"
          : (scope === "csv_batch")
              ? "¿Eliminar SOLO el lote CSV " + batch + " del " + delDate + "?"
              : "¿Eliminar TODOS los eventos del " + delDate + "?";
      if (!confirm(msg)) return;

      // En RISE estos forms suelen enviar por AJAX con appForm, pero si tu endpoint devuelve JSON,
      // podemos usar appForm para quedar en modal sin navegar:
      $formDelete.appForm({
        onSuccess: function(res){
          if (res.ok) {
            appAlert.success(res.message, {duration: 10000});
            // refrescá tu FullCalendar si está disponible
            if (window.calendar && typeof window.calendar.refetchEvents === "function") {
              window.calendar.refetchEvents();
            } else {
              location.reload();
            }
          } else {
            appAlert.error("No se pudo eliminar.");
          }
        }
      });

      $formDelete.submit();
    }
  });

  // Modo por defecto: Importar (como antes)
  setMode("import");
});
</script>
