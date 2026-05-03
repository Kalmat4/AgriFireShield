<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/AuthLayout.vue'

const form = useForm({
    email: '',
    password: '',
    remember: false,
})

const submit = () => {
    form.post('/login')
}
</script>

<template>
    <Head title="Вход — AgroMind KZ" />

    <AuthLayout>

        <div class="auth-brand">
            <span class="auth-brand__icon">🌿</span>
            <span class="auth-brand__name">AgroMind KZ</span>
        </div>

        <h1 class="auth-title">Войти в платформу</h1>
        <p class="auth-subtitle">Система агроэкологического мониторинга</p>

        <form @submit.prevent="submit" class="auth-form">

            <div class="auth-field">
                <label for="email">Email</label>
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    placeholder="example@mail.com"
                    :class="{ 'auth-input--error': form.errors.email }"
                    autocomplete="email"
                />
                <span v-if="form.errors.email" class="auth-error">{{ form.errors.email }}</span>
            </div>

            <div class="auth-field">
                <label for="password">Пароль</label>
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    placeholder="••••••••"
                    :class="{ 'auth-input--error': form.errors.password }"
                    autocomplete="current-password"
                />
                <span v-if="form.errors.password" class="auth-error">{{ form.errors.password }}</span>
            </div>

            <div class="auth-remember">
                <input id="remember" v-model="form.remember" type="checkbox" />
                <label for="remember">Запомнить меня</label>
            </div>

            <button type="submit" class="auth-btn" :disabled="form.processing">
                {{ form.processing ? 'Вход...' : 'Войти' }}
            </button>

        </form>

        <p class="auth-footer-link">
            Нет аккаунта?
            <Link href="/register">Зарегистрируйтесь</Link>
        </p>

    </AuthLayout>
</template>

<style scoped>
/* ── Brand ── */
.auth-brand {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-bottom: 28px;
}

.auth-brand__icon {
    font-size: 28px;
    filter: drop-shadow(0 0 8px rgba(0, 230, 118, 0.9));
}

.auth-brand__name {
    font-size: 20px;
    font-weight: 700;
    color: #fff;
    letter-spacing: 0.4px;
}

/* ── Heading ── */
.auth-title {
    font-size: 22px;
    font-weight: 700;
    color: #e5e5e5;
    text-align: center;
    margin: 0 0 6px;
}

.auth-subtitle {
    font-size: 13px;
    color: #555;
    text-align: center;
    margin: 0 0 28px;
}

/* ── Form ── */
.auth-form {
    display: flex;
    flex-direction: column;
    gap: 18px;
}

.auth-field {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.auth-field label {
    font-size: 13px;
    font-weight: 600;
    color: #888;
}

.auth-field input[type="email"],
.auth-field input[type="password"] {
    background: #111;
    border: 1px solid #2d2d2d;
    border-radius: 8px;
    color: #e5e5e5;
    font-size: 14px;
    padding: 10px 14px;
    outline: none;
    transition: border-color 0.18s, box-shadow 0.18s;
    font-family: inherit;
    width: 100%;
}

.auth-field input::placeholder {
    color: #555;
}

.auth-field input:focus {
    border-color: #4ade80;
    box-shadow: 0 0 0 3px rgba(74, 222, 128, 0.1);
}

.auth-input--error {
    border-color: #f87171 !important;
}

.auth-error {
    font-size: 12px;
    color: #f87171;
}

/* ── Remember ── */
.auth-remember {
    display: flex;
    align-items: center;
    gap: 8px;
}

.auth-remember input[type="checkbox"] {
    width: 16px;
    height: 16px;
    accent-color: #4ade80;
    cursor: pointer;
}

.auth-remember label {
    font-size: 13px;
    color: #888;
    cursor: pointer;
}

/* ── Submit button ── */
.auth-btn {
    width: 100%;
    padding: 11px;
    background: #4ade80;
    color: #000;
    font-size: 14px;
    font-weight: 700;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.18s;
    margin-top: 4px;
}

.auth-btn:hover:not(:disabled) {
    background: #22c55e;
}

.auth-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* ── Bottom link ── */
.auth-footer-link {
    margin-top: 20px;
    text-align: center;
    font-size: 13px;
    color: #555;
}

.auth-footer-link a {
    color: #4ade80;
    text-decoration: none;
    font-weight: 600;
}

.auth-footer-link a:hover {
    color: #22c55e;
}
</style>
