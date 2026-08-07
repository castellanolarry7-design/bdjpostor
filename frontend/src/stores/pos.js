// src/stores/pos.js
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { posApi } from '@/api/services'

export const usePosStore = defineStore('pos', () => {
  // ─── Estado ────────────────────────────────────────────────────────────────
  const cart          = ref([])   // [{ product, quantity, unitPrice, subtotal }]
  const lastSale      = ref(null)
  const exchangeRates = ref({
    USD:   { rate: 1, symbol: '$',  name: 'Dólar (USD)' },
    LOCAL: { rate: 1, symbol: 'Bs', name: 'Moneda Local' },
  })
  const localCurrencyCode = ref(localStorage.getItem('pos_local_currency') || 'VES')
  const ratesLoading      = ref(false)

  // ─── Computed ───────────────────────────────────────────────────────────────
  const cartTotal  = computed(() => cart.value.reduce((sum, i) => sum + i.subtotal, 0))
  const cartCount  = computed(() => cart.value.reduce((sum, i) => sum + i.quantity, 0))
  const cartIsEmpty = computed(() => cart.value.length === 0)

  // ─── Carrito ────────────────────────────────────────────────────────────────
  function addToCart(product) {
    const existing = cart.value.find(i => i.product.id === product.id)
    if (existing) {
      existing.quantity++
      existing.subtotal = round(existing.quantity * existing.unitPrice)
    } else {
      cart.value.push({ product, quantity: 1, unitPrice: product.price, subtotal: product.price })
    }
  }

  function updateQuantity(productId, qty) {
    const item = cart.value.find(i => i.product.id === productId)
    if (!item) return
    if (qty <= 0) { removeFromCart(productId); return }
    item.quantity = qty
    item.subtotal = round(qty * item.unitPrice)
  }

  function removeFromCart(productId) {
    cart.value = cart.value.filter(i => i.product.id !== productId)
  }

  function clearCart() {
    cart.value = []
    lastSale.value = null
  }

  // ─── Tasas de cambio (solo fiat) ────────────────────────────────────────────
  async function fetchExchangeRates() {
    ratesLoading.value = true
    try {
      const res = await fetch(`https://open.er-api.com/v6/latest/USD`)
      if (res.ok) {
        const data = await res.json()
        const localRate = data.rates[localCurrencyCode.value] ?? 1
        exchangeRates.value.LOCAL = {
          rate: localRate,
          symbol: localCurrencyCode.value === 'VES' ? 'Bs' : localCurrencyCode.value,
          name: `Moneda Local (${localCurrencyCode.value})`,
        }
      }
    } catch (_) {}
    ratesLoading.value = false
  }

  function setLocalCurrency(code) {
    localCurrencyCode.value = code
    localStorage.setItem('pos_local_currency', code)
    fetchExchangeRates()
  }

  // Convierte monto en la moneda indicada a USD
  function toUSD(amount, currencyKey) {
    if (currencyKey === 'USD') return amount
    const info = exchangeRates.value[currencyKey]
    if (!info || !info.rate || info.rate === 0) return amount
    return amount / info.rate
  }

  // Convierte USD a la moneda indicada
  function fromUSD(usdAmount, currencyKey) {
    if (currencyKey === 'USD') return usdAmount
    const info = exchangeRates.value[currencyKey]
    if (!info || !info.rate || info.rate === 0) return usdAmount
    return usdAmount * info.rate
  }

  // ─── Procesar venta ─────────────────────────────────────────────────────────
  async function submitSale(payments, notes = '') {
    const items = cart.value.map(i => ({
      product_id: i.product.id,
      quantity:   i.quantity,
      unit_price: i.unitPrice,
    }))

    const paymentsPayload = payments.map(p => ({
      method:        p.method,
      currency:      p.currency,
      amount:        p.amount,
      amount_usd:    toUSD(p.amount, p.currency),
      exchange_rate: exchangeRates.value[p.currency]?.rate ?? 1,
    }))

    const { data } = await posApi.createSale({ items, payments: paymentsPayload, notes })
    lastSale.value = data.data
    clearCart()
    return data.data
  }

  function round(n) { return Math.round(n * 100) / 100 }

  return {
    cart, lastSale, exchangeRates, ratesLoading,
    localCurrencyCode,
    cartTotal, cartCount, cartIsEmpty,
    addToCart, updateQuantity, removeFromCart, clearCart,
    fetchExchangeRates, setLocalCurrency, toUSD, fromUSD,
    submitSale,
  }
})
