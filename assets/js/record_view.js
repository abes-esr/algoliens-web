var $ = require('jquery');
$( document ).ready(function() {
    $.get( url_raw_record, function( response ) {
        $("#sudoc_record").html(response );
    });
});