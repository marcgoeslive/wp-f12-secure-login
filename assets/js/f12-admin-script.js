(function ($) {
    $(document).on("click", ".f12-admin-sidebar a", function (e) {
        e.preventDefault();
        var href = $(this).attr("href");
        href = href.substr(1, href.length);

        $(this).closest(".f12-admin").find(".active").removeClass();

        $(this).addClass("active");
        //$(".f12-admin-content").find(".active").removeClass();
        $("#"+href).addClass("active");
    });
})(jQuery);