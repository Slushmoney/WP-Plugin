function fetchdata(url, msg_text = 'not logged in') {

    var xhr = new XMLHttpRequest();
    xhr.open('GET', url);
    xhr.setRequestHeader('X_REQUESTED_WITH', 'xmlhttprequest');
    xhr.onload = function () {
        // alert(xhr.responseText);
        if (xhr.responseText == 'TRUE') {
            location.reload(true);

        } else {
            var node = document.getElementById("not_logged_msg");
            node.innerText = msg_text;
            node.style.display = "block";
            //setTimeout(fetchdata, 10000);
        }
    }




    xhr.send();
}
function smCheckLoggedIn(url) {

    var xhr = new XMLHttpRequest();
    xhr.open('GET', url);
    xhr.setRequestHeader('X_REQUESTED_WITH', 'xmlhttprequest');
    xhr.onload = function () {
        if (xhr.responseText == 'TRUE') {
            location.reload(true);
        } else {
            var modal = document.getElementById("smModal");
            modal.style.display = "none";
        }
    }
    xhr.send();
}

function smValidateNumForm(frm_donation) {
    var n = frm_donation.getElementById("donation").value;
  if( !isNaN(parseFloat(n)) && isFinite(n) && n>0 && n<=1000)
  {
     return true;
  }
  alert("Please enter numeric value between (1-1000)");
  return false;
}


// Get the modal
var modal = document.getElementById("smModal");
// Get the <span> element that closes the modal
var span = document.getElementById("smClose_warning_msg");

// When the user clicks on <span> (x), close the modal
if (typeof (span) != "undefined" && span !== null) {
    span.onclick = function () {
        modal.style.display = "none";
    }

    // When the user clicks anywhere outside of the modal, close it
    window.onclick = function (event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
}



function openCity(evt, cityName) {
  var i, tabcontent, tablinks;
  tabcontent = document.getElementsByClassName("tabcontent");
  for (i = 0; i < tabcontent.length; i++) {
    tabcontent[i].style.display = "none";
  }
  tablinks = document.getElementsByClassName("tablinks");
  for (i = 0; i < tablinks.length; i++) {
    tablinks[i].className = tablinks[i].className.replace(" active", "");
  }
  document.getElementById(cityName).style.display = "block";
  evt.currentTarget.className += " active";
}

// Get the element with id="defaultOpen" and click on it
//document.getElementById("defaultOpen").click();


      