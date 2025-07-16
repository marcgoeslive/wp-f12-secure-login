jQuery(document).ready(function($){
    if(typeof(wp) === "undefined"){
        return;
    }

    if(typeof(wp.passwordStrength) === "undefined"){
        return;
    }

    if($("#pass1-text").length === 0){
        return;
    }

    $(document).on("keyup","#pass1-text",function(){
        password_validate_on_load();
    });

    function password_validate_on_load(){
        // Validate one time
        var pass = $("#pass1-text").val();
        var score = wp.passwordStrength.meter(pass,wp.passwordStrength.userInputBlacklist(),pass);

        if($("#f12-strength-meter").length == 0){
            $("#pass1-text").parent().append("<input type='hidden' name='f12-strength-meter' value='' id='f12-strength-meter'>");
        }

        $("#f12-strength-meter").val(score);
    }

    // set a small delay to ensure the js is loaded
    setTimeout(function(){

        password_validate_on_load();
    },500);
});