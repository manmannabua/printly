<script setup lang="ts">
import { onBeforeUnmount, watch } from 'vue'
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'

const props = withDefaults(defineProps<{
  modelValue?: string
  placeholder?: string
}>(), {
  modelValue: '',
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const editor = useEditor({
  content: props.modelValue,
  extensions: [
    StarterKit.configure({
      heading: false,
      blockquote: false,
      codeBlock: false,
      code: false,
      horizontalRule: false,
    }),
  ],
  onUpdate: ({ editor: e }) => {
    emit('update:modelValue', e.getHTML())
  },
})

watch(() => props.modelValue, (val) => {
  if (editor.value && editor.value.getHTML() !== val) {
    editor.value.commands.setContent(val || '', false)
  }
})

onBeforeUnmount(() => {
  editor.value?.destroy()
})

function toggleBold() { editor.value?.chain().focus().toggleBold().run() }
function toggleItalic() { editor.value?.chain().focus().toggleItalic().run() }
function toggleBulletList() { editor.value?.chain().focus().toggleBulletList().run() }
function toggleOrderedList() { editor.value?.chain().focus().toggleOrderedList().run() }

function isActive(name: string): boolean {
  return editor.value?.isActive(name) ?? false
}
</script>

<template>
  <div class="rounded-md border border-gray-300 bg-white shadow-sm hover:border-c-primary focus-within:border-c-primary focus-within:ring-1 focus-within:ring-c-primary dark:border-zinc-600 dark:bg-zinc-800">
    <!-- Toolbar -->
    <div
      v-if="editor"
      class="flex items-center gap-0.5 border-b border-gray-200 px-2 py-1 dark:border-zinc-700"
    >
      <button
        type="button"
        title="Bold"
        class="rounded p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-zinc-700 dark:hover:text-gray-200"
        :class="{ 'bg-gray-200 text-gray-900 dark:bg-zinc-600 dark:text-white': isActive('bold') }"
        @click="toggleBold"
      >
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M6 4h8a4 4 0 014 4 4 4 0 01-4 4H6zM6 12h9a4 4 0 014 4 4 4 0 01-4 4H6z"/></svg>
      </button>
      <button
        type="button"
        title="Italic"
        class="rounded p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-zinc-700 dark:hover:text-gray-200"
        :class="{ 'bg-gray-200 text-gray-900 dark:bg-zinc-600 dark:text-white': isActive('italic') }"
        @click="toggleItalic"
      >
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10 4h4m-2 0l-4 16m0 0h4m-8 0h4"/></svg>
      </button>

      <span class="mx-1 h-4 w-px bg-gray-300 dark:bg-zinc-600" />

      <button
        type="button"
        title="Bullet List"
        class="rounded p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-zinc-700 dark:hover:text-gray-200"
        :class="{ 'bg-gray-200 text-gray-900 dark:bg-zinc-600 dark:text-white': isActive('bulletList') }"
        @click="toggleBulletList"
      >
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
      </button>
      <button
        type="button"
        title="Numbered List"
        class="rounded p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-zinc-700 dark:hover:text-gray-200"
        :class="{ 'bg-gray-200 text-gray-900 dark:bg-zinc-600 dark:text-white': isActive('orderedList') }"
        @click="toggleOrderedList"
      >
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10 6h11M10 12h11M10 18h11M3 5v2m0-2l1.5-1M3 7h2M3 17v-1.5l2-1.5M3 17h2m-2-6h2l-2 2.5"/></svg>
      </button>
    </div>

    <!-- Editor -->
    <div class="rich-editor-content px-3 py-2 text-base text-gray-900 dark:text-white">
      <EditorContent
        :editor="editor"
        :data-placeholder="placeholder"
      />
    </div>
  </div>
</template>

<style scoped>
.rich-editor-content :deep(.tiptap) {
  min-height: 5rem;
  outline: none;
}

.rich-editor-content :deep(.tiptap p.is-editor-empty:first-child::before) {
  content: attr(data-placeholder);
  float: left;
  height: 0;
  pointer-events: none;
  color: #9ca3af;
}

.rich-editor-content :deep(.tiptap ul) {
  list-style-type: disc;
  padding-left: 1.5rem;
  margin: 0.25rem 0;
}

.rich-editor-content :deep(.tiptap ol) {
  list-style-type: decimal;
  padding-left: 1.5rem;
  margin: 0.25rem 0;
}

.rich-editor-content :deep(.tiptap li) {
  margin: 0.125rem 0;
}

.rich-editor-content :deep(.tiptap li p) {
  margin: 0;
}

.rich-editor-content :deep(.tiptap p) {
  margin: 0.25rem 0;
}

.rich-editor-content :deep(.tiptap strong) {
  font-weight: 600;
}

.rich-editor-content :deep(.tiptap em) {
  font-style: italic;
}
</style>
