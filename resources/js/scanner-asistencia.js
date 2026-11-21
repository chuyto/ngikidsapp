import { Html5Qrcode, Html5QrcodeSupportedFormats } from 'html5-qrcode';

// Escaner de credenciales para Control de Asistencia.
// Lee QR/barras con la camara y pone el UID en el buscador, igual que
// si se hubiera tecleado (nunca abre links).
const MODAL_ID = 'scannerModal';
const VIEWPORT_ID = 'scannerViewport';
const STATUS_ID = 'scannerStatus';
const TORCH_BTN_ID = 'scannerTorch';

let lector = null;
let leyendo = false;
let linterna = false;

function $id(id) {
    return document.getElementById(id);
}

// Extrae el UID de 6 caracteres de lo leido: acepta el codigo plano
// ("ABC123") o URLs viejas con ?codigo= (".../asistencias/create?codigo=ABC123").
function extraerUid(texto) {
    const limpio = String(texto || '').trim();
    if (/^[A-Za-z0-9]{6}$/.test(limpio)) {
        return limpio.toUpperCase();
    }
    try {
        const codigo = new URL(limpio).searchParams.get('codigo');
        if (codigo && /^[A-Za-z0-9]{6}$/.test(codigo.trim())) {
            return codigo.trim().toUpperCase();
        }
    } catch (e) {
        // No es URL: se ignora y se sigue escuchando.
    }

    return null;
}

function avisar(mensaje, esError = false) {
    const el = $id(STATUS_ID);
    if (!el) {
        return;
    }
    el.textContent = mensaje;
    el.classList.toggle('text-brand-600', esError);
    el.classList.toggle('text-slate-500', !esError);
}

function alLeer(decodedText) {
    const uid = extraerUid(decodedText);
    if (!uid) {
        avisar('Código no válido, apunta de nuevo a la credencial.', true);

        return;
    }

    if (navigator.vibrate) {
        navigator.vibrate(80);
    }
    avisar('¡Leído! Buscando folio ' + uid + '…');
    cerrar(true);

    const input = $id('uuid_short');
    const buscar = $id('searchButton');
    if (input && buscar) {
        input.value = uid;
        buscar.click();
    }
}

function alError() {
    // Se ignora: son los frames sin codigo (ruido normal del escaneo).
}

async function abrir() {
    const modal = $id(MODAL_ID);
    if (!modal || leyendo) {
        return;
    }
    modal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
    avisar('Apunta la cámara al QR de la credencial…');

    try {
        lector = new Html5Qrcode(VIEWPORT_ID, {
            formatsToSupport: [
                Html5QrcodeSupportedFormats.QR_CODE,
                Html5QrcodeSupportedFormats.CODE_128,
            ],
            verbose: false,
        });
        leyendo = true;
        await lector.start(
            { facingMode: 'environment' },
            { fps: 10, qrbox: { width: 250, height: 250 }, aspectRatio: 1.0 },
            alLeer,
            alError
        );
        mostrarBotonLinterna();
    } catch (e) {
        leyendo = false;
        lector = null;
        const nombre = e && e.name ? e.name : '';
        if (nombre === 'NotAllowedError') {
            avisar('Permiso de cámara denegado. Actívalo en el navegador o escribe el folio a mano.', true);
        } else if (nombre === 'NotFoundError' || nombre === 'OverconstrainedError') {
            avisar('No se encontró cámara trasera. Escribe el folio a mano.', true);
        } else {
            avisar('No se pudo abrir la cámara. Escribe el folio a mano.', true);
        }
    }
}

async function cerrar(silencioso = false) {
    const modal = $id(MODAL_ID);
    if (lector && leyendo) {
        try {
            await lector.stop();
        } catch (e) {
            // Ya detenido: no pasa nada.
        }
        try {
            lector.clear();
        } catch (e) {
            // Sin vista que limpiar.
        }
    }
    lector = null;
    leyendo = false;
    linterna = false;
    if (modal) {
        modal.classList.add('hidden');
    }
    document.body.classList.remove('overflow-hidden');
    if (!silencioso) {
        const input = $id('uuid_short');
        if (input) {
            input.focus();
        }
    }
}

function mostrarBotonLinterna() {
    const btn = $id(TORCH_BTN_ID);
    if (!btn || !lector) {
        return;
    }
    let tiene = false;
    try {
        const caps = lector.getRunningTrackCapabilities();
        tiene = !!(caps && 'torch' in caps);
    } catch (e) {
        tiene = false;
    }
    btn.classList.toggle('hidden', !tiene);
}

async function alternarLinterna() {
    if (!lector || !leyendo) {
        return;
    }
    linterna = !linterna;
    try {
        await lector.applyVideoConstraints({ advanced: [{ torch: linterna }] });
        $id(TORCH_BTN_ID)?.classList.toggle('!bg-amber-400', linterna);
    } catch (e) {
        linterna = !linterna;
    }
}

window.AsistenciaScanner = { abrir, cerrar, alternarLinterna };

document.addEventListener('DOMContentLoaded', () => {
    $id('scanButton')?.addEventListener('click', abrir);
    $id('scannerClose')?.addEventListener('click', () => cerrar());
    $id('scannerCancel')?.addEventListener('click', () => cerrar());
    $id(TORCH_BTN_ID)?.addEventListener('click', alternarLinterna);
    $id(MODAL_ID)?.addEventListener('click', (e) => {
        if (e.target && e.target.id === MODAL_ID) {
            cerrar();
        }
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && leyendo) {
            cerrar();
        }
    });
});
