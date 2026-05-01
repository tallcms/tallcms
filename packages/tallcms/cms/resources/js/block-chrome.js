import { Extension } from '@tiptap/core'
import { Fragment } from '@tiptap/pm/model'
import { NodeSelection, Plugin, PluginKey } from '@tiptap/pm/state'

const PLUGIN_KEY = new PluginKey('cmsBlockChrome')
const INJECTED_FLAG = 'cmsChromeInjected'
const NODE_TYPE = 'customBlock'

const ICONS = {
    grip: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M7 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0ZM7 10a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0ZM7 16a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0ZM16 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0ZM16 10a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0ZM16 16a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z"/></svg>',
    up: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 17a.75.75 0 0 1-.75-.75V5.612L5.29 9.77a.75.75 0 1 1-1.08-1.04l5.25-5.5a.75.75 0 0 1 1.08 0l5.25 5.5a.75.75 0 0 1-1.08 1.04l-3.96-4.158V16.25A.75.75 0 0 1 10 17Z" clip-rule="evenodd"/></svg>',
    down: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 3a.75.75 0 0 1 .75.75v10.638l3.96-4.158a.75.75 0 1 1 1.08 1.04l-5.25 5.5a.75.75 0 0 1-1.08 0l-5.25-5.5a.75.75 0 1 1 1.08-1.04l3.96 4.158V3.75A.75.75 0 0 1 10 3Z" clip-rule="evenodd"/></svg>',
    duplicate: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M7 3.5A1.5 1.5 0 0 1 8.5 2h3.879a1.5 1.5 0 0 1 1.06.44l3.122 3.12A1.5 1.5 0 0 1 17 6.622V12.5a1.5 1.5 0 0 1-1.5 1.5h-1v-3.379a3 3 0 0 0-.879-2.121L10.5 5.379A3 3 0 0 0 8.379 4.5H7v-1Z"/><path d="M4.5 6A1.5 1.5 0 0 0 3 7.5v9A1.5 1.5 0 0 0 4.5 18h7a1.5 1.5 0 0 0 1.5-1.5v-5.879a1.5 1.5 0 0 0-.44-1.06L9.44 6.439A1.5 1.5 0 0 0 8.378 6H4.5Z"/></svg>',
}

function findTopLevelCustomBlockPos(view, blockDom) {
    let foundPos = -1
    view.state.doc.forEach((child, offset) => {
        if (foundPos !== -1) return
        if (child.type.name !== NODE_TYPE) return
        if (view.nodeDOM(offset) === blockDom) {
            foundPos = offset
        }
    })
    return foundPos
}

function makeButton({ className, title, html, onClick }) {
    const btn = document.createElement('button')
    btn.type = 'button'
    btn.className = className
    btn.title = title
    btn.setAttribute('aria-label', title)
    btn.innerHTML = html
    btn.addEventListener('click', (event) => {
        event.preventDefault()
        event.stopPropagation()
        onClick(event)
    })
    return btn
}

class BlockChromeView {
    constructor(view, editor) {
        this.view = view
        this.editor = editor
        this.observer = new MutationObserver(() => this.injectAll())
        this.observer.observe(view.dom, { childList: true, subtree: true })
        this.injectAll()
    }

    injectAll() {
        if (!this.view.editable) return
        const blocks = this.view.dom.querySelectorAll(
            `[data-type="${NODE_TYPE}"]`,
        )
        blocks.forEach((block) => this.inject(block))
    }

    inject(blockDom) {
        const header = blockDom.querySelector(
            '.fi-fo-rich-editor-custom-block-header',
        )
        if (!header || header.dataset[INJECTED_FLAG]) return
        header.dataset[INJECTED_FLAG] = '1'
        header.classList.add('fi-cms-block-chrome-host')

        const view = this.view
        const editor = this.editor

        const handle = document.createElement('span')
        handle.className = 'fi-cms-block-drag-handle'
        handle.title = 'Drag to reorder'
        handle.setAttribute('aria-hidden', 'true')
        handle.innerHTML = ICONS.grip

        const upBtn = makeButton({
            className: 'fi-icon-btn fi-cms-block-chrome-btn',
            title: 'Move block up',
            html: ICONS.up,
            onClick: () => {
                const pos = findTopLevelCustomBlockPos(view, blockDom)
                if (pos === -1) return
                editor.commands.moveCustomBlockUp(pos)
            },
        })

        const downBtn = makeButton({
            className: 'fi-icon-btn fi-cms-block-chrome-btn',
            title: 'Move block down',
            html: ICONS.down,
            onClick: () => {
                const pos = findTopLevelCustomBlockPos(view, blockDom)
                if (pos === -1) return
                editor.commands.moveCustomBlockDown(pos)
            },
        })

        const duplicateBtn = makeButton({
            className: 'fi-icon-btn fi-cms-block-chrome-btn',
            title: 'Duplicate block',
            html: ICONS.duplicate,
            onClick: () => {
                const pos = findTopLevelCustomBlockPos(view, blockDom)
                if (pos === -1) return
                editor.commands.duplicateCustomBlock(pos)
            },
        })

        const reorderGroup = document.createElement('div')
        reorderGroup.className = 'fi-cms-block-chrome-group'
        reorderGroup.appendChild(upBtn)
        reorderGroup.appendChild(downBtn)
        reorderGroup.appendChild(duplicateBtn)

        header.insertBefore(handle, header.firstChild)

        const deleteContainer = header.querySelector(
            '.fi-fo-rich-editor-custom-block-delete-btn-ctn',
        )
        if (deleteContainer) {
            header.insertBefore(reorderGroup, deleteContainer)
        } else {
            header.appendChild(reorderGroup)
        }
    }

    update() {
        this.injectAll()
    }

    destroy() {
        this.observer.disconnect()
    }
}

export default Extension.create({
    name: 'cmsBlockChrome',

    addCommands() {
        return {
            moveCustomBlockUp:
                (pos) =>
                ({ tr, state, dispatch }) => {
                    const node = state.doc.nodeAt(pos)
                    if (!node || node.type.name !== NODE_TYPE) return false

                    const $pos = state.doc.resolve(pos)
                    if ($pos.parent.type.name !== 'doc') return false

                    const index = $pos.index(0)
                    if (index === 0) return false

                    const prev = state.doc.child(index - 1)
                    const prevStart = pos - prev.nodeSize
                    const currentEnd = pos + node.nodeSize

                    if (dispatch) {
                        tr.replaceWith(
                            prevStart,
                            currentEnd,
                            Fragment.fromArray([node, prev]),
                        )
                        tr.setSelection(
                            NodeSelection.create(tr.doc, prevStart),
                        )
                        tr.scrollIntoView()
                        dispatch(tr)
                    }
                    return true
                },

            moveCustomBlockDown:
                (pos) =>
                ({ tr, state, dispatch }) => {
                    const node = state.doc.nodeAt(pos)
                    if (!node || node.type.name !== NODE_TYPE) return false

                    const $pos = state.doc.resolve(pos)
                    if ($pos.parent.type.name !== 'doc') return false

                    const index = $pos.index(0)
                    if (index >= state.doc.childCount - 1) return false

                    const next = state.doc.child(index + 1)
                    const currentStart = pos
                    const nextEnd = pos + node.nodeSize + next.nodeSize

                    if (dispatch) {
                        tr.replaceWith(
                            currentStart,
                            nextEnd,
                            Fragment.fromArray([next, node]),
                        )
                        tr.setSelection(
                            NodeSelection.create(
                                tr.doc,
                                currentStart + next.nodeSize,
                            ),
                        )
                        tr.scrollIntoView()
                        dispatch(tr)
                    }
                    return true
                },

            duplicateCustomBlock:
                (pos) =>
                ({ tr, state, dispatch }) => {
                    const node = state.doc.nodeAt(pos)
                    if (!node || node.type.name !== NODE_TYPE) return false

                    const $pos = state.doc.resolve(pos)
                    if ($pos.parent.type.name !== 'doc') return false

                    const insertPos = pos + node.nodeSize
                    const clone = node.type.create(
                        node.attrs,
                        node.content,
                        node.marks,
                    )

                    if (dispatch) {
                        tr.insert(insertPos, clone)
                        tr.setSelection(
                            NodeSelection.create(tr.doc, insertPos),
                        )
                        tr.scrollIntoView()
                        dispatch(tr)
                    }
                    return true
                },
        }
    },

    addProseMirrorPlugins() {
        const editor = this.editor

        return [
            new Plugin({
                key: PLUGIN_KEY,
                view(editorView) {
                    return new BlockChromeView(editorView, editor)
                },
            }),
        ]
    },
})
