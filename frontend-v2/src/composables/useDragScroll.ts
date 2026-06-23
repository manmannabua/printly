import { onMounted, onBeforeUnmount, type Ref } from 'vue'

interface UseDragScrollOptions {
  /** Distance from container edge (px) that triggers auto-scroll during HTML5 drag. */
  edgeThreshold?: number
  /** Max horizontal scroll speed (px per ~16ms tick) when pointer hugs the edge. */
  edgeSpeed?: number
  /** Mouse-down on elements matching this selector (or their descendants) will NOT
   *  initiate panning. Defaults cover draggable cards and form/interactive controls. */
  panSkipSelector?: string
}

/**
 * Adds two scroll behaviors to a horizontally scrollable Kanban container:
 *   1. Edge-auto-scroll while a card is being dragged — pulls hidden columns into
 *      view so the user can drop into off-screen targets.
 *   2. Click-and-drag panning on empty board space (Trello-style grab-to-pan), so
 *      users don't have to chase the bottom scrollbar.
 *
 * Pass a Ref pointing at the scrollable element. Both behaviors no-op gracefully
 * if the element isn't mounted yet.
 */
export function useDragScroll(
  elementRef: Ref<HTMLElement | null>,
  options: UseDragScrollOptions = {},
) {
  const edgeThreshold = options.edgeThreshold ?? 80
  const edgeSpeed = options.edgeSpeed ?? 18
  const panSkipSelector =
    options.panSkipSelector ??
    '[draggable="true"], button, a, input, select, textarea, [role="button"]'

  let scrollInterval: ReturnType<typeof setInterval> | null = null
  let isPanning = false
  let panStartX = 0
  let panStartScrollLeft = 0

  function clearScroll(): void {
    if (scrollInterval) {
      clearInterval(scrollInterval)
      scrollInterval = null
    }
  }

  function onDragOver(e: DragEvent): void {
    const el = elementRef.value
    if (!el) return
    const rect = el.getBoundingClientRect()
    const fromLeft = e.clientX - rect.left
    const fromRight = rect.right - e.clientX

    clearScroll()
    if (fromLeft < edgeThreshold && fromLeft >= 0) {
      const speed = ((edgeThreshold - fromLeft) / edgeThreshold) * edgeSpeed
      scrollInterval = setInterval(() => {
        el.scrollLeft -= speed
      }, 16)
    } else if (fromRight < edgeThreshold && fromRight >= 0) {
      const speed = ((edgeThreshold - fromRight) / edgeThreshold) * edgeSpeed
      scrollInterval = setInterval(() => {
        el.scrollLeft += speed
      }, 16)
    }
  }

  function onMouseDown(e: MouseEvent): void {
    const el = elementRef.value
    if (!el || e.button !== 0) return
    const target = e.target as HTMLElement | null
    if (!target || target.closest(panSkipSelector)) return

    isPanning = true
    panStartX = e.clientX
    panStartScrollLeft = el.scrollLeft
    el.style.cursor = 'grabbing'
    el.style.userSelect = 'none'
  }

  function onMouseMove(e: MouseEvent): void {
    if (!isPanning) return
    const el = elementRef.value
    if (!el) return
    el.scrollLeft = panStartScrollLeft - (e.clientX - panStartX)
  }

  function endPan(): void {
    if (!isPanning) return
    isPanning = false
    const el = elementRef.value
    if (el) {
      el.style.cursor = ''
      el.style.userSelect = ''
    }
  }

  onMounted(() => {
    const el = elementRef.value
    if (!el) return
    el.addEventListener('dragover', onDragOver)
    el.addEventListener('dragend', clearScroll)
    el.addEventListener('drop', clearScroll)
    el.addEventListener('mousedown', onMouseDown)
    document.addEventListener('mousemove', onMouseMove)
    document.addEventListener('mouseup', endPan)
  })

  onBeforeUnmount(() => {
    clearScroll()
    endPan()
    const el = elementRef.value
    if (el) {
      el.removeEventListener('dragover', onDragOver)
      el.removeEventListener('dragend', clearScroll)
      el.removeEventListener('drop', clearScroll)
      el.removeEventListener('mousedown', onMouseDown)
    }
    document.removeEventListener('mousemove', onMouseMove)
    document.removeEventListener('mouseup', endPan)
  })
}
