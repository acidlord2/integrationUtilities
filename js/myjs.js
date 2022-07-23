function showLoad(windowText) {
	var overlay = document.getElementById("overlay");
	if (overlay == null) {
		var page = document.body;
		var overlay = document.createElement('div');
		overlay.id = "overlay";
		overlay.className = "overlay";
		overlay.innerHTML = '<div class="centered"><div class="inner">' + windowText + '</div></div>';
		page.appendChild(overlay);
	}		
}

function updateLoad(windowText) {
	var overlay = document.getElementById("overlay");
	if (overlay == null) {
		var page = document.body;
		var overlay = document.createElement('div');
		overlay.id = "overlay";
		overlay.className = "overlay";
		overlay.innerHTML = '<div class="centered">' + windowText + '</div>';
		page.appendChild(overlay);
	}
	else
		overlay.innerHTML = '<div class="centered">' + windowText + '</div>';
}

var keys = {37: 1, 38: 1, 39: 1, 40: 1};

function preventDefault(e) {
  e.preventDefault();
}

function preventDefaultForScrollKeys(e) {
  if (keys[e.keyCode]) {
    preventDefault(e);
    return false;
  }
}

// modern Chrome requires { passive: false } when adding event
var supportsPassive = false;
try {
  window.addEventListener("test", null, Object.defineProperty({}, 'passive', {
    get: function () { supportsPassive = true; } 
  }));
} catch(e) {}

var wheelOpt = supportsPassive ? { passive: false } : false;
var wheelEvent = 'onwheel' in document.createElement('div') ? 'wheel' : 'mousewheel';


function disableScroll() {
  window.addEventListener('DOMMouseScroll', preventDefault, false); // older FF
  window.addEventListener(wheelEvent, preventDefault, wheelOpt); // modern desktop
  window.addEventListener('touchmove', preventDefault, wheelOpt); // mobile
  window.addEventListener('keydown', preventDefaultForScrollKeys, false);
}

function enableScroll() {
  window.removeEventListener('DOMMouseScroll', preventDefault, false);
  window.removeEventListener(wheelEvent, preventDefault, wheelOpt); 
  window.removeEventListener('touchmove', preventDefault, wheelOpt);
  window.removeEventListener('keydown', preventDefaultForScrollKeys, false);
}

function deleteLoad(win) {
	var overlay = win.document.getElementById("overlay");
	if (overlay != null)
		overlay.parentNode.removeChild(overlay);
}
// Get the modal
var modal = document.getElementById("myModal");

// Get the text p of modal
var modalText = document.getElementById("modal-text");

// Get the <span> element that closes the modal
var span = document.getElementsByClassName("close")[0];

// When the user clicks the button, open the modal 
function showModal(txt) {
  modal.style.display = "block";
  modalText.innerHTML = txt;
  document.getElementById("barcodePack").blur();
}

function closeModal() {
  modal.style.display = "none";
  document.getElementById("barcodePack").focus();
}


// When the user clicks on <span> (x), close the modal
span.onclick = function() {
  closeModal();
}

// When the user clicks anywhere outside of the modal, close it
window.onclick = function(event) {
  if (event.target == modal) {
    closeModal();
  }
}

window.onscroll = function() {myFunction()};

// Get the header
var header = document.getElementById("header");

// Get the offset position of the navbar
var sticky = header.offsetTop;

// Add the sticky class to the header when you reach its scroll position. Remove "sticky" when you leave the scroll position
function myFunction() {
  if (window.pageYOffset > sticky) {
    header.classList.add("sticky");
  } else {
    header.classList.remove("sticky");
  }
}

function playAudio(sound) {
	var audio = new Audio('/js/' + sound);
	audio.play();
}

// Fetch data
