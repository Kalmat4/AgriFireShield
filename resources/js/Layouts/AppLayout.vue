<script setup>
import { Link, router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

const logout = () => { router.post('/logout') }

const setLocale = (lang) => {
    locale.value = lang
    localStorage.setItem('agromind_locale', lang)
}
</script>

<template>
    <div class="min-vh-100" style="background: #070d08;">
        <header class="afs-header">
            <div class="afs-header__inner">
                <Link href="/dashboard" class="afs-logo">
                    <span class="afs-logo__icon">🌿</span>
                    <span class="afs-logo__text">AgroMind KZ</span>
                </Link>
                <nav class="afs-nav">
                    <Link href="/dashboard" class="afs-nav__btn">{{ t('nav.home') }}</Link>
                    <Link href="/subsidies" class="afs-nav__btn">{{ t('nav.subsidies') }}</Link>
                    <Link href="/ecology" class="afs-nav__btn">{{ t('nav.ecology') }}</Link>
                    <Link href="/yield" class="afs-nav__btn">{{ t('nav.yield') }}</Link>
                    <Link href="/about" class="afs-nav__btn">{{ t('nav.about') }}</Link>
                    <Link href="/profile" class="afs-nav__btn">{{ t('nav.profile') }}</Link>

                    <div class="lang-switcher">
                        <button
                            v-for="lang in ['ru', 'en', 'kz']"
                            :key="lang"
                            class="lang-btn"
                            :class="{ active: locale === lang }"
                            @click="setLocale(lang)"
                        >{{ lang.toUpperCase() }}</button>
                    </div>

                    <button class="afs-nav__logout" @click="logout">{{ t('nav.logout') }}</button>
                </nav>
            </div>
        </header>
        <slot />
    </div>
</template>

<style scoped>
.afs-header {
    background: linear-gradient(135deg, #080d09 0%, #0b2014 100%);
    border-bottom: 3px solid #00e676;
    box-shadow: 0 2px 20px rgba(0, 230, 118, 0.25);
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
    filter: drop-shadow(0 0 8px rgba(0, 230, 118, 0.9));
}

.afs-logo__text {
    font-size: 20px;
    font-weight: 700;
    color: #ffffff;
    letter-spacing: 0.5px;
}

.afs-nav {
    display: flex;
    align-items: center;
    gap: 8px;
}

.afs-nav__btn {
    display: inline-flex;
    align-items: center;
    padding: 8px 18px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    color: #a8d5b5;
    text-decoration: none;
    border: 1px solid transparent;
    transition: background 0.18s, color 0.18s, box-shadow 0.18s;
}

.afs-nav__btn:hover,
.afs-nav__btn.router-link-active {
    background: rgba(0, 230, 118, 0.12);
    color: #00e676;
    border-color: rgba(0, 230, 118, 0.35);
    box-shadow: 0 0 8px rgba(0, 230, 118, 0.15);
}

.afs-nav__logout {
    display: inline-flex;
    align-items: center;
    padding: 8px 18px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    color: #a8d5b5;
    background: transparent;
    border: 1px solid rgba(168, 213, 181, 0.25);
    cursor: pointer;
    transition: background 0.18s, color 0.18s;
}

.afs-nav__logout:hover {
    background: rgba(0, 230, 118, 0.12);
    color: #00e676;
    border-color: rgba(0, 230, 118, 0.4);
}

/* ── Language switcher ── */
.lang-switcher {
    display: flex;
    align-items: center;
    gap: 2px;
    background: rgba(0, 230, 118, 0.06);
    border: 1px solid rgba(0, 230, 118, 0.2);
    border-radius: 8px;
    padding: 3px;
    margin: 0 8px;
}

.lang-btn {
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    color: #a8d5b5;
    background: transparent;
    border: none;
    cursor: pointer;
    transition: all 0.15s;
    letter-spacing: 0.5px;
}

.lang-btn:hover { color: #00e676; }

.lang-btn.active {
    background: rgba(0, 230, 118, 0.2);
    color: #00e676;
}
</style>
