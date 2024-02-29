// This concept is Jonathan Reinink - thanks main!
export function showHtmlModal(html) {
    // Create a new modal only if one does not already exist.
    let modal = document.getElementById('raxm-error');

    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'raxm-error';
        modal.style.cssText = `
            position: fixed;
            width: 100vw;
            height: 100vh;
            padding: 50px;
            background-color: rgba(0, 0, 0, 0.6);
            z-index: 200000;
        `;

        // Close on click.
        modal.addEventListener('click', () => hideHtmlModal(modal));
        
        modal.tabIndex = 0; // Add tabindex to make the modal focusable.
        document.body.prepend(modal);
        document.body.style.overflow = 'hidden'; // Hide background scrolling.
    }

    let iframe = document.createElement('iframe');
    iframe.style.cssText = `
        background-color: #17161A;
        border-radius: 5px;
        width: 100%;
        height: 100%;
    `;

    modal.innerHTML = ''; // Clean previous contents if any.
    modal.appendChild(iframe);

    iframe.contentWindow.document.open();
    iframe.contentWindow.document.write(html);
    iframe.contentWindow.document.close();

    // Close on escape key press.
    modal.addEventListener('keydown', e => {
        if (e.key === 'Escape') hideHtmlModal(modal);
    });

    modal.focus(); // Focusing the modal so that it can receive keyboard events.
}

export function hideHtmlModal(modal) {
    modal.outerHTML = ''; // Eliminate the modal.
    document.body.style.overflow = 'visible'; // Restore the background shift.
}
