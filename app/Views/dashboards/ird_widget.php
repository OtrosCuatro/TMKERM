<div class="card h377 bg-white">
    <div class="card-header">
        <i class="fa fa-hdd"></i>&nbsp; Uso de IRD
    </div>
    <div class="card-body d-flex justify-content-center align-items-center">
        <div style="position: relative; width: 260px; height: 260px;">
            <canvas id="ird-usage-chart"></canvas>
            <div id="ird-usage-label" 
                 style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);
                        font-size:22px;font-weight:bold;color:#333;">
                0 / 0
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctxIRD = document.getElementById('ird-usage-chart').getContext('2d');
    const labelIRD = document.getElementById('ird-usage-label');

    let irdChart = new Chart(ctxIRD, {
        type: 'doughnut',
        data: {
            labels: ['En uso', 'Disponibles'],
            datasets: [{
                data: [0, 0],
                backgroundColor: ['#dc3545', '#28a745'], // rojo = en uso, verde = libres
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            cutout: '70%',
            plugins: { legend: { display: false } }
        }
    });

    async function updateIRDChart() {
        try {
            const res = await fetch("<?= get_uri('ird/status') ?>"); // endpoint CI4
            const data = await res.json();

            const enUso = data.en_uso;
            const total = data.total;
            const libres = total - enUso;

            irdChart.data.datasets[0].data = [enUso, libres];
            irdChart.update();

            labelIRD.textContent = `${enUso} / ${total}`;
            // Cambiar color del texto según % uso
            const percent = enUso / total;
            if (percent > 0.8) {
                labelIRD.style.color = "#dc3545"; // rojo si >80%
            } else if (percent > 0.5) {
                labelIRD.style.color = "#ffc107"; // amarillo si >50%
            } else {
                labelIRD.style.color = "#28a745"; // verde si <50%
            }
        } catch (e) {
            console.error("Error al cargar datos IRD:", e);
        }
    }

    // actualizar cada 5s
    setInterval(updateIRDChart, 5000);
    updateIRDChart();
</script>
