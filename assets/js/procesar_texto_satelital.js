function procesarTextoSatelital(textoOriginal) {
    let resumen = "";

    const regexes = {
        satelite: /(SES\d{2}[A-Z]?|INTELSAT \d{1,2}|AMAZONAS \d{1,2}|IS\d{1,2}|EUTELSAT \d{1,2}[A-Z]?)/i,
        frecuencia: /(\d{4,5})(?:\s*[\.,]\s*\d+)?\s*Mhz/i,
        fec: /\d{1,2}\/\d{1,2}/,
        rolloff: /(?:roll[-\s]?off|ro)\s*[:\-]?\s*(0\.\d+)/i,
        ca: /(?:BISS\-?KEY|CA\-?ID|B\-?KEY)[\s:]*(\w+)/i,
        symbolrate: /(\d{2,5})\s*(?:ks|Ms|symbolrate)/i,
        mod: /(?:DVB[-\s]?S2|QPSK|8PSK|16APSK|32APSK)/i,
        pol: /(H|V|Horizontal|Vertical)/i,
        svc: /SVC\d{1,3}/i
    };

    const matches = {
        satelite: (textoOriginal.match(regexes.satelite) || [])[1] || "",
        frecuencia: (textoOriginal.match(regexes.frecuencia) || [])[1] || "",
        symbolrate: (textoOriginal.match(regexes.symbolrate) || [])[1] || "",
        svc: (textoOriginal.match(regexes.svc) || [])[0] || "",
        ca: (textoOriginal.match(regexes.ca) || [])[1] || ""
    };

    // Construir resumen
    resumen = [matches.satelite, matches.frecuencia, matches.symbolrate, matches.svc, matches.ca]
        .filter(Boolean)
        .join(" ")
        .trim();

    return resumen;
}