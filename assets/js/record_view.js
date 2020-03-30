var $ = require('jquery');
$( document ).ready(function() {
    $.getJSON( url_raw_record, function( response ) {
        $("#sudoc_record").html(response["unimarc_record"] );
        if (response["title"] != "") {
            $("#record_title").append(" - <i>"  + response["title"] + "</i>");
        }
    });

    if (url_is_localized) {
        $.get( url_is_localized, function( response ) {
            // $("#sudoc_record").html(response );
            if (response != '') {
                $("#unloca").html(response);
                $("#unloca").show();
            }
        });
    }

    $("#btn_unimarc").click(function () {
        //open up the content needed - toggle the slide- if visible, slide up, if not slidedown.
        $("#sudoc_record").slideToggle(500, function () {
            //execute this after slideToggle is done
            //change text of header based on visibility of content div
            $("#btn_unimarc").text(function () {
                //change text based on condition
                return $("#sudoc_record").is(":visible") ? "Masquer la notice Unimarc" : "Afficher la notice unimarc";
            });
        });
        return false;
    });
});