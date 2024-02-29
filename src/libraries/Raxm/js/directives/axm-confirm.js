import { directive, getDirectives } from '../directives.js'

directive('confirm', ({ el, directive }) => {
    let message = directive.expression
    let shouldPrompt = directive.modifiers.includes('prompt')

    // Convert sanitized linebreaks ("\n") to real line breaks...
    message = message.replaceAll('\\n', '\n')

    if (message === '') message = 'Are you sure?'

    el.__raxm_confirm = (action) => {
        if (shouldPrompt) {
            let [question, expected] = message.split('|')

            if (! expected) {
                console.warn('Raxm: Must provide expectation with axm:confirm.prompt')
            } else {
                let input = prompt(question)

                if (input === expected) {
                    action()
                }
            }
        } else {
            if (confirm(message)) action()
        }
    }
})
