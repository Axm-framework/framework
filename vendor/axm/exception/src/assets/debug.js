	var traceReg     = new RegExp("(^|\\s)trace-file(\\s|$)");
	var collapsedReg = new RegExp("(^|\\s)collapsed(\\s|$)");

	var e = document.getElementsByTagName("div");
	for (var j = 0, len = e.length; j < len; j++) {
		if (traceReg.test(e[j].className)) {
			e[j].onclick = function() {
				var trace = this.parentNode.parentNode;
				if (collapsedReg.test(trace.className))
					trace.className = trace.className.replace("collapsed", "expanded");
				else
					trace.className = trace.className.replace("expanded", "collapsed");
			}
		}
	}


	function openTab(evt,cityName) {
		var i, tabcontent,tablinks;
		tabcontent = document.getElementsByClassName("tab-content");
		for(i=0; i < tabcontent.length; i++){
			tabcontent[i].style.display="none";
		}

		tablinks = document.getElementsByClassName("tablinks");
		for(i=0; i < tablinks.length; i++){
			tablinks[i].className = tablinks[i].className.replace("active", "");
		}

		document.getElementById(cityName).style.display = "block";
		evt.currentTarget.className += " active";
	}

	function copyTextToClipboard() {
		const errorfile   = document.querySelector(".source").textContent;
		const messageText = document.querySelector(".message p").textContent;
		const tracesText  = document.querySelector(".traces").textContent;

		// Crear un elemento de textarea temporal para copiar el texto
		const tempTextArea = document.createElement("textarea");
		tempTextArea.value = 'Error Axm Framework: ' +  messageText + ' File: '  + errorfile + ' Traces: ' + tracesText;
		document.body.appendChild(tempTextArea);
		tempTextArea.select();
		document.execCommand("copy");
		document.body.removeChild(tempTextArea);
		alert("Message copy");
	}

    function toggleDarkMode() {
        const body = document.body;
        body.classList.toggle("dark-mode");
    }




	// Esperar a que el documento esté completamente cargado antes de buscar el elemento
	document.addEventListener("DOMContentLoaded", function () {
	
		// Tab focus
		document.getElementById("default").click();
		//end 

		// Copy message
		const copyIcon = document.getElementById("copyIcon");
		copyIcon.addEventListener("click", copyTextToClipboard);
		//end


		// Agrega un evento clic al botón
		// const darkModeButton = document.getElementById("darkModeButton");
		// darkModeButton.addEventListener("click", toggleDarkMode);
		//end
	
		
	});
