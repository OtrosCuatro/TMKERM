function procesarTexto() {
    let texto = document.getElementById("description").value.trim();
    let textoLimpio = texto.replace(/[\t\n\r\[\],:"]+/g, " ").toUpperCase();
    let palabras = textoLimpio.split(" ");
    
    const satelites = {
        "IS-34": "IS34", "INT21": "IS21", "INT707": "IS707", "IS-11": "IS11", "IS-14": "IS14", "IS-1R": "IS1R",
        "TELSTAR12": "TelStar12", "TELSTAR14": "TelStar14", "SES6": "SES6", "SES10": "SES10", "SES14": "SES14",
        "ARSAT1": "AR1", "ARSAT2": "AR2", "EU8": "EU8", "EU113": "EU113", "EU117": "EU117", "NSS7": "NSS7",
        "NSS4": "NSS4", "NSS806": "NSS806", "AMAZONAS2": "Amzn2", "AMAZONAS3": "Amzn3", "HISPASAT1C": "Hisp1C",
        "HISPASAT1D": "Hisp1D", "HISPASAT1E": "Hisp1E", "HISPASAT6": "Hisp6", "GALAXY28": "G28"
    };
    
    let satelite = Object.keys(satelites).find(key => textoLimpio.includes(key)) || "No encontrado";
    satelite = satelites[satelite] || "No encontrado";
    
    let polDwn = textoLimpio.includes("VERTICAL") ? "V" : (textoLimpio.includes("HORIZONTAL") ? "H" : "");
    satelite += polDwn;
    
    let frecDwnMatch = textoLimpio.match(/(?:D\/L|DOWNLINK FREQ)\.?\s*(\d{4,5})/);
    let frecDwn = frecDwnMatch ? frecDwnMatch[1] : "";
    
    let srMatch = textoLimpio.match(/(?:SYMBOL RATE|SR|S\/R|L RATE)\s*(\d+[.,]?\d*)/);
    let sr = srMatch ? srMatch[1].replace(",", ".") : "";
    
    let modulacionMatch = textoLimpio.match(/(DVB-S2|DVB|8PSK|QPSK|16APSK)/g);
    let modulacion = "";
    if (modulacionMatch) {
        if (modulacionMatch.includes("DVB-S2") && modulacionMatch.includes("8PSK")) {
            modulacion = "DVB-S2 8PSK";
        } else {
            modulacion = modulacionMatch.join(" ");
        }
    }
    
    let bissMatch = textoLimpio.match(/(?:BISS CODE|BISS-1)\s*([A-F0-9]{4})\s*([A-F0-9]{4})\s*([A-F0-9]{4})/);
    let biss = bissMatch ? bissMatch.slice(1).join("") : "";
    
    if (biss.length === 12) {
        biss = biss.substring(4, 8) + biss.substring(8, 12) + "FEED59";
    }
    
    let resultado = `${satelite} ${frecDwn} ${sr} ${modulacion} Biss-1 ${biss}`.trim();
    
    let resultadoInput = document.getElementById("resultado");
    if (resultadoInput) resultadoInput.value = resultado;
}