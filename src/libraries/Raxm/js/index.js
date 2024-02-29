import { directive } from "./directives.js";
import { start, stop, rescan } from "./boot.js";
import { find, first, getByName, all, on, trigger, hook } from "./store.js";

let Raxm = {
    directive,
    start,
    stop,
    rescan,
    find,
    first,
    getByName,
    all,
    on,
    trigger,
    hook,
};

if (window.Raxm) console.warn("Detected multiple instances of Raxm running");
if (window.Alpine)
    console.warn("Detected multiple instances of Alpine running");

// Register support...
import "./features/index.js";

// Register directives...
import "./directives/index.js";

if (window.Raxm === undefined) {
    document.addEventListener("DOMContentLoaded", () => {
        window.Raxm = Raxm;
        // Start Raxm...
        Raxm.start();
    });
}

export { Raxm };
