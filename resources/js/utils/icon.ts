import { defineAsyncComponent, type Component } from 'vue'

// lucide icon resolver function
export function resolveIcon(name: string): Component {
  const iconName = name.charAt(0).toUpperCase() + name.slice(1)
  return defineAsyncComponent(async () => {
    const icons = await import('lucide-vue-next')

    const maybeComponent = icons[iconName as keyof typeof icons]
    const fallback = icons.LayoutGrid

    // pastikan hasilnya adalah komponen Vue (bukan fungsi biasa seperti createLucideIcon)
    if (typeof maybeComponent == 'function' || typeof maybeComponent == 'object') {
      return maybeComponent as Component
    }

    return fallback as Component
  })
}