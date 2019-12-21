$(document).ready(function () {
    $("#loader").hide();
    $("#btn-maj").click(function () {
        $('#majValidationModal').modal('toggle'); //or  $('#IDModal').modal('hide');
        // return false;
        $.ajax({
            url: 'processMaj.php',
            type: 'GET', // Le type de la requête HTTP, ici devenu POST
            dataType: 'html',

            success: function (response, statut) { // success est toujours en place, bien sûr !
                $("#result").append(response)

            },

            error: function (resultat, statut, erreur) {
                alert("MAJ KO")
            },
            beforeSend: function () {
                $("#loader").show();
                $("#btn-maj-modal").prop('disabled', true)
            },
            // hides the loader after completion of request, whether successfull or failor.
            complete: function () {
                $("#loader").hide();
                $("#btn-maj-modal").prop('disabled', false)
            },
        });
    });
});



