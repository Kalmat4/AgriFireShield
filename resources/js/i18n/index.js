import { createI18n } from 'vue-i18n'
import ru from './ru.js'
import en from './en.js'
import kz from './kz.js'

const savedLocale = localStorage.getItem('agromind_locale') || 'ru'

export const i18n = createI18n({
  legacy: false,
  locale: savedLocale,
  fallbackLocale: 'ru',
  messages: { ru, en, kz }
})
