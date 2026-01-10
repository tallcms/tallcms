import Sortable from 'sortablejs'

export default function treeManager({ parentId = null }) {
    return {
        parentId,
        sortable: null,
        init () {
            this.sortable = new Sortable(this.$el, {
                group: 'nested',
                animation: 150,
                fallbackOnBody: true,
                swapThreshold: 0.50,
                draggable: '[data-sortable-item]',
                handle: '[data-sortable-handle]',
                onEnd: (evt) => {
                    console.log(evt, 'onEnd');
                    let info = {
                        id: evt.item.dataset.id,
                        ancestor: evt.from.dataset.id,
                        parent: evt.to.dataset.id,
                        from: evt.oldIndex,
                        to: evt.newIndex
                    }

                    if (info.parent !== info.ancestor || info.from !== info.to) {
                        this.$wire.mountAction('moveNode', info)
                    }
                }
            })
        },
    }
}