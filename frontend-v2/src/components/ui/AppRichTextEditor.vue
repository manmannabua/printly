<script setup lang="ts">
import { computed, onBeforeUnmount, watch } from 'vue'
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import { Table, TableRow, TableCell, TableHeader } from '@tiptap/extension-table'
import { Extension } from '@tiptap/core'

const TabIndent = Extension.create({
  name: 'tabIndent',
  addKeyboardShortcuts() {
    return {
      Tab: () => this.editor.commands.insertContent('    '),
      ' ': ({ editor }) => {
        const { $from } = editor.state.selection
        const textBefore = $from.parent.textContent.slice(0, $from.parentOffset)
        if (/^[ ]*$/.test(textBefore)) {
          return editor.commands.insertContent(' ')
        }
        return false
      },
    }
  },
})

const props = withDefaults(defineProps<{
  modelValue?: string
  label?: string
  placeholder?: string
  error?: string | string[]
  id?: string
  disabled?: boolean
  required?: boolean
}>(), {
  modelValue: '',
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const inputId = computed(() => props.id || (props.label ? `rte-${props.label.toLowerCase().replace(/\s+/g, '-')}` : undefined))

const errorMessage = computed(() => {
  if (!props.error) return null
  if (Array.isArray(props.error)) return props.error[0] ?? null
  return props.error
})

const hasError = computed(() => !!errorMessage.value)

const editor = useEditor({
  content: props.modelValue,
  editable: !props.disabled,
  extensions: [
    StarterKit.configure({
      heading: { levels: [2, 3] },
      link: {
        openOnClick: false,
        HTMLAttributes: { rel: 'noopener noreferrer nofollow', target: '_blank' },
      },
    }),
    Table,
    TableRow,
    TableCell,
    TableHeader,
    TabIndent,
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

watch(() => props.disabled, (val) => {
  editor.value?.setEditable(!val)
})

onBeforeUnmount(() => {
  editor.value?.destroy()
})

function toggleBold() { editor.value?.chain().focus().toggleBold().run() }
function toggleItalic() { editor.value?.chain().focus().toggleItalic().run() }
function toggleStrike() { editor.value?.chain().focus().toggleStrike().run() }
function toggleH2() { editor.value?.chain().focus().toggleHeading({ level: 2 }).run() }
function toggleH3() { editor.value?.chain().focus().toggleHeading({ level: 3 }).run() }
function toggleBulletList() { editor.value?.chain().focus().toggleBulletList().run() }
function toggleOrderedList() { editor.value?.chain().focus().toggleOrderedList().run() }
function toggleBlockquote() { editor.value?.chain().focus().toggleBlockquote().run() }
function insertHr() { editor.value?.chain().focus().setHorizontalRule().run() }
function undo() { editor.value?.chain().focus().undo().run() }
function redo() { editor.value?.chain().focus().redo().run() }

function toggleLink() {
  if (!editor.value) return
  if (editor.value.isActive('link')) {
    editor.value.chain().focus().unsetLink().run()
    return
  }
  const url = window.prompt('Enter URL')
  if (url) {
    editor.value.chain().focus().setLink({ href: url }).run()
  }
}

function isActive(name: string, attrs?: Record<string, unknown>): boolean {
  return editor.value?.isActive(name, attrs) ?? false
}

function insertTable() {
  editor.value?.chain().focus().insertTable({ rows: 3, cols: 2, withHeaderRow: true }).run()
}
function addRowAfter() { editor.value?.chain().focus().addRowAfter().run() }
function addColumnAfter() { editor.value?.chain().focus().addColumnAfter().run() }
function deleteRow() { editor.value?.chain().focus().deleteRow().run() }
function deleteColumn() { editor.value?.chain().focus().deleteColumn().run() }
function deleteTable() { editor.value?.chain().focus().deleteTable().run() }
</script>

<template>
  <div>
    <label
      v-if="label"
      :for="inputId"
      class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300"
    >
      {{ label }}
      <span v-if="required" class="text-danger-500">*</span>
    </label>

    <div
      class="rounded-lg border bg-white transition-colors dark:bg-gray-800"
      :class="[
        hasError
          ? 'border-danger-500 focus-within:border-danger-500 focus-within:ring-1 focus-within:ring-danger-500'
          : 'border-gray-300 focus-within:border-primary-500 focus-within:ring-1 focus-within:ring-primary-500 dark:border-gray-600',
        disabled ? 'cursor-not-allowed opacity-50' : '',
      ]"
    >
      <!-- Toolbar -->
      <div
        v-if="editor && !disabled"
        class="flex flex-wrap items-center gap-0.5 border-b border-gray-200 px-2 py-1.5 dark:border-gray-600"
        role="toolbar"
        aria-label="Text formatting"
      >
        <button
          type="button"
          title="Bold"
          class="rounded p-1.5 text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200"
          :class="{ 'bg-gray-200 text-gray-900 dark:bg-gray-700 dark:text-gray-200': isActive('bold') }"
          @click="toggleBold"
        >
          <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M6 4h8a4 4 0 014 4 4 4 0 01-4 4H6zM6 12h9a4 4 0 014 4 4 4 0 01-4 4H6z"/></svg>
        </button>
        <button
          type="button"
          title="Italic"
          class="rounded p-1.5 text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200"
          :class="{ 'bg-gray-200 text-gray-900 dark:bg-gray-700 dark:text-gray-200': isActive('italic') }"
          @click="toggleItalic"
        >
          <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10 4h4m-2 0l-4 16m0 0h4m-8 0h4"/></svg>
        </button>
        <button
          type="button"
          title="Strikethrough"
          class="rounded p-1.5 text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200"
          :class="{ 'bg-gray-200 text-gray-900 dark:bg-gray-700 dark:text-gray-200': isActive('strike') }"
          @click="toggleStrike"
        >
          <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14M12 5c-2 0-3.5 1-3.5 3s1.5 2 3.5 2 3.5 1 3.5 3-1.5 3-3.5 3"/></svg>
        </button>

        <span class="mx-1 h-5 w-px bg-gray-300 dark:bg-gray-600" />

        <button
          type="button"
          title="Heading 2"
          class="rounded px-1.5 py-1 text-xs font-bold text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200"
          :class="{ 'bg-gray-200 text-gray-900 dark:bg-gray-700 dark:text-gray-200': isActive('heading', { level: 2 }) }"
          @click="toggleH2"
        >
          H2
        </button>
        <button
          type="button"
          title="Heading 3"
          class="rounded px-1.5 py-1 text-xs font-bold text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200"
          :class="{ 'bg-gray-200 text-gray-900 dark:bg-gray-700 dark:text-gray-200': isActive('heading', { level: 3 }) }"
          @click="toggleH3"
        >
          H3
        </button>

        <span class="mx-1 h-5 w-px bg-gray-300 dark:bg-gray-600" />

        <button
          type="button"
          title="Bullet List"
          class="rounded p-1.5 text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200"
          :class="{ 'bg-gray-200 text-gray-900 dark:bg-gray-700 dark:text-gray-200': isActive('bulletList') }"
          @click="toggleBulletList"
        >
          <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
        </button>
        <button
          type="button"
          title="Ordered List"
          class="rounded p-1.5 text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200"
          :class="{ 'bg-gray-200 text-gray-900 dark:bg-gray-700 dark:text-gray-200': isActive('orderedList') }"
          @click="toggleOrderedList"
        >
          <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10 6h11M10 12h11M10 18h11M3 5v2m0-2l1.5-1M3 7h2M3 17v-1.5l2-1.5M3 17h2m-2-6h2l-2 2.5"/></svg>
        </button>
        <button
          type="button"
          title="Blockquote"
          class="rounded p-1.5 text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200"
          :class="{ 'bg-gray-200 text-gray-900 dark:bg-gray-700 dark:text-gray-200': isActive('blockquote') }"
          @click="toggleBlockquote"
        >
          <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10 11h-4a1 1 0 01-1-1V7a1 1 0 011-1h3a1 1 0 011 1v3a4 4 0 01-4 4m11-4h-4a1 1 0 01-1-1V7a1 1 0 011-1h3a1 1 0 011 1v3a4 4 0 01-4 4"/></svg>
        </button>

        <span class="mx-1 h-5 w-px bg-gray-300 dark:bg-gray-600" />

        <button
          type="button"
          title="Link"
          class="rounded p-1.5 text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200"
          :class="{ 'bg-gray-200 text-gray-900 dark:bg-gray-700 dark:text-gray-200': isActive('link') }"
          @click="toggleLink"
        >
          <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/></svg>
        </button>
        <button
          type="button"
          title="Horizontal Rule"
          class="rounded p-1.5 text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200"
          @click="insertHr"
        >
          <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 12h18"/></svg>
        </button>

        <span class="mx-1 h-5 w-px bg-gray-300 dark:bg-gray-600" />

        <button
          type="button"
          title="Undo"
          class="rounded p-1.5 text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200"
          @click="undo"
        >
          <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 10h10a5 5 0 015 5v2M3 10l4-4M3 10l4 4"/></svg>
        </button>
        <button
          type="button"
          title="Redo"
          class="rounded p-1.5 text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200"
          @click="redo"
        >
          <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 10H11a5 5 0 00-5 5v2M21 10l-4-4M21 10l-4 4"/></svg>
        </button>

        <span class="mx-1 h-5 w-px bg-gray-300 dark:bg-gray-600" />

        <!-- Table controls -->
        <button
          type="button"
          title="Insert Table"
          class="rounded px-1.5 py-1 text-xs font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200"
          @click="insertTable"
        >
          Table
        </button>
        <template v-if="isActive('table')">
          <button
            type="button"
            title="Add Row Below"
            class="rounded px-1.5 py-1 text-xs text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200"
            @click="addRowAfter"
          >
            +Row
          </button>
          <button
            type="button"
            title="Add Column Right"
            class="rounded px-1.5 py-1 text-xs text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200"
            @click="addColumnAfter"
          >
            +Col
          </button>
          <button
            type="button"
            title="Delete Row"
            class="rounded px-1.5 py-1 text-xs text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200"
            @click="deleteRow"
          >
            -Row
          </button>
          <button
            type="button"
            title="Delete Column"
            class="rounded px-1.5 py-1 text-xs text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200"
            @click="deleteColumn"
          >
            -Col
          </button>
          <button
            type="button"
            title="Delete Table"
            class="rounded px-1.5 py-1 text-xs text-red-600 hover:bg-red-50 hover:text-red-700 dark:text-red-400 dark:hover:bg-red-950 dark:hover:text-red-300"
            @click="deleteTable"
          >
            Del Table
          </button>
        </template>
      </div>

      <!-- Editor content -->
      <div class="rte-content px-3 py-2.5 text-sm text-gray-900 dark:text-gray-100">
        <EditorContent
          :editor="editor"
          :data-placeholder="placeholder"
        />
      </div>
    </div>

    <p
      v-if="hasError"
      :id="inputId ? `${inputId}-error` : undefined"
      class="mt-1 text-sm text-danger-600"
    >
      {{ errorMessage }}
    </p>
  </div>
</template>

<style scoped>
.rte-content :deep(.tiptap) {
  min-height: 6rem;
  outline: none;
}

.rte-content :deep(.tiptap p.is-editor-empty:first-child::before) {
  content: attr(data-placeholder);
  float: left;
  height: 0;
  pointer-events: none;
  color: #9ca3af;
}

.rte-content :deep(.tiptap ul) {
  list-style-type: disc;
  padding-left: 1.5rem;
  margin: 0.25rem 0;
}

.rte-content :deep(.tiptap ol) {
  list-style-type: decimal;
  padding-left: 1.5rem;
  margin: 0.25rem 0;
}

.rte-content :deep(.tiptap li) {
  margin: 0.125rem 0;
}

.rte-content :deep(.tiptap li p) {
  margin: 0;
}

.rte-content :deep(.tiptap p) {
  margin: 0.25rem 0;
}

.rte-content :deep(.tiptap h2) {
  font-size: 1.25rem;
  font-weight: 600;
  margin: 0.75rem 0 0.25rem;
}

.rte-content :deep(.tiptap h3) {
  font-size: 1.1rem;
  font-weight: 600;
  margin: 0.5rem 0 0.25rem;
}

.rte-content :deep(.tiptap blockquote) {
  border-left: 3px solid #d1d5db;
  padding-left: 0.75rem;
  margin: 0.5rem 0;
  color: #6b7280;
}

.rte-content :deep(.tiptap hr) {
  border: none;
  border-top: 1px solid #e5e7eb;
  margin: 0.75rem 0;
}

.rte-content :deep(.tiptap a) {
  color: #4f46e5;
  text-decoration: underline;
}

:where(.dark, .dark *) .rte-content :deep(.tiptap p.is-editor-empty:first-child::before) {
  color: #6b7280;
}

:where(.dark, .dark *) .rte-content :deep(.tiptap blockquote) {
  border-left-color: #4b5563;
  color: #9ca3af;
}

:where(.dark, .dark *) .rte-content :deep(.tiptap hr) {
  border-top-color: #374151;
}

:where(.dark, .dark *) .rte-content :deep(.tiptap a) {
  color: #818cf8;
}

.rte-content :deep(.tiptap strong) {
  font-weight: 600;
}

.rte-content :deep(.tiptap em) {
  font-style: italic;
}

.rte-content :deep(.tiptap s) {
  text-decoration: line-through;
}

/* Table styles */
.rte-content :deep(.tiptap table) {
  width: 100%;
  border-collapse: collapse;
  margin: 0.75rem 0;
  font-size: 0.875rem;
}

.rte-content :deep(.tiptap th),
.rte-content :deep(.tiptap td) {
  border: 1px solid #d1d5db;
  padding: 0.4rem 0.75rem;
  vertical-align: top;
  text-align: left;
}

.rte-content :deep(.tiptap th) {
  background-color: #f3f4f6;
  font-weight: 600;
}

.rte-content :deep(.tiptap .selectedCell) {
  background-color: #dbeafe;
}

:where(.dark, .dark *) .rte-content :deep(.tiptap th),
:where(.dark, .dark *) .rte-content :deep(.tiptap td) {
  border-color: #374151;
}

:where(.dark, .dark *) .rte-content :deep(.tiptap th) {
  background-color: #1f2937;
}

:where(.dark, .dark *) .rte-content :deep(.tiptap .selectedCell) {
  background-color: #1e3a5f;
}
</style>
