<template>
    <div class="app">
        <h3>Pixel Canvas</h3>

        <div class="canvas-wrap">
            <canvas
                ref="canvasRef"
                :width="canvasSize"
                :height="canvasSize"
                tabindex="0"
                aria-label="Pixel canvas"
                @click="onCanvasClick"
                @mousemove="onCanvasMove"
                @keydown="onCanvasKeydown"
            ></canvas>

            <div v-if="hover.info" id="hover-tip" :style="hoverStyle" role="tooltip" aria-live="polite">
                <div><strong>[{{ hover.info.x }}, {{ hover.info.y }}]</strong></div>
                <div><span class="dot" :style="{ background: hover.info.color }"></span> {{ hover.info.color }}</div>
                <div v-if="hover.info.buyer">by {{ shortAddr(hover.info.buyer) }}</div>
            </div>
        </div>

        <div v-if="busy.show" id="spinner" aria-live="polite">{{ busy.message }}</div>

        <div class="toolbar" role="form" aria-label="Controls">
            <div class="field">
                <label for="x">X</label>
                <input id="x" type="number" min="0" max="499" v-model.number="form.x" @input="validate" />
            </div>
            <div class="field">
                <label for="y">Y</label>
                <input id="y" type="number" min="0" max="499" v-model.number="form.y" @input="validate" />
            </div>
            <div class="field">
                <label for="color">Color</label>
                <input id="color" type="color" v-model="form.color" @input="validate" />
            </div>
            <div class="field" style="grid-column: span 2;">
                <label for="tx">Transaction signature</label>
                <input id="tx" type="text" placeholder="Paste Solana transaction signature" v-model.trim="form.tx" @input="validate" />
            </div>
            <div class="field">
                <label for="zoom">Zoom</label>
                <div class="zoom-row">
                    <input id="zoom" type="range" min="1" max="10" step="1" v-model.number="zoom" />
                    <span id="zoom-label">{{ zoom }}x</span>
                </div>
            </div>
            <div class="field">
                <label>Preview</label>
                <div id="preview" :style="{ background: form.color }"></div>
            </div>
            <div class="field">
                <label>&nbsp;</label>
                <button class="btn" @click="connectWallet" :disabled="connecting">{{ connecting ? 'Connecting…' : 'Connect Wallet' }}</button>
            </div>
            <div class="field">
                <label>&nbsp;</label>
                <button class="btn primary" @click="buyPixel" :disabled="!canBuy || submitting">
                    {{ submitting ? 'Submitting…' : 'Buy Pixel' }}
                </button>
            </div>
        </div>

        <div id="instructions" aria-label="Instructions">
            <strong>How to claim a pixel:</strong>
            <ol>
                <li>Connect your wallet</li>
                <li>Select X/Y and color</li>
                <li>Paste Solana transaction signature</li>
                <li>Click Buy Pixel</li>
            </ol>
        </div>

        <div id="recent-activity" aria-label="Recent activity" v-if="recent.length">
            <strong>Recent claims:</strong>
            <ul>
                <li v-for="p in recent" :key="p.id || p.x + '-' + p.y">
                    [{{ p.x }},{{ p.y }}] {{ p.color }} <span v-if="p.buyer">by {{ shortAddr(p.buyer) }}</span>
                </li>
            </ul>
        </div>

        <div class="row">
            <div id="wallet" class="status">Wallet: {{ buyer ? shortAddr(buyer) : 'not connected' }}</div>
            <div class="status">Token: TROLLOWEEN • mint DG1S…pump</div>
        </div>

        <div id="toasts">
            <div
                v-for="t in toasts"
                :key="t.id"
                class="toast"
                :class="t.type"
                role="status"
                @click="dismissToast(t.id)"
            >
                {{ t.msg }}
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch, nextTick } from 'vue';
import axios from 'axios';

const SIZE = 500;

const canvasRef = ref(null);
let ctx = null;

const pixels = ref([]); // array of { x, y, color, buyer? }
const recent = ref([]);

const zoom = ref(1);
const canvasSize = computed(() => SIZE * zoom.value);

const buyer = ref(null);
const provider = ref(null);

const form = reactive({
    x: 0,
    y: 0,
    color: '#ff0000',
    tx: ''
});

const connecting = ref(false);
const submitting = ref(false);

const busy = reactive({ show: false, message: 'Loading...' });

const toasts = ref([]);
let toastSeq = 0;

const hover = reactive({
    info: null,
    x: 0,
    y: 0
});
const hoverStyle = computed(() => ({
    left: hover.x + 12 + 'px',
    top: hover.y + 12 + 'px'
}));

const canBuy = computed(() => {
    const xOk = Number.isInteger(form.x) && form.x >= 0 && form.x <= 499;
    const yOk = Number.isInteger(form.y) && form.y >= 0 && form.y <= 499;
    const colorOk = /^#[0-9A-Fa-f]{6}$/.test(form.color);
    const txOk = !!form.tx;
    const walletOk = !!buyer.value;
    return xOk && yOk && colorOk && txOk && walletOk;
});

function shortAddr(a) {
    if (!a) return '';
    return a.slice(0, 4) + '…' + a.slice(-4);
}

function showToast(msg, type = 'info', ms = 3500) {
    const id = ++toastSeq;
    toasts.value.push({ id, msg, type });
    setTimeout(() => {
        const i = toasts.value.findIndex(t => t.id === id);
        if (i >= 0) toasts.value.splice(i, 1);
    }, ms);
}
function dismissToast(id) {
    const i = toasts.value.findIndex(t => t.id === id);
    if (i >= 0) toasts.value.splice(i, 1);
}

function setBusy(flag, message = 'Loading...') {
    busy.show = flag;
    busy.message = message;
}

function validate() {
    /* computed canBuy reacts automatically */
}

function clampXY(x, y) {
    return {
        x: Math.max(0, Math.min(499, x)),
        y: Math.max(0, Math.min(499, y))
    };
}

function drawCanvas() {
    if (!ctx || !canvasRef.value) return;
    // Resize canvas for crisp zoomed pixels
    const c = canvasRef.value;
    // Clear and draw
    ctx.setTransform(1, 0, 0, 1, 0, 0);
    ctx.clearRect(0, 0, c.width, c.height);
    ctx.imageSmoothingEnabled = false;
    ctx.save();
    ctx.scale(zoom.value, zoom.value);
    for (const p of pixels.value) {
        ctx.fillStyle = p.color;
        ctx.fillRect(p.x, p.y, 1, 1);
    }
    ctx.restore();
}

async function loadCanvas() {
    try {
        setBusy(true, 'Loading canvas…');
        const { data } = await axios.get('/api/pixels');
        pixels.value = Array.isArray(data) ? data : [];
        await nextTick();
        drawCanvas();
    } catch (e) {
        console.error(e);
        showToast('Failed to load canvas', 'error');
    } finally {
        setBusy(false);
    }
}

async function loadRecent() {
    try {
        const { data } = await axios.get('/api/pixels/recent');
        recent.value = Array.isArray(data) ? data : [];
    } catch {
        // Endpoint may not exist; ignore silently
        recent.value = [];
    }
}

async function connectWallet() {
    provider.value = window.phantom?.solana || window.solana;
    if (!provider.value?.isPhantom) {
        showToast('Phantom not found. Opening install page…', 'info');
        window.open('https://phantom.app/download', '_blank', 'noopener');
        return;
    }
    try {
        connecting.value = true;
        const resp = await provider.value.connect();
        buyer.value = resp.publicKey.toString();
        showToast('Wallet connected', 'success');
    } catch {
        showToast('Wallet connection canceled', 'error');
    } finally {
        connecting.value = false;
    }
}

async function buyPixel() {
    if (!canBuy.value) {
        showToast('Fill all fields correctly', 'error');
        return;
    }
    provider.value = window.phantom?.solana || window.solana;
    if (!provider.value?.isPhantom) {
        showToast('Phantom wallet required', 'error');
        return;
    }
    try {
        submitting.value = true;
        if (!buyer.value) {
            await connectWallet();
            if (!buyer.value) return;
        }
        showToast('Submitting for verification…', 'info');
        const payload = {
            tx_signature: form.tx,
            x: form.x,
            y: form.y,
            color: form.color,
            buyer: buyer.value
        };
        const res = await axios.post('/api/pixel/claim', payload);
        if (res.status >= 200 && res.status < 300) {
            showToast('Pixel claimed', 'success');
            form.tx = '';
            await loadCanvas();
            await loadRecent();
        } else {
            showToast('Backend error', 'error');
        }
    } catch (e) {
        const msg = e?.response?.data?.message || e?.message || 'Unexpected error';
        showToast(msg, 'error');
    } finally {
        submitting.value = false;
    }
}

function eventToPixel(e) {
    const rect = canvasRef.value.getBoundingClientRect();
    const rx = Math.floor((e.clientX - rect.left) / zoom.value);
    const ry = Math.floor((e.clientY - rect.top) / zoom.value);
    const { x, y } = clampXY(rx, ry);
    return { x, y };
}

function onCanvasClick(e) {
    const { x, y } = eventToPixel(e);
    form.x = x;
    form.y = y;
    validate();
}

function onCanvasMove(e) {
    const { x, y } = eventToPixel(e);
    hover.x = e.clientX;
    hover.y = e.clientY;
    const found = pixels.value.find(p => p.x === x && p.y === y);
    hover.info = found ? { x, y, color: found.color, buyer: found.buyer } : { x, y, color: '#000000' };
}

function onCanvasKeydown(e) {
    if (e.key === 'ArrowUp') form.y = Math.max(0, form.y - 1);
    if (e.key === 'ArrowDown') form.y = Math.min(499, form.y + 1);
    if (e.key === 'ArrowLeft') form.x = Math.max(0, form.x - 1);
    if (e.key === 'ArrowRight') form.x = Math.min(499, form.x + 1);
    validate();
}

onMounted(async () => {
    const c = canvasRef.value;
    ctx = c.getContext('2d');
    provider.value = window.phantom?.solana || window.solana;
    provider.value?.on?.('connect', pk => {
        buyer.value = pk?.toString?.() || buyer.value;
    });
    await loadCanvas();
    await loadRecent();
});

watch(zoom, async () => {
    await nextTick();
    drawCanvas();
});

watch(pixels, () => {
    drawCanvas();
}, { deep: true });
</script>

<style scoped>
:root {
    --bg:#0b1220; --card:#0f172a; --muted:#94a3b8; --text:#e2e8f0;
    --primary:#3b82f6; --primary-600:#2563eb; --success:#10b981; --error:#ef4444; --border:#1f2937;
}
* { box-sizing: border-box; }
.app { width: 820px; max-width: 100%; margin: 0 auto; padding: 24px; color: var(--text); font: 14px/1.4 system-ui, Segoe UI, Roboto, Ubuntu, Cantarell, sans-serif; }
h3 { margin: 0 0 12px 0; }
.canvas-wrap { position: relative; display: inline-block; }
canvas { display: block; border: 1px solid var(--border); border-radius: 10px; background: #0a0f1c; }

#hover-tip {
    position: fixed;
    background: var(--card);
    color: var(--text);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 6px 8px;
    font-size: 12px;
    pointer-events: none;
    box-shadow: 0 10px 30px rgba(0,0,0,.3);
}
#hover-tip .dot {
    display: inline-block;
    width: 10px; height: 10px;
    border-radius: 50%;
    border: 1px solid var(--border);
    margin-right: 6px;
    vertical-align: -1px;
}

.toolbar {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 10px;
    margin-top: 12px;
}
.field { display: flex; flex-direction: column; gap: 6px; }
label { color: var(--muted); font-size: .85rem; }
input[type=number], input[type=text] {
    width: 100%; padding: 10px 12px; border: 1px solid var(--border);
    border-radius: 8px; background: var(--card); color: var(--text); outline: none;
}
input[type=color] {
    width: 100%; height: 38px; padding: 4px; border-radius: 8px; border: 1px solid var(--border); background: var(--card);
}
#preview {
    width: 38px; height: 38px; border-radius: 8px; border: 1px solid var(--border);
}
.zoom-row { display: flex; align-items: center; gap: 8px; }
.btn {
    padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border);
    background: var(--card); color: var(--text); cursor: pointer;
}
.btn.primary { background: var(--primary); border-color: var(--primary); color: #fff; }
.btn.primary:hover { background: var(--primary-600); }
.btn:disabled { opacity: .6; cursor: not-allowed; }

.row { display: flex; gap: 12px; align-items: center; justify-content: space-between; margin-top: 10px; }
.status { color: var(--muted); }

#toasts {
    position: fixed; top: 16px; right: 16px;
    display: flex; flex-direction: column; gap: 8px; z-index: 1000;
}
.toast {
    min-width: 240px; max-width: 420px; padding: 10px 12px; border-radius: 10px;
    background: var(--card); border: 1px solid var(--border);
    box-shadow: 0 10px 30px rgba(0,0,0,.3);
    transition: opacity .3s, transform .3s;
}
.toast.info { border-left: 4px solid var(--primary); }
.toast.success { border-left: 4px solid var(--success); }
.toast.error { border-left: 4px solid var(--error); }

#spinner {
    position: fixed; top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    background: rgba(0,0,0,.7); color: #fff;
    padding: 20px; border-radius: 10px; z-index: 2000;
}

#instructions { margin-top: 12px; }
#recent-activity { margin-top: 12px; }

@media (max-width: 600px) {
    .app { width: 100%; padding: 8px; }
    .toolbar { grid-template-columns: 1fr; }
}
</style>
