import { ref } from 'vue'
export const searchOpen = ref(false)
export function useGlobalSearch() {
  function open() { searchOpen.value = true }
  function close() { searchOpen.value = false }
  function toggle() { searchOpen.value = !searchOpen.value }
  return { searchOpen, open, close, toggle }
}
