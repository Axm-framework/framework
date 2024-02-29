    const NProgress = {
        version: '0.2.0',
        settings: {
        minimum: 0.08,
        easing: 'linear',
        positionUsing: '',
        speed: 200,
        trickle: true,
        trickleSpeed: 200,
        showSpinner: true,
        barSelector: '[role="bar"]',
        spinnerSelector: '[role="spinner"]',
        parent: 'body',
        template: `
            <div class="bar" role="bar"><div class="peg"></div></div>
            <div class="spinner" role="spinner"><div class="spinner-icon"></div></div>
        `
        },
        status: null,
    
        configure(options) {
            Object.assign(this.settings, options)
            return this
        },
    
        set(n) {
            const started = this.isStarted()
            n = this.clamp(n, this.settings.minimum, 1)
            this.status = n === 1 ? null : n
        
            const progress = this.render(!started)
            const bar = progress.querySelector(this.settings.barSelector)
            const { speed, easing } = this.settings
        
            progress.offsetWidth // Repaint
        
            this.queue((next) => {
                if (this.settings.positionUsing === '') {
                    this.settings.positionUsing = this.getPositioningCSS()
                }
        
                this.css(bar, this.barPositionCSS(n, speed, easing))
        
                if (n === 1) {
                    this.css(progress, {
                        transition: 'none',
                        opacity: 1
                    })
                    
                    progress.offsetWidth // Repaint
            
                    setTimeout(() => {
                        this.css(progress, {
                            transition: `all ${speed}ms linear`,
                            opacity: 0
                        })
                        setTimeout(() => {
                            this.remove()
                            next()
                        }, speed)
                    }, speed)

                } else {
                    setTimeout(next, speed)
                }
            })
        
            return this
        },
    
        isStarted() {
            return typeof this.status === 'number'
        },
    
        start() {
            if (!this.status) {
                this.set(0)
            }
        
            const work = () => {
                setTimeout(() => {
                    if (!this.status) return
                    this.trickle()
                    work()
                }, this.settings.trickleSpeed)
            }
        
            if (this.settings.trickle) work()
        
            return this
        },
    
        done(force) {
            if (!force && !this.status) return this
            return this.inc(0.3 + 0.5 * Math.random()).set(1)
        },
    
        inc(amount) {
            let n = this.status
        
            if (!n) {
                return this.start()
            } else if (n > 1) {
                return
            } else {
                if (typeof amount !== 'number') {
                    if (n >= 0 && n < 0.2) {
                        amount = 0.1
                    } else if (n >= 0.2 && n < 0.5) {
                        amount = 0.04
                    } else if (n >= 0.5 && n < 0.8) {
                        amount = 0.02
                    } else if (n >= 0.8 && n < 0.99) {
                        amount = 0.005
                    } else {
                        amount = 0
                    }
                }
        
                n = this.clamp(n + amount, 0, 0.994)
                return this.set(n)
            }
        },
    
        trickle() {
            return this.inc()
        },
    
        render(fromStart) {
            if (this.isRendered()) return document.getElementById('nprogress')
        
            this.addClass(document.documentElement, 'nprogress-busy')
        
            const progress = document.createElement('div')
            progress.id = 'nprogress'
            progress.innerHTML = this.settings.template
        
            const bar = progress.querySelector(this.settings.barSelector)
            const perc = fromStart ? '-100' : this.toBarPerc(this.status || 0)
            const parent = this.isDOM(this.settings.parent)
                ? this.settings.parent
                : document.querySelector(this.settings.parent)
        
            let spinner
        
            this.css(bar, {
                transition: 'all 0 linear',
                transform: `translate3d(${perc}%,0,0)`
            })
        
            if (!this.settings.showSpinner) {
                spinner = progress.querySelector(this.settings.spinnerSelector)
                spinner && this.removeElement(spinner)
            }
        
            if (parent != document.body) {
                this.addClass(parent, 'nprogress-custom-parent')
            }
        
            parent.appendChild(progress)
            return progress
        },
    
        remove() {
            this.removeClass(document.documentElement, 'nprogress-busy')
            const parent = this.isDOM(this.settings.parent)
                ? this.settings.parent
                : document.querySelector(this.settings.parent)
        
            this.removeClass(parent, 'nprogress-custom-parent')
            const progress = document.getElementById('nprogress')
            progress && this.removeElement(progress)
        },
    
        isRendered() {
            return !!document.getElementById('nprogress')
        },
    
        getPositioningCSS() {
            const bodyStyle = document.body.style
                // Sniff prefixes
            const vendorPrefix = ('WebkitTransform' in bodyStyle) ? 'Webkit' :
                ('MozTransform' in bodyStyle) ? 'Moz' :
                ('msTransform' in bodyStyle) ? 'ms' :
                ('OTransform' in bodyStyle) ? 'O' : '';
        
            return vendorPrefix + 'Perspective' in bodyStyle
                ? 'translate3d'
                : vendorPrefix + 'Transform' in bodyStyle
                ? 'translate'
                : 'margin'
        },
    
        isDOM(obj) {
            if (typeof HTMLElement === 'object') {
                return obj instanceof HTMLElement
            }
            return (
                obj &&
                typeof obj === 'object' &&
                obj.nodeType === 1 &&
                typeof obj.nodeName === 'string'
            )
        },
    
        clamp(n, min, max) {
            if (n < min) return min
            if (n > max) return max
            return n
        },
    
        toBarPerc(n) {
            return (-1 + n) * 100
        },
    
        barPositionCSS(n, speed, ease) {
            let barCSS
        
            if (this.settings.positionUsing === 'translate3d') {
                barCSS = { transform: `translate3d(${this.toBarPerc(n)}%,0,0)` }
            } else if (this.settings.positionUsing === 'translate') {
                barCSS = { transform: `translate(${this.toBarPerc(n)}%,0)` }
            } else {
                barCSS = { 'margin-left': `${this.toBarPerc(n)}%` }
            }
        
            barCSS.transition = `all ${speed}ms ${ease}`
            return barCSS
        },
    
        queue: function() {
            const pending = []
          
            function next() {
                const fn = pending.shift()
                if (fn) {
                    fn(next)
                }
            }
          
            return function(fn) {
                pending.push(fn)
                if (pending.length === 1) next()
            }
        }(),
          
          
        css: (() => {
            const cssPrefixes = ['Webkit', 'O', 'Moz', 'ms']
            const cssProps = {}
        
            function camelCase(string) {
                return string
                    .replace(/^-ms-/, 'ms-')
                    .replace(/-([\da-z])/gi, (match, letter) => letter.toUpperCase())
            }
        
            function getVendorProp(name) {
                const style = document.body.style
                if (name in style) return name
        
                let i = cssPrefixes.length
                const capName = name.charAt(0).toUpperCase() + name.slice(1)
                let vendorName
                while (i--) {
                    vendorName = cssPrefixes[i] + capName
                    if (vendorName in style) return vendorName
                }
        
                return name
            }
        
            function getStyleProp(name) {
                name = camelCase(name)
                return cssProps[name] || (cssProps[name] = getVendorProp(name))
            }
        
            return (element, properties) => {
                let prop, value
        
                if (properties) {
                    for (prop in properties) {
                        value = properties[prop]
                        if (value !== undefined && properties.hasOwnProperty(prop)) {
                            const propToApply = getStyleProp(prop)
                            element.style[propToApply] = value
                        }
                    }
                }
            }
        })(),
        
        hasClass(element, name) {
            const list = typeof element === 'string' ? element : this.classList(element)
            return list.indexOf(` ${name} `) >= 0
        },
    
        addClass(element, name) {
            const oldList = this.classList(element)
            const newList = oldList + name
        
            if (!this.hasClass(oldList, name)) {
                element.className = newList.substring(1)
            }
        },
    
        removeClass(element, name) {
            const oldList = this.classList(element)
            let newList
        
            if (this.hasClass(element, name)) {
                newList = oldList.replace(` ${name} `, ' ')
                element.className = newList.substring(1, newList.length - 1)
            }
        },
    
        classList(element) {
            return (` ${element && element.className || ''} `).replace(/\s+/gi, ' ')
        },
    
        removeElement(element) {
            element && element.parentNode && element.parentNode.removeChild(element)
        }
    }
    
    export default NProgress
    