<script>
    jQuery(function ($) {
        $('#wplk-upgrade-button').click(function (e) {
            var confirmed = confirm("This action will modify your database. It is strongly recommended that you backup your database before proceeding. Are you sure you want to upgrade now?");
            if (confirmed !== true) {
                e.preventDefault();
            }
        });
    });
</script>