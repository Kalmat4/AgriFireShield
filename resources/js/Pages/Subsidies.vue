<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    subsidy_flags: { type: Array, default: () => [] },
    anomalies:     { type: Array, default: () => [] },
    stats:         { type: Object, default: () => ({}) },
})

const logout = () => router.post('/logout')

function formatBillion(val) {
    if (!val) return '0 млрд ₸'
    return (val / 1_000_000_000).toFixed(1) + ' млрд ₸'
}

function formatNum(val) {
    if (val == null) return '—'
    return Number(val).toLocaleString('ru-RU')
}

function rowClass(deviation) {
    if (deviation > 10)  return 'subs-row--red'
    if (deviation > 2)   return 'subs-row--yellow'
    return ''
}

function severityClass(severity) {
    if (severity === 'critical') return 'badge badge--red'
    if (severity === 'medium')   return 'badge badge--orange'
    return 'badge badge--yellow'
}

function anomalyLabel(type) {
    if (type === 'overreport')     return 'Завышение'
    if (type === 'subsidy_fraud')  return 'Субсидийный фрод'
    return type
}
</script>

<template>
    <AppLayout>
        <Head title="Информация о субсидиях" />

        <!-- Header -->
        <header class="afs-header">
            <div class="afs-header__inner">
                <Link href="/dashboard" class="afs-logo">
                    <span class="afs-logo__icon">🔥</span>
                    <span class="afs-logo__text">AgriFireShield</span>
                </Link>
                <nav class="afs-nav">
                    <Link href="/dashboard"  class="afs-nav__btn">Главная</Link>
                    <Link href="/subsidies"  class="afs-nav__btn">Субсидии</Link>
                    <Link href="/yield"      class="afs-nav__btn">Урожайность</Link>
                    <Link href="/profile"    class="afs-nav__btn">Профиль</Link>
                    <button class="afs-nav__logout" @click="logout">Выйти</button>
                </nav>
            </div>
        </header>

        <div class="subs-bg">
        <div class="subs-page">

            <div class="subs-page-title">Информация о субсидиях</div>

            <!-- Section 1: Stats cards -->
            <div class="subs-cards">
                <div class="subs-card">
                    <div class="subs-card__label">Общий объём субсидий</div>
                    <div class="subs-card__value">{{ formatBillion(stats.total_subsidy_kzt) }}</div>
                </div>
                <div class="subs-card subs-card--red">
                    <div class="subs-card__label">Потенциальные потери</div>
                    <div class="subs-card__value subs-card__value--red">{{ formatBillion(stats.total_potential_loss_kzt) }}</div>
                </div>
                <div class="subs-card">
                    <div class="subs-card__label">Критических аномалий</div>
                    <div class="subs-card__value">
                        <span class="badge badge--red">{{ stats.critical_count ?? 0 }}</span>
                    </div>
                </div>
                <div class="subs-card">
                    <div class="subs-card__label">Случаев мошенничества</div>
                    <div class="subs-card__value">
                        <span class="badge badge--orange">{{ stats.fraud_count ?? 0 }}</span>
                    </div>
                </div>
            </div>

            <!-- Section 2: Subsidy flags table -->
            <div class="subs-section">
                <div class="subs-section__title">Флаги субсидий</div>
                <div class="subs-table-wrap">
                    <table class="subs-table">
                        <thead>
                            <tr>
                                <th>Регион</th>
                                <th>Год</th>
                                <th>Задекл. площадь (га)</th>
                                <th>Спутн. площадь (га)</th>
                                <th>Расхождение (га)</th>
                                <th>Отклонение %</th>
                                <th>Сумма субсидий</th>
                                <th>Потенц. потери</th>
                                <th>Примечания</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(row, i) in subsidy_flags"
                                :key="i"
                                :class="rowClass(row.deviation_pct)"
                            >
                                <td>{{ row.region_name }}</td>
                                <td>{{ row.harvest_year }}</td>
                                <td>{{ formatNum(row.declared_area_ha) }}</td>
                                <td>{{ formatNum(row.satellite_area_ha) }}</td>
                                <td>{{ formatNum(row.area_gap_ha) }}</td>
                                <td>{{ row.deviation_pct != null ? Number(row.deviation_pct).toFixed(1) + ' %' : '—' }}</td>
                                <td>{{ formatNum(row.subsidy_amount_kzt) }} ₸</td>
                                <td>{{ formatNum(row.potential_loss_kzt) }} ₸</td>
                                <td>{{ row.notes ?? '—' }}</td>
                            </tr>
                            <tr v-if="!subsidy_flags.length">
                                <td colspan="9" class="subs-table__empty">Нет данных</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Section 3: Anomalies table -->
            <div class="subs-section">
                <div class="subs-section__title">Аномалии урожайности</div>
                <div class="subs-table-wrap">
                    <table class="subs-table">
                        <thead>
                            <tr>
                                <th>Год</th>
                                <th>Регион</th>
                                <th>Культура</th>
                                <th>Заявл. урожайность</th>
                                <th>Спутн. урожайность</th>
                                <th>Отклонение %</th>
                                <th>Тип</th>
                                <th>Серьёзность</th>
                                <th>Описание</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, i) in anomalies" :key="i">
                                <td>{{ row.harvest_year }}</td>
                                <td>{{ row.region_name }}</td>
                                <td>{{ row.crop_name }}</td>
                                <td>{{ formatNum(row.declared_yield) }}</td>
                                <td>{{ formatNum(row.satellite_yield) }}</td>
                                <td>{{ row.deviation_pct != null ? Number(row.deviation_pct).toFixed(1) + ' %' : '—' }}</td>
                                <td>{{ anomalyLabel(row.anomaly_type) }}</td>
                                <td><span :class="severityClass(row.severity)">{{ row.severity }}</span></td>
                                <td>{{ row.description ?? '—' }}</td>
                            </tr>
                            <tr v-if="!anomalies.length">
                                <td colspan="9" class="subs-table__empty">Нет активных аномалий</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* ── Header (reuse Dashboard styles) ─────────────────────────────────────── */
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
.afs-logo__icon { font-size: 28px; filter: drop-shadow(0 0 6px rgba(255,120,0,0.8)); }
.afs-logo__text { font-size: 20px; font-weight: 700; color: #fff; letter-spacing: 0.5px; }
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
.afs-nav__logout:hover { background: rgba(255,60,0,0.2); color: #fff; border-color: rgba(255,60,0,0.5); }

/* ── Dark background ──────────────────────────────────────────────────────── */
.subs-bg {
    background: #111;
    min-height: calc(100vh - 64px);
}

/* ── Page wrapper ─────────────────────────────────────────────────────────── */
.subs-page {
    padding: 28px 32px;
    max-width: 1400px;
    margin: 0 auto;
}

.subs-page-title {
    font-size: 22px;
    font-weight: 700;
    color: #fff;
    margin-bottom: 24px;
    letter-spacing: 0.3px;
}

/* ── Stat cards ───────────────────────────────────────────────────────────── */
.subs-cards {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 32px;
}
.subs-card {
    background: #1e1e1e;
    border: 1px solid #2d2d2d;
    border-radius: 12px;
    padding: 20px 22px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.subs-card--red { border-color: rgba(220, 38, 38, 0.4); background: #1e1212; }
.subs-card__label { font-size: 12px; font-weight: 600; color: #888; text-transform: uppercase; letter-spacing: 0.5px; }
.subs-card__value { font-size: 24px; font-weight: 700; color: #fff; }
.subs-card__value--red { color: #f87171; }

/* ── Badges ───────────────────────────────────────────────────────────────── */
.badge {
    display: inline-block;
    padding: 4px 14px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 700;
}
.badge--red    { background: rgba(220,38,38,0.2);  color: #f87171; border: 1px solid rgba(220,38,38,0.4); }
.badge--orange { background: rgba(234,88,12,0.2);  color: #fb923c; border: 1px solid rgba(234,88,12,0.4); }
.badge--yellow { background: rgba(202,138,4,0.2);  color: #facc15; border: 1px solid rgba(202,138,4,0.4); }

/* ── Section ──────────────────────────────────────────────────────────────── */
.subs-section { margin-bottom: 36px; }
.subs-section__title {
    font-size: 16px;
    font-weight: 700;
    color: #e0d6cc;
    margin-bottom: 12px;
    padding-bottom: 8px;
    border-bottom: 2px solid #2d1a00;
}

/* ── Table ────────────────────────────────────────────────────────────────── */
.subs-table-wrap {
    overflow-x: auto;
    border-radius: 10px;
    border: 1px solid #2d2d2d;
}
.subs-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
.subs-table thead tr {
    background: #1a1a1a;
}
.subs-table th {
    padding: 11px 14px;
    text-align: left;
    color: #888;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    white-space: nowrap;
    border-bottom: 1px solid #2d2d2d;
}
.subs-table tbody tr {
    background: #161616;
    border-bottom: 1px solid #222;
    transition: background 0.12s;
}
.subs-table tbody tr:hover { background: #1e1e1e; }
.subs-table td {
    padding: 10px 14px;
    color: #c8bfb5;
    vertical-align: middle;
}
.subs-row--red  { background: rgba(220, 38, 38, 0.12) !important; }
.subs-row--red:hover { background: rgba(220, 38, 38, 0.18) !important; }
.subs-row--yellow  { background: rgba(202, 138, 4, 0.1) !important; }
.subs-row--yellow:hover { background: rgba(202, 138, 4, 0.16) !important; }
.subs-table__empty {
    text-align: center;
    padding: 28px;
    color: #555;
    font-style: italic;
}
</style>
