<div class="modal-body">
  <form id="resourceUsageForm">
    <div class="row">
      <div class="col-md-4">
        <label>Fecha y hora</label>
        <input type="datetime-local" name="datetime" class="form-control">
      </div>
      <div class="col-md-4">
        <label>Rango desde</label>
        <input type="date" name="date_from" class="form-control">
      </div>
      <div class="col-md-4">
        <label>Rango hasta</label>
        <input type="date" name="date_to" class="form-control">
      </div>
    </div>
    <div class="row mt-2">
      <div class="col-md-4">
        <label>Recurso</label>
        <select name="resource" class="form-control">
          <option value="">Todos</option>
          <option value="smd">SMD</option>
          <option value="conv">Conversor</option>
          <option value="ig">IG</option>
          <option value="asi_ip">ASI IP</option>
        </select>
      </div>
      <div class="col-md-4">
        <label>Cliente</label>
        <input type="text" name="client_id" class="form-control">
      </div>
      <div class="col-md-4">
        <label>Creador</label>
        <input type="text" name="created_by" class="form-control">
      </div>
    </div>
    <div class="mt-3 text-right">
      <button type="submit" class="btn btn-primary">Buscar</button>
    </div>
  </form>

  <div id="resourceResult" class="mt-3"></div>
</div>

<script>
function safe(txt) {
  if (!txt) return "-";
  return String(txt)
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;");
}

document.getElementById("resourceUsageForm").addEventListener("submit", function(e) {
  e.preventDefault();

  const formData = new FormData(this);

  fetch("<?php echo get_uri('events/get_resource_usage_data'); ?>", {
    method: "POST",
    body: formData
  })
  .then(r => r.json())
.then(resp => {
  if (!resp.success) {
    document.getElementById("resourceResult").innerHTML = "<div class='text-danger'>Error al traer datos</div>";
    return;
  }
  const d = resp.data;
  console.log("Respuesta d:", d);

  // Extraemos marcas ASI IP
  const marcas = d.asiip_marcas || {};
  
  document.getElementById("resourceResult").innerHTML = `
    <table class="table table-bordered table-dark">
      <tr><th>Total eventos activos</th><td>${d.total_eventos ?? 0}</td></tr>
      <tr>
        <th>ASI IP en uso</th>
        <td>
          ${d.total_asi_ip ?? 0}<br>
          <small>
            MediaKind: ${marcas["MediaKind"] ?? 0} |
            ATEME: ${marcas["ATEME"] ?? 0} |
            Nimbra/Kiloview: ${marcas["Nimbra/Kiloview"] ?? 0} |
            Otros: ${marcas["Otros"] ?? 0}
          </small>
        </td>
      </tr>
      <tr><th>Conversores en uso</th><td>${d.total_conv ?? 0}</td></tr>
      <tr><th>IG en uso</th><td>${d.total_ig ?? 0}</td></tr>
      <tr><th>SMD en uso</th><td>${d.total_smd ?? 0}</td></tr>
    </table>
  `;
})

  .catch(err => {
    console.error(err);
    document.getElementById("resourceResult").innerHTML = "<div class='text-danger'>Error de conexión</div>";
  });
});
</script>

<style>
/* Tablas oscuras dentro del modal */
#resourceResult table.custom-dark-table {
  width: 100%;
  background-color: #292929 !important;
  color: #f0f0f0 !important;
  border-collapse: collapse;
  border: 1px solid #444 !important;
  border-radius: 6px;
  overflow: hidden;
}
#resourceResult table.custom-dark-table th,
#resourceResult table.custom-dark-table td {
  padding: 8px 10px;
  border: 1px solid #444 !important;
  vertical-align: middle;
}
#resourceResult table.custom-dark-table th {
  background-color: #d5d5d5 !important;
  font-weight: 600;
}
#resourceResult table.custom-dark-table tr:hover td {
  background-color: #c5c5c5ff !important;
}
</style>
