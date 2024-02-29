import { getCsrfToken } from '../util/utils.js'

let uploadManagers = new WeakMap

function getUploadManager(component) {
    if (! uploadManagers.has(component)) {
        let manager = new UploadManager(component)

        uploadManagers.set(component, manager)

        manager.registerListeners()
    }

    return uploadManagers.get(component)
}

export function handleFileUpload(el, property, component, cleanup) {
    if (! (el.tagName === 'INPUT' && el.type === 'file')) return

    let manager = getUploadManager(component)

    let start  = () => el.dispatchEvent(new CustomEvent('raxm-upload-start',  { bubbles: true, detail: { id: component.id, property}}))
    let finish = () => el.dispatchEvent(new CustomEvent('raxm-upload-finish', { bubbles: true, detail: { id: component.id, property}}))
    let error  = () => el.dispatchEvent(new CustomEvent('raxm-upload-error',  { bubbles: true, detail: { id: component.id, property}}))
    let progress = (progressEvent) => {
        var percentCompleted = Math.round( (progressEvent.loaded * 100) / progressEvent.total )

        el.dispatchEvent(
            new CustomEvent('raxm-upload-progress', {
                bubbles: true, detail: { progress: percentCompleted }
            })
        )
    }

    let eventHandler = e => {
        if (e.target.files.length === 0) return

        start()

        if (e.target.multiple) {
            manager.uploadMultiple(property, e.target.files, finish, error, progress)
        } else {
            manager.upload(property, e.target.files[0], finish, error, progress)
        }
    }

    el.addEventListener('change', eventHandler)

    // There's a bug in browsers where selecting a file, removing it,
    // then re-adding it doesn't fire the change event. This fixes it.
    // Reference: https://stackoverflow.com/questions/12030686/html-input-file-selection-event-not-firing-upon-selecting-the-same-file
    let clearFileInputValue = () => { el.value = null }
    el.addEventListener('click', clearFileInputValue)

    cleanup(() => {
        el.removeEventListener('change', eventHandler)
        el.removeEventListener('click', clearFileInputValue)
    })
}

class UploadManager {
    constructor(component) {
        this.component = component
        this.uploadBag = new MessageBag
        this.removeBag = new MessageBag
    }

    registerListeners() {
        this.component.on('upload:generatedSignedUrl', (name, url) => {
            // We have to add reduntant "setLoading" calls because the dom-patch
            // from the first response will clear the setUploadLoading call
            // from the first upload call.
            setUploadLoading(this.component, name)

            this.handleSignedUrl(name, url)
        })

        this.component.on('upload:generatedSignedUrlForS3', (name, payload) => {
            setUploadLoading(this.component, name)

            this.handleS3PreSignedUrl(name, payload)
        })

        this.component.on('upload:finished', (name, tmpFilenames) => this.markUploadFinished(name, tmpFilenames))
        this.component.on('upload:errored',  (name) => this.markUploadErrored(name))
        this.component.on('upload:removed',  (name, tmpFilename) => this.removeBag.shift(name).finishCallback(tmpFilename))
    }

    upload(name, file, finishCallback, errorCallback, progressCallback) {
        this.setUpload(name, {
            files: [file],
            multiple: false,
            finishCallback,
            errorCallback,
            progressCallback,
        })
    }

    uploadMultiple(name, files, finishCallback, errorCallback, progressCallback) {
        this.setUpload(name, {
            files: Array.from(files),
            multiple: true,
            finishCallback,
            errorCallback,
            progressCallback,
        })
    }

    removeUpload(name, tmpFilename, finishCallback) {
        this.removeBag.push(name, {
            tmpFilename, finishCallback
        })

        this.component.call('removeUpload', name, tmpFilename);
    }

    setUpload(name, uploadObject) {

        this.uploadBag.add(name, uploadObject)

        if (this.uploadBag.get(name).length === 1) {
    
            this.startUpload(name, uploadObject)
        }
    }

    handleSignedUrl(name, url) {

        let formData = new FormData()
        Array.from(this.uploadBag.first(name).files).forEach(file => formData.append('files[]', file, file.name))
    
        let headers = {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-Axm': true,
        }

        let csrfToken = getCsrfToken()

        if (csrfToken) headers['X-CSRF-TOKEN'] = csrfToken

        this.makeRequest(name, formData, 'post', url, headers, response => {
            return response.paths
        })
    }

    handleS3PreSignedUrl(name, payload) {
        let formData = this.uploadBag.first(name).files[0]
        let headers  = payload.headers

        if ('Host' in headers) delete headers.Host
        let url = payload.url

        this.makeRequest(name, formData, 'put', url, headers, response => {
            return [payload.path]
        })
    }

    makeRequest(name, formData, method, url, headers, retrievePaths) {

        let request = new XMLHttpRequest()
        request.open(method, url)

        Object.entries(headers).forEach(([key, value]) => {
            request.setRequestHeader(key, value)
        })

        request.upload.addEventListener('progress', e => {
            e.detail = {}
            e.detail.progress = Math.round((e.loaded * 100) / e.total)

            this.uploadBag.first(name).progressCallback(e)
        })

        request.addEventListener('load', () => {
            if ((request.status+'')[0] === '2') {
                let paths = retrievePaths(request.response && JSON.parse(request.response))

                this.component.$raxm.call('finishUpload', name, paths, this.uploadBag.first(name).multiple)

                return
            }

            let errors = null

            if (request.status === 422) {
                errors = request.response
            }

            this.component.$raxm.call('uploadErrored', name, errors, this.uploadBag.first(name).multiple)
        })

        request.send(formData)
    }

    startUpload(name, uploadObject) {
        let fileInfos = uploadObject.files.map(file => {
            return { name: file.name, size: file.size, type: file.type }
        })

        this.component.$raxm.call('startUpload', name, fileInfos, uploadObject.multiple);

        setUploadLoading(this.component, name)
    }

    markUploadFinished(name, tmpFilenames) {
        unsetUploadLoading(this.component)

        let uploadObject = this.uploadBag.shift(name)
        uploadObject.finishCallback(uploadObject.multiple ? tmpFilenames : tmpFilenames[0])

        if (this.uploadBag.get(name).length > 0) this.startUpload(name, this.uploadBag.last(name))
    }

    markUploadErrored(name) {
        unsetUploadLoading(this.component)

        this.uploadBag.shift(name).errorCallback()

        if (this.uploadBag.get(name).length > 0) this.startUpload(name, this.uploadBag.last(name))
    }
}

export default class MessageBag {
    constructor() {
        this.bag = {}
    }

    add(name, thing) {
        if (! this.bag[name]) {
            this.bag[name] = []
        }

        this.bag[name].push(thing)
    }

    push(name, thing) {
        this.add(name, thing)
    }

    first(name) {
        if (! this.bag[name]) return null

        return this.bag[name][0]
    }

    last(name) {
        return this.bag[name].slice(-1)[0]
    }

    get(name) {
        return this.bag[name]
    }

    shift(name) {
        return this.bag[name].shift()
    }

    call(name, ...params) {
        (this.listeners[name] || []).forEach(callback => {
            callback(...params)
        })
    }

    has(name) {
        return Object.keys(this.listeners).includes(name)
    }
}

function setUploadLoading() {
    // @todo
}

function unsetUploadLoading() {
    // @todo
}

export function upload(
    component,
    name,
    file,
    finishCallback = () => { },
    errorCallback = () => { },
    progressCallback = () => { }
) {
    let uploadManager = getUploadManager(component)

    uploadManager.upload(
        name,
        file,
        finishCallback,
        errorCallback,
        progressCallback
    )
}

export function uploadMultiple(
    component,
    name,
    files,
    finishCallback = () => { },
    errorCallback = () => { },
    progressCallback = () => { }
) {
    let uploadManager = getUploadManager(component)

    uploadManager.uploadMultiple(
        name,
        files,
        finishCallback,
        errorCallback,
        progressCallback
    )
}

export function removeUpload(
    component,
    name,
    tmpFilename,
    finishCallback = () => { },
    errorCallback = () => { }
) {
    let uploadManager = getUploadManager(component)

    uploadManager.removeUpload(
        name,
        tmpFilename,
        finishCallback,
        errorCallback
    )
}
