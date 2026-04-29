<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { ref, onMounted, onBeforeUnmount, nextTick } from 'vue'
import axios from 'axios'
import 'leaflet/dist/leaflet.css'
import L from 'leaflet'

// Fix Leaflet default marker icons broken by Vite bundling
delete L.Icon.Default.prototype._getIconUrl
L.Icon.Default.mergeOptions({
    iconRetinaUrl: new URL('leaflet/dist/images/marker-icon-2x.png', import.meta.url).href,
    iconUrl:       new URL('leaflet/dist/images/marker-icon.png',    import.meta.url).href,
    shadowUrl:     new URL('leaflet/dist/images/marker-shadow.png',  import.meta.url).href,
})

const props = defineProps({
    currentZone: { type: Object, default: null },
    sessionId:   { type: String, default: '' },
})

// ── Oblast data ───────────────────────────────────────────────────────────────
const OBLASTS = [
    { name: 'Акмолинская',            west: 68.9, south: 50.5, east: 75.5, north: 54.0 },
    { name: 'Актюбинская',            west: 55.0, south: 46.0, east: 67.5, north: 51.5 },
    { name: 'Алматинская',            west: 75.0, south: 42.5, east: 80.5, north: 45.5 },
    { name: 'Атырауская',             west: 49.0, south: 45.5, east: 56.5, north: 48.5 },
    { name: 'Восточно-Казахстанская', west: 79.5, south: 46.5, east: 87.5, north: 50.5 },
    { name: 'Жамбылская',             west: 69.5, south: 42.0, east: 77.0, north: 45.5 },
    { name: 'Западно-Казахстанская',  west: 49.5, south: 49.5, east: 56.5, north: 52.5 },
    { name: 'Карагандинская',         west: 68.0, south: 46.0, east: 78.0, north: 50.5 },
    { name: 'Костанайская',           west: 61.0, south: 51.0, east: 68.5, north: 54.5 },
    { name: 'Кызылординская',         west: 60.0, south: 43.5, east: 69.5, north: 47.5 },
    { name: 'Мангыстауская',          west: 50.0, south: 41.5, east: 57.0, north: 46.0 },
    { name: 'Павлодарская',           west: 73.5, south: 50.5, east: 79.5, north: 54.5 },
    { name: 'Северо-Казахстанская',   west: 67.0, south: 53.0, east: 73.5, north: 55.5 },
    { name: 'Туркестанская',          west: 66.0, south: 41.0, east: 72.5, north: 44.5 },
    { name: 'Улытауская',             west: 60.0, south: 46.5, east: 68.0, north: 50.5 },
    { name: 'г. Алматы',              west: 76.6, south: 43.0, east: 77.3, north: 43.5 },
    { name: 'г. Астана',              west: 71.2, south: 50.9, east: 71.8, north: 51.3 },
]

// ── Reactive state ────────────────────────────────────────────────────────────
const mapEl          = ref(null)
const selectedOblast = ref(null)
const hotspots       = ref([])
const loading        = ref(false)
const errorMsg       = ref(null)

const activeTab = ref('map')

function switchTab(tab) {
    activeTab.value = tab
    if (tab === 'crop') fetchSessions()
}

// ── Crop Chat ─────────────────────────────────────────────────────────────────
const cropMessages      = ref([])
const cropInput         = ref('')
const cropImage         = ref(null)
const cropMediaType     = ref('image/jpeg')
const cropPreview       = ref(null)
const cropLoading       = ref(false)
const cropFileRef       = ref(null)
const cropScrollEl      = ref(null)

// ── Sessions ──────────────────────────────────────────────────────────────────
const cropSessions      = ref([])
const currentSessionId  = ref(null)
const sessionsLoading   = ref(false)

async function fetchSessions() {
    sessionsLoading.value = true
    try {
        const { data } = await axios.get('/crop/sessions')
        cropSessions.value = data
    } finally {
        sessionsLoading.value = false
    }
}

async function loadSession(id) {
    if (currentSessionId.value === id) return
    currentSessionId.value = id
    cropMessages.value     = []
    clearCropImage()
    cropLoading.value = true
    try {
        const { data } = await axios.get(`/crop/sessions/${id}`)
        cropMessages.value = data.messages
        await nextTick()
        scrollCropToBottom()
    } finally {
        cropLoading.value = false
    }
}

function startNewChat() {
    currentSessionId.value = null
    cropMessages.value     = []
    cropInput.value        = ''
    clearCropImage()
}

async function deleteCropSession(id, e) {
    e.stopPropagation()
    if (!confirm('Удалить этот чат?')) return
    await axios.delete(`/crop/sessions/${id}`)
    cropSessions.value = cropSessions.value.filter(s => s.id !== id)
    if (currentSessionId.value === id) startNewChat()
}

function upsertSession(session) {
    const idx = cropSessions.value.findIndex(s => s.id === session.id)
    if (idx === -1) {
        cropSessions.value.unshift(session)
    } else {
        cropSessions.value.splice(idx, 1)
        cropSessions.value.unshift(session)
    }
}

function formatSessionDate(dateStr) {
    const d = new Date(dateStr)
    const now = new Date()
    if (d.toDateString() === now.toDateString()) {
        return d.toLocaleTimeString('ru', { hour: '2-digit', minute: '2-digit' })
    }
    return d.toLocaleDateString('ru', { day: '2-digit', month: '2-digit' })
}

function onCropFile(e) {
    const file = e.target.files[0]
    if (!file) return
    cropMediaType.value = file.type || 'image/jpeg'
    const reader = new FileReader()
    reader.onload = (ev) => {
        cropPreview.value = ev.target.result
        cropImage.value   = ev.target.result.split(',')[1]
    }
    reader.readAsDataURL(file)
}

function clearCropImage() {
    cropImage.value   = null
    cropPreview.value = null
    if (cropFileRef.value) cropFileRef.value.value = ''
}

async function sendCrop() {
    const text  = cropInput.value.trim()
    const img   = cropImage.value
    const media = cropMediaType.value
    if (!text && !img) return

    cropMessages.value.push({ role: 'user', text, preview: cropPreview.value })
    cropInput.value = ''
    clearCropImage()
    await nextTick()
    scrollCropToBottom()

    cropLoading.value = true
    try {
        const { data } = await axios.post('/n8n/crop', {
            message:   text,
            image:     img,
            mediaType: media,
            sessionId: currentSessionId.value,
        })

        if (data.sessionId && data.sessionId !== currentSessionId.value) {
            currentSessionId.value = data.sessionId
            upsertSession({ id: data.sessionId, title: data.sessionTitle, updated_at: new Date().toISOString() })
        } else if (data.sessionId) {
            upsertSession({ id: data.sessionId, title: data.sessionTitle, updated_at: new Date().toISOString() })
        }

        cropMessages.value.push({ role: 'ai', text: data.response ?? data, preview: null })
    } catch {
        cropMessages.value.push({ role: 'ai', text: 'Ошибка подключения к сервису анализа. Попробуйте снова.', preview: null })
    } finally {
        cropLoading.value = false
        await nextTick()
        scrollCropToBottom()
    }
}

function scrollCropToBottom() {
    if (cropScrollEl.value) cropScrollEl.value.scrollTop = cropScrollEl.value.scrollHeight
}

function onCropKeydown(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault()
        sendCrop()
    }
}

// ── Camera ────────────────────────────────────────────────────────────────────
const cameraOpen  = ref(false)
const videoEl     = ref(null)
const canvasEl    = ref(null)
let   cameraStream = null

async function openCamera() {
    cameraOpen.value = true
    await nextTick()
    try {
        cameraStream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: { ideal: 'environment' } },
            audio: false,
        })
        videoEl.value.srcObject = cameraStream
        await videoEl.value.play()
    } catch {
        cameraOpen.value = false
        alert('Не удалось получить доступ к камере')
    }
}

function closeCamera() {
    cameraStream?.getTracks().forEach(t => t.stop())
    cameraStream = null
    cameraOpen.value = false
}

function capturePhoto() {
    const video  = videoEl.value
    const canvas = canvasEl.value
    canvas.width  = video.videoWidth
    canvas.height = video.videoHeight
    canvas.getContext('2d').drawImage(video, 0, 0)
    const dataUrl = canvas.toDataURL('image/jpeg', 0.92)
    cropPreview.value   = dataUrl
    cropImage.value     = dataUrl.split(',')[1]
    cropMediaType.value = 'image/jpeg'
    closeCamera()
}

// ── Leaflet internals (non-reactive) ─────────────────────────────────────────
let map             = null
let rectLayers      = {}
let hotspotLayer    = null

// ── Styles ───────────────────────────────────────────────────────────────────
const normalStyle = () => ({ color: '#e85c00', weight: 1.5, fillColor: '#e85c00', fillOpacity: 0.08 })
const activeStyle = () => ({ color: '#ff4500', weight: 2.5, fillColor: '#ff4500', fillOpacity: 0.22 })

function oblastBounds(o) {
    return [[o.south, o.west], [o.north, o.east]]
}

// ── Map init ──────────────────────────────────────────────────────────────────
function initMap() {
    map = L.map(mapEl.value, { center: [48.0, 67.0], zoom: 5 })

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 18,
    }).addTo(map)

    hotspotLayer = L.layerGroup().addTo(map)

    OBLASTS.forEach(oblast => {
        const rect = L.rectangle(oblastBounds(oblast), normalStyle())
            .addTo(map)
            .bindTooltip(oblast.name, { sticky: true })

        rect.on('click', () => selectOblast(oblast))
        rectLayers[oblast.name] = rect
    })

    if (props.currentZone) {
        const saved = OBLASTS.find(o => o.name === props.currentZone.oblast_name)
        if (saved) restoreSelection(saved)
    }
}

// ── Selection ─────────────────────────────────────────────────────────────────
function selectOblast(oblast) {
    if (selectedOblast.value) {
        rectLayers[selectedOblast.value.name]?.setStyle(normalStyle())
    }
    selectedOblast.value = oblast
    rectLayers[oblast.name]?.setStyle(activeStyle())
    map.fitBounds(oblastBounds(oblast), { padding: [30, 30] })
    saveAndFetch(oblast)
}

async function saveAndFetch(oblast) {
    loading.value  = true
    errorMsg.value = null
    hotspots.value = []
    hotspotLayer.clearLayers()

    try {
        const { data } = await axios.patch('/zone', {
            oblast_name: oblast.name,
            bbox_west:   oblast.west,
            bbox_south:  oblast.south,
            bbox_east:   oblast.east,
            bbox_north:  oblast.north,
        })
        hotspots.value = data.hotspots
        renderHotspots(data.hotspots)
    } catch {
        errorMsg.value = 'Ошибка получения данных о пожарах.'
    } finally {
        loading.value = false
    }
}

async function restoreSelection(oblast) {
    selectedOblast.value = oblast
    rectLayers[oblast.name]?.setStyle(activeStyle())
    loading.value = true
    try {
        const { data } = await axios.get('/zone/fires')
        hotspots.value = data.hotspots
        renderHotspots(data.hotspots)
    } catch { /* silent */ } finally {
        loading.value = false
    }
}

// ── Hotspot markers ───────────────────────────────────────────────────────────
function renderHotspots(spots) {
    hotspotLayer.clearLayers()
    spots.forEach(spot => {
        L.circleMarker([spot.lat, spot.lon], {
            radius: 6, color: '#cc0000', fillColor: '#ff2200', fillOpacity: 0.85, weight: 1,
        })
        .bindPopup(
            `<b>Очаг возгорания</b><br>` +
            `Яркость: ${spot.brightness} K<br>` +
            `FRP: ${spot.frp} МВт<br>` +
            `Уверенность: ${spot.confidence}<br>` +
            `Период: ${spot.daynight === 'D' ? 'День' : 'Ночь'}`
        )
        .addTo(hotspotLayer)
    })
}

// ── Lifecycle ─────────────────────────────────────────────────────────────────
onMounted(initMap)
onBeforeUnmount(() => { map?.remove(); closeCamera() })

const logout = () => router.post('/logout')
</script>

<template>
    <AppLayout>
        <Head title="AgriFireShield" />

        <!-- Header -->
        <header class="afs-header">
            <div class="afs-header__inner">
                <Link href="/dashboard" class="afs-logo">
                    <span class="afs-logo__icon">🔥</span>
                    <span class="afs-logo__text">AgriFireShield</span>
                </Link>
                <nav class="afs-nav">
                    <Link href="/dashboard" class="afs-nav__btn">Главная</Link>
                    <Link href="/profile"   class="afs-nav__btn">Профиль</Link>
                    <button class="afs-nav__logout" @click="logout">Выйти</button>
                </nav>
            </div>
        </header>

        <!-- Error banner -->
        <div v-if="errorMsg" class="afs-error-banner">{{ errorMsg }}</div>

        <!-- Tabs -->
        <div class="afs-tabs">
            <button
                class="afs-tab"
                :class="{ 'afs-tab--active': activeTab === 'map' }"
                @click="switchTab('map')"
            >
                Карта пожаров
            </button>
            <button
                class="afs-tab"
                :class="{ 'afs-tab--active': activeTab === 'crop' }"
                @click="switchTab('crop')"
            >
                ИИ Помощник агроному
            </button>
        </div>

        <!-- Workspace -->
        <div class="afs-workspace">

            <!-- Sidebar -->
            <aside class="afs-sidebar">
                <div class="afs-sidebar__header">
                    <span class="afs-sidebar__title">Регионы Казахстана</span>
                    <span v-if="hotspots.length" class="afs-fire-badge">🔥 {{ hotspots.length }}</span>
                </div>

                <ul class="afs-oblast-list">
                    <li
                        v-for="oblast in OBLASTS"
                        :key="oblast.name"
                        class="afs-oblast-item"
                        :class="{ 'afs-oblast-item--active': selectedOblast?.name === oblast.name }"
                        @click="selectOblast(oblast)"
                    >
                        <span class="afs-oblast-item__name">{{ oblast.name }}</span>
                        <span
                            v-if="selectedOblast?.name === oblast.name && hotspots.length"
                            class="afs-oblast-item__count"
                        >
                            {{ hotspots.length }}
                        </span>
                    </li>
                </ul>
            </aside>

            <!-- Map panel -->
            <div v-show="activeTab === 'map'" class="afs-map-panel">
                <div ref="mapEl" class="afs-map"></div>

                <!-- Loading overlay -->
                <div v-if="loading" class="afs-map-loading">
                    <div class="afs-spinner"></div>
                    <span>Загрузка данных NASA FIRMS...</span>
                </div>

                <!-- Summary bar -->
                <div v-if="selectedOblast && !loading" class="afs-summary-bar">
                    <span class="afs-summary-bar__oblast">{{ selectedOblast.name }}</span>
                    <span v-if="hotspots.length" class="afs-summary-bar__count">
                        Обнаружено очагов: <strong>{{ hotspots.length }}</strong>
                    </span>
                    <span v-else class="afs-summary-bar__none">
                        Активных пожаров не обнаружено
                    </span>
                </div>

                <!-- Placeholder when nothing selected -->
                <div v-if="!selectedOblast && !loading" class="afs-map-hint">
                    Выберите регион на карте или в списке слева
                </div>
            </div>

            <!-- Crop Chat panel -->
            <div v-show="activeTab === 'crop'" class="afs-crop-panel">

                <!-- Sessions sidebar -->
                <div class="afs-sessions-sidebar">
                    <div class="afs-sessions-header">
                        <span class="afs-sessions-title">История чатов</span>
                        <button class="afs-sessions-new" @click="startNewChat" title="Новый чат">+</button>
                    </div>
                    <div class="afs-sessions-list">
                        <div v-if="sessionsLoading" class="afs-sessions-spinner">
                            <div class="afs-spinner"></div>
                        </div>
                        <div v-else-if="cropSessions.length === 0" class="afs-sessions-empty">
                            Чатов пока нет
                        </div>
                        <div
                            v-for="s in cropSessions"
                            :key="s.id"
                            class="afs-session-item"
                            :class="{ 'afs-session-item--active': s.id === currentSessionId }"
                            @click="loadSession(s.id)"
                        >
                            <div class="afs-session-item__body">
                                <span class="afs-session-item__title">{{ s.title }}</span>
                                <span class="afs-session-item__date">{{ formatSessionDate(s.updated_at) }}</span>
                            </div>
                            <button class="afs-session-item__del" @click="deleteCropSession(s.id, $event)" title="Удалить">✕</button>
                        </div>
                    </div>
                </div>

                <!-- Main chat area -->
                <div class="afs-crop-main">

                <!-- Messages -->
                <div ref="cropScrollEl" class="afs-crop-messages">
                    <div v-if="cropMessages.length === 0" class="afs-crop-empty">
                        <div class="afs-crop-empty__icon">🌾</div>
                        <div class="afs-crop-empty__title">ИИ Помощник агроному</div>
                        <div class="afs-crop-empty__hint">
                            Загрузите фото урожая или задайте вопрос —<br>
                            ИИ определит состояние и даст рекомендации
                        </div>
                    </div>

                    <div
                        v-for="(msg, idx) in cropMessages"
                        :key="idx"
                        class="afs-msg"
                        :class="msg.role === 'user' ? 'afs-msg--user' : 'afs-msg--ai'"
                    >
                        <div class="afs-msg__avatar">
                            {{ msg.role === 'user' ? '👤' : '🌿' }}
                        </div>
                        <div class="afs-msg__bubble">
                            <img v-if="msg.preview" :src="msg.preview" class="afs-msg__image" alt="crop photo" />
                            <span v-if="msg.text" class="afs-msg__text">{{ msg.text }}</span>
                        </div>
                    </div>

                    <div v-if="cropLoading" class="afs-msg afs-msg--ai">
                        <div class="afs-msg__avatar">🌿</div>
                        <div class="afs-msg__bubble afs-msg__bubble--typing">
                            <span class="afs-typing-dot"></span>
                            <span class="afs-typing-dot"></span>
                            <span class="afs-typing-dot"></span>
                        </div>
                    </div>
                </div>

                <!-- Image preview bar -->
                <div v-if="cropPreview" class="afs-crop-preview-bar">
                    <img :src="cropPreview" class="afs-crop-preview-img" alt="preview" />
                    <button class="afs-crop-preview-remove" @click="clearCropImage">✕</button>
                </div>

                <!-- Input -->
                <div class="afs-crop-input-row">
                    <input ref="cropFileRef" type="file" accept="image/*" class="afs-crop-file-hidden" @change="onCropFile" />
                    <button class="afs-crop-attach-btn" @click="cropFileRef.click()" title="Прикрепить фото из галереи">
                        📎
                    </button>
                    <button class="afs-crop-attach-btn" @click="openCamera" title="Сфотографировать">
                        📷
                    </button>
                    <textarea
                        v-model="cropInput"
                        class="afs-crop-textarea"
                        placeholder="Опишите проблему или задайте вопрос... (Enter — отправить)"
                        rows="1"
                        @keydown="onCropKeydown"
                    ></textarea>
                    <button
                        class="afs-crop-send-btn"
                        :disabled="cropLoading || (!cropInput.trim() && !cropImage)"
                        @click="sendCrop"
                    >
                        Отправить
                    </button>
                </div>
                </div><!-- /.afs-crop-main -->
            </div><!-- /.afs-crop-panel -->
        </div>

        <!-- Camera modal -->
        <Teleport to="body">
            <div v-if="cameraOpen" class="afs-camera-overlay" @click.self="closeCamera">
                <div class="afs-camera-modal">
                    <div class="afs-camera-header">
                        <span class="afs-camera-title">Сфотографировать урожай</span>
                        <button class="afs-camera-close" @click="closeCamera">✕</button>
                    </div>
                    <video ref="videoEl" class="afs-camera-video" autoplay playsinline muted></video>
                    <canvas ref="canvasEl" style="display:none"></canvas>
                    <div class="afs-camera-footer">
                        <button class="afs-camera-capture-btn" @click="capturePhoto">
                            <span class="afs-camera-capture-ring"></span>
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<style scoped>
/* ── Header ──────────────────────────────────────────────────────────────── */
.afs-header {
    background: linear-gradient(135deg, #1a1a1a 0%, #2d1a00 100%);
    border-bottom: 3px solid #e85c00;
    box-shadow: 0 2px 12px rgba(232, 92, 0, 0.3);
    position: sticky;
    top: 0;
    z-index: 1000;
}
.afs-header__inner {
    max-width: 100%;
    padding: 0 24px;
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.afs-logo {
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    user-select: none;
}
.afs-logo__icon {
    font-size: 28px;
    filter: drop-shadow(0 0 6px rgba(255, 120, 0, 0.8));
}
.afs-logo__text {
    font-size: 20px;
    font-weight: 700;
    color: #ffffff;
    letter-spacing: 0.5px;
}
.afs-nav { display: flex; align-items: center; gap: 8px; }
.afs-nav__btn {
    display: inline-flex;
    align-items: center;
    padding: 8px 18px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    color: #e0d6cc;
    text-decoration: none;
    border: 1px solid transparent;
    transition: background 0.18s, color 0.18s;
}
.afs-nav__btn:hover,
.afs-nav__btn.router-link-active {
    background: rgba(232, 92, 0, 0.18);
    color: #fff;
    border-color: rgba(232, 92, 0, 0.4);
}
.afs-nav__logout {
    display: inline-flex;
    align-items: center;
    padding: 8px 18px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    color: #e0d6cc;
    background: transparent;
    border: 1px solid rgba(255,255,255,0.2);
    cursor: pointer;
    transition: background 0.18s, color 0.18s;
}
.afs-nav__logout:hover {
    background: rgba(255, 60, 0, 0.2);
    color: #fff;
    border-color: rgba(255, 60, 0, 0.5);
}

/* ── Error banner ─────────────────────────────────────────────────────────── */
.afs-error-banner {
    background: #3d0a00;
    border-bottom: 2px solid #cc2200;
    color: #ff8888;
    padding: 10px 20px;
    font-size: 13px;
    font-weight: 500;
}

/* ── Workspace ────────────────────────────────────────────────────────────── */
.afs-workspace {
    display: flex;
    height: calc(100vh - 64px - 49px);
    overflow: hidden;
}

/* ── Sidebar ──────────────────────────────────────────────────────────────── */
.afs-sidebar {
    width: 260px;
    min-width: 220px;
    background: #1a1a1a;
    border-right: 2px solid #2d1a00;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.afs-sidebar__header {
    padding: 14px 18px;
    background: #2d1a00;
    border-bottom: 1px solid #3d2400;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
}
.afs-sidebar__title {
    color: #fff;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}
.afs-fire-badge {
    background: #e85c00;
    color: #fff;
    font-size: 12px;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 20px;
}
.afs-oblast-list {
    list-style: none;
    padding: 6px 0;
    margin: 0;
    overflow-y: auto;
    flex: 1;
}
.afs-oblast-list::-webkit-scrollbar { width: 4px; }
.afs-oblast-list::-webkit-scrollbar-track { background: #111; }
.afs-oblast-list::-webkit-scrollbar-thumb { background: #3d2400; border-radius: 2px; }
.afs-oblast-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 18px;
    cursor: pointer;
    border-left: 3px solid transparent;
    transition: background 0.15s, border-color 0.15s;
}
.afs-oblast-item:hover {
    background: rgba(232, 92, 0, 0.12);
    border-left-color: rgba(232, 92, 0, 0.5);
}
.afs-oblast-item--active {
    background: rgba(232, 92, 0, 0.2);
    border-left-color: #e85c00;
}
.afs-oblast-item__name {
    color: #c8bfb5;
    font-size: 13px;
    font-weight: 500;
}
.afs-oblast-item--active .afs-oblast-item__name { color: #fff; }
.afs-oblast-item__count {
    background: #cc2200;
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 12px;
}

/* ── Map panel ────────────────────────────────────────────────────────────── */
.afs-map-panel {
    flex: 1;
    position: relative;
    display: flex;
    flex-direction: column;
    min-width: 0;
}
.afs-map {
    flex: 1;
    min-height: 0;
    z-index: 1;
}

/* ── Loading overlay ──────────────────────────────────────────────────────── */
.afs-map-loading {
    position: absolute;
    inset: 0;
    background: rgba(26, 26, 26, 0.6);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    z-index: 500;
    color: #fff;
    font-size: 14px;
}
.afs-spinner {
    width: 36px;
    height: 36px;
    border: 3px solid rgba(232, 92, 0, 0.3);
    border-top-color: #e85c00;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ── Summary bar ──────────────────────────────────────────────────────────── */
.afs-summary-bar {
    background: #2d1a00;
    border-top: 2px solid #e85c00;
    padding: 12px 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    flex-shrink: 0;
    z-index: 2;
}
.afs-summary-bar__oblast { color: #ff8c00; font-weight: 700; font-size: 14px; }
.afs-summary-bar__count  { color: #e0d6cc; font-size: 13px; }
.afs-summary-bar__count strong { color: #ff4500; font-size: 15px; }
.afs-summary-bar__none   { color: #777; font-size: 13px; }

/* ── Tabs ─────────────────────────────────────────────────────────────────── */
.afs-tabs {
    display: flex;
    gap: 4px;
    padding: 8px 16px;
    background: #111;
    border-bottom: 1px solid #2d1a00;
    flex-shrink: 0;
}
.afs-tab {
    padding: 7px 20px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    color: #888;
    background: transparent;
    border: 1px solid transparent;
    cursor: pointer;
    transition: background 0.18s, color 0.18s, border-color 0.18s;
}
.afs-tab:hover {
    background: rgba(232, 92, 0, 0.1);
    color: #e0d6cc;
    border-color: rgba(232, 92, 0, 0.3);
}
.afs-tab--active {
    background: rgba(232, 92, 0, 0.2);
    color: #fff;
    border-color: #e85c00;
}

/* ── Map hint ─────────────────────────────────────────────────────────────── */
.afs-map-hint {
    position: absolute;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(26, 26, 26, 0.82);
    color: #c8bfb5;
    font-size: 13px;
    padding: 10px 20px;
    border-radius: 20px;
    border: 1px solid #3d2400;
    pointer-events: none;
    z-index: 10;
    white-space: nowrap;
}

/* ── Crop Chat panel ──────────────────────────────────────────────────────── */
.afs-crop-panel {
    flex: 1;
    display: flex;
    flex-direction: row;
    background: #141414;
    min-width: 0;
    overflow: hidden;
}

/* ── Sessions sidebar ────────────────────────────────────────────────────── */
.afs-sessions-sidebar {
    width: 220px;
    min-width: 180px;
    flex-shrink: 0;
    background: #141414;
    border-right: 1px solid #2d1a00;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.afs-sessions-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 14px;
    background: #1a1a1a;
    border-bottom: 1px solid #2d1a00;
    flex-shrink: 0;
}
.afs-sessions-title {
    color: #888;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.afs-sessions-new {
    width: 26px;
    height: 26px;
    border-radius: 6px;
    background: #e85c00;
    border: none;
    color: #fff;
    font-size: 18px;
    line-height: 1;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: opacity 0.15s;
}
.afs-sessions-new:hover { opacity: 0.85; }
.afs-sessions-list {
    flex: 1;
    overflow-y: auto;
    padding: 4px 0;
}
.afs-sessions-list::-webkit-scrollbar { width: 3px; }
.afs-sessions-list::-webkit-scrollbar-track { background: #111; }
.afs-sessions-list::-webkit-scrollbar-thumb { background: #3d2400; border-radius: 2px; }
.afs-sessions-spinner {
    display: flex;
    justify-content: center;
    padding: 24px 0;
}
.afs-sessions-empty {
    padding: 20px 14px;
    color: #444;
    font-size: 12px;
    text-align: center;
}
.afs-session-item {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 9px 12px;
    cursor: pointer;
    border-left: 2px solid transparent;
    transition: background 0.14s, border-color 0.14s;
}
.afs-session-item:hover { background: rgba(232, 92, 0, 0.08); border-left-color: rgba(232,92,0,0.4); }
.afs-session-item--active { background: rgba(232, 92, 0, 0.15); border-left-color: #e85c00; }
.afs-session-item__body {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.afs-session-item__title {
    color: #c8bfb5;
    font-size: 12px;
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: block;
}
.afs-session-item--active .afs-session-item__title { color: #fff; }
.afs-session-item__date {
    color: #555;
    font-size: 10px;
}
.afs-session-item__del {
    flex-shrink: 0;
    background: transparent;
    border: none;
    color: #444;
    font-size: 11px;
    cursor: pointer;
    padding: 2px 4px;
    border-radius: 4px;
    opacity: 0;
    transition: opacity 0.14s, color 0.14s, background 0.14s;
}
.afs-session-item:hover .afs-session-item__del { opacity: 1; }
.afs-session-item__del:hover { color: #ff8888; background: rgba(200,0,0,0.15); }

/* ── Crop main area ──────────────────────────────────────────────────────── */
.afs-crop-main {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.afs-crop-messages {
    flex: 1;
    overflow-y: auto;
    padding: 20px 24px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}
.afs-crop-messages::-webkit-scrollbar { width: 5px; }
.afs-crop-messages::-webkit-scrollbar-track { background: #111; }
.afs-crop-messages::-webkit-scrollbar-thumb { background: #3d2400; border-radius: 3px; }

.afs-crop-empty {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
    color: #555;
    text-align: center;
    padding: 60px 20px;
}
.afs-crop-empty__icon { font-size: 48px; margin-bottom: 4px; }
.afs-crop-empty__title { font-size: 18px; font-weight: 700; color: #888; }
.afs-crop-empty__hint { font-size: 13px; line-height: 1.6; color: #555; }

/* ── Chat messages ───────────────────────────────────────────────────────── */
.afs-msg {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    max-width: 780px;
}
.afs-msg--user {
    flex-direction: row-reverse;
    align-self: flex-end;
}
.afs-msg--ai {
    align-self: flex-start;
}
.afs-msg__avatar {
    flex-shrink: 0;
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: #2d1a00;
    border: 1px solid #3d2400;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}
.afs-msg__bubble {
    background: #1e1e1e;
    border: 1px solid #2d2d2d;
    border-radius: 14px;
    padding: 12px 16px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    max-width: 660px;
}
.afs-msg--user .afs-msg__bubble {
    background: #2d1a00;
    border-color: #e85c00;
    border-radius: 14px 4px 14px 14px;
}
.afs-msg--ai .afs-msg__bubble {
    border-radius: 4px 14px 14px 14px;
}
.afs-msg__image {
    max-width: 320px;
    max-height: 240px;
    object-fit: contain;
    border-radius: 8px;
    border: 1px solid #3d3d3d;
}
.afs-msg__text {
    color: #d4d4d4;
    font-size: 13.5px;
    line-height: 1.65;
    white-space: pre-wrap;
    word-break: break-word;
}
.afs-msg--user .afs-msg__text { color: #ffe0c0; }

/* Typing animation */
.afs-msg__bubble--typing {
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 5px;
    padding: 14px 18px;
}
.afs-typing-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #e85c00;
    animation: typing-bounce 1.2s infinite ease-in-out;
}
.afs-typing-dot:nth-child(2) { animation-delay: 0.2s; }
.afs-typing-dot:nth-child(3) { animation-delay: 0.4s; }
@keyframes typing-bounce {
    0%, 80%, 100% { transform: scale(0.7); opacity: 0.5; }
    40%           { transform: scale(1.1); opacity: 1; }
}

/* ── Image preview bar ───────────────────────────────────────────────────── */
.afs-crop-preview-bar {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 20px;
    background: #1a1a1a;
    border-top: 1px solid #2d2d2d;
    flex-shrink: 0;
}
.afs-crop-preview-img {
    height: 60px;
    width: auto;
    border-radius: 6px;
    border: 1px solid #3d2400;
    object-fit: cover;
}
.afs-crop-preview-remove {
    background: #3d0a00;
    color: #ff8888;
    border: 1px solid #cc2200;
    border-radius: 6px;
    width: 28px;
    height: 28px;
    font-size: 12px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.15s;
}
.afs-crop-preview-remove:hover { background: #6d1000; }

/* ── Input row ───────────────────────────────────────────────────────────── */
.afs-crop-input-row {
    display: flex;
    align-items: flex-end;
    gap: 10px;
    padding: 14px 20px;
    background: #1a1a1a;
    border-top: 2px solid #2d1a00;
    flex-shrink: 0;
}
.afs-crop-file-hidden {
    display: none;
}
.afs-crop-attach-btn {
    flex-shrink: 0;
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: #2d1a00;
    border: 1px solid #3d2400;
    color: #c8bfb5;
    font-size: 18px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.15s, border-color 0.15s;
}
.afs-crop-attach-btn:hover {
    background: rgba(232, 92, 0, 0.2);
    border-color: #e85c00;
}
.afs-crop-textarea {
    flex: 1;
    background: #222;
    border: 1px solid #333;
    border-radius: 10px;
    color: #e0d6cc;
    font-size: 13.5px;
    line-height: 1.5;
    padding: 10px 14px;
    resize: none;
    max-height: 120px;
    overflow-y: auto;
    outline: none;
    font-family: inherit;
    transition: border-color 0.15s;
}
.afs-crop-textarea:focus { border-color: #e85c00; }
.afs-crop-textarea::placeholder { color: #555; }
.afs-crop-send-btn {
    flex-shrink: 0;
    padding: 10px 22px;
    background: #e85c00;
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    transition: opacity 0.18s;
    height: 40px;
}
.afs-crop-send-btn:hover:not(:disabled) { opacity: 0.85; }
.afs-crop-send-btn:disabled { opacity: 0.4; cursor: not-allowed; }

/* ── Camera modal ────────────────────────────────────────────────────────── */
.afs-camera-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.85);
    z-index: 9000;
    display: flex;
    align-items: center;
    justify-content: center;
}
.afs-camera-modal {
    background: #1a1a1a;
    border: 2px solid #e85c00;
    border-radius: 16px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    width: min(96vw, 640px);
    max-height: 92vh;
}
.afs-camera-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 20px;
    background: #2d1a00;
    border-bottom: 1px solid #3d2400;
    flex-shrink: 0;
}
.afs-camera-title {
    color: #fff;
    font-size: 14px;
    font-weight: 700;
}
.afs-camera-close {
    background: transparent;
    border: none;
    color: #888;
    font-size: 18px;
    cursor: pointer;
    line-height: 1;
    padding: 2px 6px;
    border-radius: 6px;
    transition: color 0.15s, background 0.15s;
}
.afs-camera-close:hover { color: #fff; background: rgba(255,255,255,0.1); }
.afs-camera-video {
    width: 100%;
    aspect-ratio: 4/3;
    object-fit: cover;
    background: #000;
    display: block;
}
.afs-camera-footer {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
    background: #111;
    flex-shrink: 0;
}
.afs-camera-capture-btn {
    width: 68px;
    height: 68px;
    border-radius: 50%;
    background: #e85c00;
    border: 4px solid #fff;
    cursor: pointer;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.12s, opacity 0.12s;
    box-shadow: 0 0 0 3px rgba(232, 92, 0, 0.4);
}
.afs-camera-capture-btn:hover { transform: scale(1.06); }
.afs-camera-capture-btn:active { transform: scale(0.94); opacity: 0.85; }
.afs-camera-capture-ring {
    width: 46px;
    height: 46px;
    border-radius: 50%;
    border: 3px solid rgba(255,255,255,0.7);
    display: block;
}
</style>
