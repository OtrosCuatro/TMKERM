function procesarTextoSatelital() {
    const texto = document.getElementById("sat_texto").value;
    let resumen = [];

    // Buscar frecuencia TX o RX
    const freqMatch = texto.match(/(?:TX|RX):?\s*(\d{4,6})\s*[VH]/i);
    if (freqMatch) resumen.push(freqMatch[1]);

    // Buscar symbol rate
    const srMatch = texto.match(/S\s*Rate\s*(\d{1,2}(?:[.,]\d{1,2})?)/i);
    if (srMatch) resumen.push(srMatch[1].replace(",", "."));

    // Buscar DVB-S2
    if (/DVB-?S2/i.test(texto)) resumen.push("DVB-S2");

    // Buscar codificación
    if (/BISS/i.test(texto)) resumen.push("Biss-1");

    // Buscar código BISS
    const bissMatch = texto.match(/BISS\s*Code:?\s*([\dA-F\s]+)/i);
    if (bissMatch) {
        const code = bissMatch[1].replace(/\s+/g, "").replace(".", "");
        resumen.push(code);
    }

    // Mostrar resultado
    document.getElementById("sat_resultado").textContent = resumen.join(" ");
}
