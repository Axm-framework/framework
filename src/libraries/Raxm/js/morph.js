import store from './store.js'
import { getDirectives, PREFIX_REGEX, PREFIX_DISPLAY } from './directives.js'
import nodeInitializer from './node_initializer.js'
import morphdom from './dom/morphdom/index.js'
import DOM from './dom/dom.js'
import Component from './component.js';
import { alpinifyElementsForMorphdom } from './features/supportAlpine.js'

export function morph(el, html) {

    let morphChanges = { changed: [], added: [], removed: [] }
    let id = el.__raxm.id
   
    // trigger('morph', { el, toEl: to, component })

    morphdom(el, html, {
        childrenOnly: false,

        getNodeKey: node => {
            if (isntElement(node)) return

            // This allows the tracking of elements by the "key" attribute, like in VueJs.
            return node.hasAttribute(`${PREFIX_REGEX}key`)
                ?  node.getAttribute(`${PREFIX_REGEX}key`)
                : // If no "key", then first check for "axm:id", then "id"
                node.hasAttribute(`${PREFIX_DISPLAY}id`)
                ? node.getAttribute(`${PREFIX_REGEX}id`)
                : node.id
        },

        onBeforeNodeAdded: node => {
            //
        },

        onBeforeNodeDiscarded: node => {
            // If the node is from x-if with a transition.
            if (
                node.__x_inserted_me &&
                Array.from(node.attributes).some(attr =>
                    /x-transition/.test(attr.name)
                )
            ) {
                return false
            }
        },

        onNodeDiscarded: node => {
            store.callHook('element.removed', node, el)

            if (node.__raxm) {
                store.removeComponent(node.__raxm)
            }

           morphChanges.removed.push(node)
        },

        onBeforeElChildrenUpdated: node => {
            //
        },

        onBeforeElUpdated: (from, to) => {
        
            // if (isntElement(el)) return

            // Because morphdom also supports vDom nodes, it uses isSameNode to detect
            // sameness. When dealing with DOM nodes, we want isEqualNode, otherwise
            // isSameNode will ALWAYS return false.
            if (from.isEqualNode(to)) {
                return false
            }

            store.callHook('element.updating', from, to, el)

            // Reset the index of axm:modeled select elements in the
            // "to" node before doing the diff, so that the options
            // have the proper in-memory .selected value set.
            if (
                from.hasAttribute(`${PREFIX_REGEX}model`) &&
                from.tagName.toUpperCase() === 'SELECT'
            ) {
                to.selectedIndex = -1
            }

            let fromDirectives = getDirectives(from)

            // Honor the "axm:ignore" attribute or the .__raxm_ignore element property.
            if (
                fromDirectives.has('ignore')      ||
                from.__raxm_ignore      === true  ||
                from.__raxm_ignore_self === true
            ) {
                if (
                    (fromDirectives.has('ignore') &&
                        fromDirectives.get('ignore').modifiers.includes('self')
                    )   || from.__raxm_ignore_self === true
                ) {
                    // Don't update children of "axm:ignore.self" attribute.
                    from.skipElUpdatingButStillUpdateChildren = true
                } else {
                    return false
                }
            }

            //Children will update themselves.
        if (DOM.isComponentRootEl(from) && from.getAttribute(`${PREFIX_DISPLAY}id`) !== id) return false

            
            // Give the root Raxm "to" element, the same object reference as the "from"
            // element. This ensures new Alpine magics like $raxm and @entangle can
            // initialize in the context of a real Raxm component object.
            if (DOM.isComponentRootEl(from)) to.__raxm = el

            alpinifyElementsForMorphdom(from, to)
        },

        onElUpdated: node => {
            // if (isntElement(el)) return
            morphChanges.changed.push(node)

            store.callHook('element.updated', node, el)
        },

        onNodeAdded: node => {
            // if (isntElement(el)) return

            const closestComponentId = DOM.closestRoot(node).getAttribute(`${PREFIX_REGEX}id`)

            if (closestComponentId === id) {
                if (nodeInitializer.initialize(node, el) === false) {
                    return false
                }
            } else if (DOM.isComponentRootEl(node)) {
                store.addComponent(new Component(node, el.connection))

                // We don't need to initialize children, the
                // new Component constructor will do that for us.
                node.skipAddingChildren = true
            }

          morphChanges.added.push(node)
        },    
                
    })
    
    window.skipShow = false

    function isntElement(el) {
        return typeof el.hasAttribute !== 'function'
    }

    function isComponentRootEl(el) {
        return el.hasAttribute(PREFIX_DISPLAY)
    }

}