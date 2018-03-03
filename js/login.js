function login(formId) {
    var form = $(formId);
    $('input[name="password"]').removeClass('error');
    $.ajax({
        "url":"php/api.php",
        "data":{
            "command":"login",
            "password":form.find('input[name="password"]').val()
        },
        "method":"POST",
        "dataType":"JSON",
        "success":function(resp) {
            if (resp.success) {
                // Successfully loggedin
                $('input[name="password"]').addClass('success');
                $('input[name="password"]').removeClass('error');
                $('input[name="password"]').val('');
                location.reload();
            } else {
                // Failed to login
                $('input[name="password"]').addClass('error');
                $('input[name="password"]').val('');
            }
        },
        "error":function() {
            console.error('UNKNOWN ERROR OCCURED');
        }
    })
}