import store from '../store.js'
import { overrideMethod } from '../$raxm.js'

store.registerHook('message.received', (message, component) => {
    let response = message.response

    let js  = response.effects.js
    let xjs = response.effects.xjs

    if (js) {
        Object.entries(js).forEach(([method, body]) => {
            overrideMethod(component, method, () => {
               evaluate(component.el, body)
            })
        })
    }

    if (xjs) {
        xjs.forEach(expression => {
            evaluate(component.el, expression)
        })
    }
})


//1
// Función para evaluar código en un worker web
// export async function evaluate(code) {
//     try {
//         const blob = new Blob([code], { type: "application/javascript" });
//         const blobUrl = URL.createObjectURL(blob);

//         const worker = new Worker(blobUrl);
//         const result = await new Promise((resolve, reject) => {
//             worker.onmessage = (e) => resolve(e.data);
//             worker.onerror = (error) => reject(error.message);
//         });

//         URL.revokeObjectURL(blobUrl);
//         return result;
//     } catch (error) {
//         console.error("Error during evaluation:", error);
//         throw error;
//     }
// }




//2
// export function evaluate(expression, el) {

//     if (typeof expression === 'function') {
//         return generateEvaluatorFromFunction(dataStack, expression)
//     }

//     generateFunctionFromString(expression, el)
// }

// export function generateEvaluatorFromFunction(dataStack, func) {
//     return (receiver = () => {}, { scope = {}, params = [] } = {}) => {
//         let result = func.apply(mergeProxies([scope, ...dataStack]), params)

//         runIfTypeOfFunction(receiver, result)
//     }
// }

// export function runIfTypeOfFunction(receiver, value, scope, params, el) {
//     if (shouldAutoEvaluateFunctions && typeof value === 'function') {
//         let result = value.apply(scope, params)

//         if (result instanceof Promise) {
//             result.then(i => runIfTypeOfFunction(receiver, i, scope, params)).catch( error => handleError( error, el, value ) )
//         } else {
//             receiver(result)
//         }
//     } else {
//         receiver(value)
//     }
// }

// let evaluatorMemo = {}

// function generateFunctionFromString(expression, el) {
//     if (evaluatorMemo[expression]) {
//         return evaluatorMemo[expression]
//     }

//     let AsyncFunction = Object.getPrototypeOf(async function(){}).constructor

//     // Some expressions that are useful in Alpine are not valid as the right side of an expression.
//     // Here we'll detect if the expression isn't valid for an assignement and wrap it in a self-
//     // calling function so that we don't throw an error AND a "return" statement can b e used.
//     let rightSideSafeExpression = 0
//         // Support expressions starting with "if" statements like: "if (...) doSomething()"
//         || /^[\n\s]*if.*\(.*\)/.test(expression)
//         // Support expressions starting with "let/const" like: "let foo = 'bar'"
//         || /^(let|const)\s/.test(expression)
//             ? `(() => { ${expression} })()`
//             : expression

//     const safeAsyncFunction = () => {
//         try {
//             return new AsyncFunction(['__self', 'scope'], `with (scope) { __self.result = ${rightSideSafeExpression} }; __self.finished = true; return __self.result;`)
//         } catch ( error ) {
//             handleError( error, el, expression )
//             return Promise.resolve()
//         }
//     }

//     let func = safeAsyncFunction()

//     evaluatorMemo[expression] = func

//     return func
// }

// export function mergeProxies(objects) {
//     let thisProxy = new Proxy({}, {
//         ownKeys: () => {
//             return Array.from(new Set(objects.flatMap(i => Object.keys(i))))
//         },

//         has: (target, name) => {
//             return objects.some(obj => obj.hasOwnProperty(name))
//         },

//         get: (target, name) => {
//             return (objects.find(obj => {
//                 if (obj.hasOwnProperty(name)) {
//                     let descriptor = Object.getOwnPropertyDescriptor(obj, name)

//                     // If we already bound this getter, don't rebind.
//                     if ((descriptor.get && descriptor.get._x_alreadyBound) || (descriptor.set && descriptor.set._x_alreadyBound)) {
//                         return true
//                     }
                    
//                     // Properly bind getters and setters to this wrapper Proxy.
//                     if ((descriptor.get || descriptor.set) && descriptor.enumerable) {
//                         // Only bind user-defined getters, not our magic properties.
//                         let getter = descriptor.get
//                         let setter = descriptor.set
//                         let property = descriptor

//                         getter = getter && getter.bind(thisProxy)
//                         setter = setter && setter.bind(thisProxy)

//                         if (getter) getter._x_alreadyBound = true
//                         if (setter) setter._x_alreadyBound = true

//                         Object.defineProperty(obj, name, {
//                             ...property,
//                             get: getter,
//                             set: setter,
//                         })
//                     }

//                     return true 
//                 }

//                 return false
//             }) || {})[name]
//         },

//         set: (target, name, value) => {
//             let closestObjectWithKey = objects.find(obj => obj.hasOwnProperty(name))

//             if (closestObjectWithKey) {
//                 closestObjectWithKey[name] = value
//             } else {
//                 objects[objects.length - 1][name] = value
//             }

//             return true
//         },
//     })

//     return thisProxy
// }

// export function handleError(error, el, expression = undefined) {
//     Object.assign( error, { el, expression } )

//     console.warn(`Raxm Expression Error: ${error.message}\n\n${ expression ? 'Expression: \"' + expression + '\"\n\n' : '' }`, el)

//     setTimeout( () => { throw error }, 0 )
// }



//3

const cache = new Map();

export async function evaluate(code, context) {
    const sandbox = new InMemorySandbox(context);

    // Ejecutar en sandbox
    let result;
    try {
        result = await new Function('sandbox', `with (sandbox) { ${code} }`).call(null, sandbox);
    } catch (error) {
        // Manejo centralizado de errores
        handleError(error);
    }

    // Sanitizar entrada y salida
    if (typeof result === 'string') {
        result = sanitize(result);
    }

    // Retornar estandarizado
    const output = {
        result,
        context,
        key
    };

    // Caching
    cache.set(key, output);

    return output;
}

function handleError(error) {
    // Reportar error
    console.error(error);
    // Opcional: lanzar nuevo error
    throw new Error('Evaluation failed');
}

function sanitize(value) {
    // Aquí debes implementar la lógica para sanitizar el valor
    // Esto puede incluir eliminar caracteres no deseados, escapar contenido peligroso, etc.

    // Ejemplo básico: Reemplazar todas las etiquetas HTML con una cadena vacía
    const sanitizedValue = value.replace(/<[^>]*>/g, '');

    return sanitizedValue;
}

class InMemorySandbox {
    constructor(context) {
        this.context = context;
        this.objects = new Map();
    }

    get(name) {
        if (this.context.hasOwnProperty(name)) {
            return this.context[name];
        } else if (this.objects.has(name)) {
            return this.objects.get(name);
        } else {
            return undefined;
        }
    }

    set(name, value) {
        this.objects.set(name, value);
    }

    call(name, ...args) {
        const object = this.get(name);
        if (object === undefined) {
            throw new Error(`No existe el objeto ${name}`);
        }

        return object.apply(this, args);
    }

    create(constructor, ...args) {
        const object = new constructor(...args);
        this.objects.set(object.constructor.name, object);

        return object;
    }
}
