/**
 * Script to check/uncheck all boxes
 * Written by Jürgen K.
 */

/*jshint esversion: 6 */

window.onload = function () {

    function checkAll() {
        for (var i = 0; i < checkboxes.length; i++) {
            checkboxes[i].checked = true;
        }
        this.onclick = uncheckAll;
    }

    function uncheckAll() {
        for (var i = 0; i < checkboxes.length; i++) {
            checkboxes[i].checked = false;
        }
        this.onclick = checkAll;
    }

    const checkboxes = document.getElementsByName('input_templates[]');
    if(checkboxes.length != 0){
        const toggle = document.getElementById('toggle-link');
        toggle.onclick = checkAll;
    }

};
